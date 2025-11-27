<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Club;
use App\Models\FundTransaction;
use App\Models\FundTransactionPaymentProof;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class MatchController extends Controller
{
    /**
     * Lấy danh sách trận đấu của club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy club_id từ query parameter hoặc từ user hiện tại
            $clubId = $request->query('club_id') ?? $request->input('club_id');
            
            // Nếu không có club_id từ request, thử lấy từ user đăng nhập qua zalo_gid
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.',
                    'data' => []
                ], 400);
            }

            // Pagination parameters
            $limit = (int)($request->input('limit') ?? $request->query('limit') ?? 10);
            $offset = (int)($request->input('offset') ?? $request->query('offset') ?? 0);
            
            // Get total count trước khi paginate
            $totalCount = GameMatch::where('club_id', $clubId)->count();
            
            // Apply pagination
            $matches = GameMatch::with(['teams', 'creator'])
                ->where('club_id', $clubId)
                ->orderBy('match_date', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($match) use ($clubId) {
                    // Debug logging
                    \Log::info("MatchController::index - Processing match ID: {$match->id}");
                    
                    // Load teams trực tiếp từ database thay vì dùng relationship
                    $teams = \DB::table('teams')
                        ->where('match_id', $match->id)
                        ->orderBy('id', 'desc')
                        ->get();
                    
                    \Log::info("MatchController::index - Match {$match->id} teams loaded from DB:", [
                        'total_teams' => $teams->count(),
                        'teams' => $teams->map(function($t) { return ['id' => $t->id, 'name' => $t->name]; })->toArray()
                    ]);
                    
                    // Debug chi tiết teams collection
                    \Log::info("MatchController::index - Match {$match->id} teams collection:", [
                        'teams_count' => $teams->count(),
                        'teams_raw' => $teams->toArray(),
                        'teams_names' => $teams->pluck('name')->toArray()
                    ]);
                    
                    // Tìm team A và B sử dụng filter thay vì where
                    // Ưu tiên team có cầu thủ trước
                    $teamsA = $teams->filter(function($team) {
                        return str_contains($team->name, 'A');
                    });
                    
                    $teamsB = $teams->filter(function($team) {
                        return str_contains($team->name, 'B');
                    });
                    
                    // Tìm team A có cầu thủ trước, nếu không có thì lấy team A đầu tiên
                    $teamA = $teamsA->filter(function($team) {
                        $playerCount = \DB::table('team_players')->where('team_id', $team->id)->count();
                        return $playerCount > 0;
                    })->first();
                    
                    if (!$teamA) {
                        $teamA = $teamsA->first();
                    }
                    
                    // Tìm team B có cầu thủ trước, nếu không có thì lấy team B đầu tiên
                    $teamB = $teamsB->filter(function($team) {
                        $playerCount = \DB::table('team_players')->where('team_id', $team->id)->count();
                        return $playerCount > 0;
                    })->first();
                    
                    if (!$teamB) {
                        $teamB = $teamsB->first();
                    }
                    
                    \Log::info("MatchController::index - Match {$match->id} teams found:", [
                        'teamA' => $teamA ? ['id' => $teamA->id, 'name' => $teamA->name] : null,
                        'teamB' => $teamB ? ['id' => $teamB->id, 'name' => $teamB->name] : null
                    ]);
                    
                    // Debug logic tìm team
                    $teamsA = $teams->where('name', 'like', '%A%');
                    $teamsB = $teams->where('name', 'like', '%B%');
                    
                    \Log::info("MatchController::index - Match {$match->id} team filtering:", [
                        'teamsA_count' => $teamsA->count(),
                        'teamsA_names' => $teamsA->pluck('name')->toArray(),
                        'teamsB_count' => $teamsB->count(),
                        'teamsB_names' => $teamsB->pluck('name')->toArray()
                    ]);
                    
                    \Log::info("MatchController::index - Match {$match->id} teams selected:", [
                        'teamA_selected' => $teamA ? ['id' => $teamA->id, 'name' => $teamA->name] : null,
                        'teamB_selected' => $teamB ? ['id' => $teamB->id, 'name' => $teamB->name] : null
                    ]);
                    
                    // Nếu không có teams, log warning
                    if (!$teamA || !$teamB) {
                        \Log::warning("MatchController::index - Match {$match->id} missing teams");
                    }
                    
                    // Lấy players cho Team A (chỉ lấy active members)
                    $teamAPlayers = [];
                    if ($teamA) {
                        $teamAPlayers = \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->join('user_clubs', function($join) use ($clubId) {
                                $join->on('users.id', '=', 'user_clubs.user_id')
                                     ->where('user_clubs.club_id', '=', $clubId)
                                     ->where('user_clubs.is_active', '=', true)
                                     ->where('user_clubs.status', '=', 'active');
                            })
                            ->where('team_players.team_id', $teamA->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.phone', 'users.zalo_avatar')
                            ->distinct()
                            ->get();
                        
                        \Log::info("MatchController::index - Match {$match->id} Team A players:", [
                            'team_id' => $teamA->id,
                            'players_count' => $teamAPlayers->count(),
                            'players' => $teamAPlayers->toArray()
                        ]);
                        
                        $teamAPlayers = $teamAPlayers->map(function ($player) {
                            return [
                                'id' => $player->id,
                                'name' => $player->name,
                                'avatar' => $player->avatar,
                                'phone' => $player->phone
                            ];
                        });
                    }
                    
                    // Lấy players cho Team B (chỉ lấy active members)
                    $teamBPlayers = [];
                    if ($teamB) {
                        $teamBPlayers = \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->join('user_clubs', function($join) use ($clubId) {
                                $join->on('users.id', '=', 'user_clubs.user_id')
                                     ->where('user_clubs.club_id', '=', $clubId)
                                     ->where('user_clubs.is_active', '=', true)
                                     ->where('user_clubs.status', '=', 'active');
                            })
                            ->where('team_players.team_id', $teamB->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.phone', 'users.zalo_avatar')
                            ->distinct()
                            ->get();
                        
                        \Log::info("MatchController::index - Match {$match->id} Team B players:", [
                            'team_id' => $teamB->id,
                            'players_count' => $teamBPlayers->count(),
                            'players' => $teamBPlayers->toArray()
                        ]);
                        
                        $teamBPlayers = $teamBPlayers->map(function ($player) {
                            return [
                                'id' => $player->id,
                                'name' => $player->name,
                                'avatar' => $player->avatar,
                                'phone' => $player->phone
                            ];
                        });
                    }
                    
                    return [
                        'id' => $match->id,
                        'title' => $match->title,
                        'date' => $match->match_date->format('Y-m-d'),
                        'time' => $match->time,
                        'location' => $match->location,
                        'description' => $match->description,
                        'status' => $match->status,
                        'betAmount' => (float) $match->bet_amount,
                        'createdBy' => $match->creator->name ?? 'Unknown',
                        'createdAt' => $match->created_at->format('Y-m-d'),
                        'teamA' => [
                            'id' => $teamA?->id,
                            'name' => 'Đội A',
                            'players' => $teamAPlayers,
                            'score' => $teamA?->score,
                            'isWinner' => $teamA?->is_winner
                        ],
                        'teamB' => [
                            'id' => $teamB?->id,
                            'name' => 'Đội B',
                            'players' => $teamBPlayers,
                            'score' => $teamB?->score,
                            'isWinner' => $teamB?->is_winner
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $matches,
                'total' => $totalCount,
                'per_page' => $limit,
                'current_page' => ($offset / $limit) + 1,
                'has_more' => ($offset + $limit) < $totalCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo trận đấu mới
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Debug: Log tất cả request data
            \Log::info('MatchController::store - Request data:', [
                'all_input' => $request->all(),
                'headers' => $request->headers->all(),
                'zalo_gid_header' => $request->header('X-Zalo-GID'),
                'zalo_gid_input' => $request->input('zalo_gid')
            ]);
            
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|string', // Frontend gửi string, không phải date object
                'time' => 'nullable|string',
                'location' => 'nullable|string',
                'description' => 'nullable|string',
                'betAmount' => 'required|numeric|min:0',
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request data hoặc từ user đăng nhập qua zalo_gid
            $clubId = $request->input('club_id');
            $userId = null;
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        $userId = $user->id;
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }
            
            // Nếu chưa có userId, lấy từ request hoặc default
            if (!$userId) {
                $userId = $request->input('created_by') ?? 1; // Default user ID
            }

            // Debug: Log data trước khi tạo match
            \Log::info('MatchController::store - Creating match with data:', [
                'club_id' => $clubId,
                'title' => $request->title,
                'match_date' => $request->date,
                'time' => $request->time,
                'location' => $request->location,
                'description' => $request->description,
                'status' => 'upcoming',
                'bet_amount' => $request->betAmount,
                'created_by' => $userId,
            ]);

            // Validate và format date
            $matchDate = $request->date;
            if (is_string($matchDate)) {
                // Nếu date là string, thử parse thành date
                try {
                    $parsedDate = \Carbon\Carbon::parse($matchDate);
                    $matchDate = $parsedDate->format('Y-m-d');
                } catch (\Exception $e) {
                    \Log::warning('MatchController::store - Could not parse date:', [
                        'original_date' => $matchDate,
                        'error' => $e->getMessage()
                    ]);
                    // Sử dụng date gốc nếu không parse được
                }
            }

            DB::beginTransaction();

            // Tạo trận đấu
            try {
                $match = GameMatch::create([
                    'club_id' => $clubId,
                    'title' => $request->title,
                    'match_date' => $matchDate, // Sử dụng date đã format
                    'time' => $request->time,
                    'location' => $request->location,
                    'description' => $request->description,
                    'status' => 'upcoming',
                    'bet_amount' => $request->betAmount, // Frontend gửi 'betAmount', map thành 'bet_amount'
                    'created_by' => $userId,
                ]);
            } catch (\Exception $e) {
                \Log::error('MatchController::store - Error creating match:', [
                    'club_id' => $clubId,
                    'title' => $request->title,
                    'match_date' => $matchDate,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e; // Re-throw để catch block chính xử lý
            }

            // Debug: Log match created
            \Log::info('MatchController::store - Match created successfully:', [
                'match_id' => $match->id,
                'match_title' => $match->title
            ]);

            // Tạo 2 đội mặc định
            try {
                $teamA = Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội A',
                ]);

                $teamB = Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội B',
                ]);

                // Debug: Log teams created
                \Log::info('MatchController::store - Teams created successfully:', [
                    'team_a_id' => $teamA->id,
                    'team_b_id' => $teamB->id
                ]);
            } catch (\Exception $e) {
                \Log::error('MatchController::store - Error creating teams:', [
                    'match_id' => $match->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e; // Re-throw để catch block chính xử lý
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo trận đấu thành công',
                'data' => $match
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Debug: Log exception details
            \Log::error('MatchController::store - Exception occurred:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin trận đấu
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Debug: Log request data
            \Log::info('MatchController::update - Request data:', [
                'id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|string',
                'time' => 'nullable|string|max:10',
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'betAmount' => 'required|numeric|min:0',
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                \Log::warning('MatchController::update - Validation failed:', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            \Log::info('MatchController::update - Club ID from request/header:', ['club_id' => $clubId]);
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                \Log::info('MatchController::update - Zalo GID:', ['zalo_gid' => $zaloGid]);
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        \Log::info('MatchController::update - User found:', ['user_id' => $user->id]);
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                            \Log::info('MatchController::update - UserClub found:', ['club_id' => $clubId]);
                        } else {
                            \Log::warning('MatchController::update - No active UserClub found for user:', ['user_id' => $user->id]);
                        }
                    } else {
                        \Log::warning('MatchController::update - No user found for zalo_gid:', ['zalo_gid' => $zaloGid]);
                    }
                }
            }
            
            if (!$clubId) {
                \Log::error('MatchController::update - No club_id determined');
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                \Log::warning('MatchController::update - Match not found:', [
                    'match_id' => $id,
                    'club_id' => $clubId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            \Log::info('MatchController::update - Match found:', [
                'match_id' => $match->id,
                'match_title' => $match->title
            ]);

            // Format date properly
            $matchDate = \Carbon\Carbon::parse($request->date);

            \Log::info('MatchController::update - Updating match with data:', [
                'title' => $request->title,
                'match_date' => $matchDate,
                'time' => $request->time,
                'location' => $request->location,
                'description' => $request->description,
                'bet_amount' => $request->betAmount
            ]);

            try {
                $updateData = [
                    'title' => $request->title,
                    'match_date' => $matchDate,
                    'time' => $request->time,
                    'location' => $request->location,
                    'description' => $request->description,
                    'bet_amount' => $request->betAmount,
                ];
                
                \Log::info('MatchController::update - Update data prepared:', $updateData);
                
                $result = $match->update($updateData);
                
                \Log::info('MatchController::update - Update result:', ['result' => $result]);
                
                if ($result) {
                    \Log::info('MatchController::update - Match updated successfully');
                } else {
                    \Log::warning('MatchController::update - Update returned false');
                }
                
                // Refresh the model to get updated data
                $match->refresh();
                
            } catch (\Exception $e) {
                \Log::error('MatchController::update - Error during update:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trận đấu thành công',
                'data' => $match
            ]);
        } catch (\Exception $e) {
            \Log::error('MatchController::update - Exception occurred:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method để tạo hoặc lấy teams cho một match
     * Đảm bảo không có teams trùng lặp
     */
    private function getOrCreateTeams($matchId)
    {
        // Lấy teams hiện có
        $teams = Team::where('match_id', $matchId)->get();
        
        // Tìm teams theo tên chính xác
        $teamA = $teams->where('name', 'Đội A')->first();
        $teamB = $teams->where('name', 'Đội B')->first();
        
        // Nếu có nhiều hơn 2 teams, xóa teams trùng lặp
        if ($teams->count() > 2) {
            \Log::warning('MatchController::getOrCreateTeams - Found duplicate teams:', [
                'match_id' => $matchId,
                'total_teams' => $teams->count(),
                'teams' => $teams->map(function($t) { 
                    return ['id' => $t->id, 'name' => $t->name]; 
                })->toArray()
            ]);
            
            // Xóa tất cả teams trừ 2 teams đầu tiên
            $teamsToDelete = $teams->slice(2);
            foreach ($teamsToDelete as $team) {
                \Log::info('MatchController::getOrCreateTeams - Deleting duplicate team:', [
                    'team_id' => $team->id,
                    'name' => $team->name
                ]);
                $team->delete();
            }
            
            // Lấy lại teams sau khi xóa
            $teams = Team::where('match_id', $matchId)->get();
            $teamA = $teams->where('name', 'Đội A')->first();
            $teamB = $teams->where('name', 'Đội B')->first();
        }
        
        // Tạo Team A nếu chưa có
        if (!$teamA) {
            $teamA = Team::create([
                'match_id' => $matchId,
                'name' => 'Đội A',
            ]);
            \Log::info('MatchController::getOrCreateTeams - Created Team A:', ['id' => $teamA->id]);
        }
        
        // Tạo Team B nếu chưa có
        if (!$teamB) {
            $teamB = Team::create([
                'match_id' => $matchId,
                'name' => 'Đội B',
            ]);
            \Log::info('MatchController::getOrCreateTeams - Created Team B:', ['id' => $teamB->id]);
        }
        
        return [$teamA, $teamB];
    }

    /**
     * Cập nhật thành viên cho các đội
     */
    public function updateTeams(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'teamAPlayers' => 'array',
                'teamBPlayers' => 'array',
                'teamAPlayers.*' => 'exists:users,id',
                'teamBPlayers.*' => 'exists:users,id',
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validation số lượng cầu thủ
            $teamAPlayers = $request->teamAPlayers ?? [];
            $teamBPlayers = $request->teamBPlayers ?? [];

            if (count($teamAPlayers) < 1 || count($teamAPlayers) > 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đội A phải có từ 1 đến 2 cầu thủ'
                ], 422);
            }

            if (count($teamBPlayers) < 1 || count($teamBPlayers) > 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đội B phải có từ 1 đến 2 cầu thủ'
                ], 422);
            }

            // Kiểm tra cầu thủ không được ở cả 2 đội
            $duplicatePlayers = array_intersect($teamAPlayers, $teamBPlayers);
            if (!empty($duplicatePlayers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cầu thủ không thể ở cả 2 đội'
                ], 422);
            }

            // Lấy club_id từ request hoặc từ zalo_gid
            $clubId = $request->input('club_id');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            // Validate tất cả players phải là active members của club
            $allPlayers = array_merge($teamAPlayers, $teamBPlayers);
            $activeMembers = \App\Models\UserClub::where('club_id', $clubId)
                ->where('is_active', true)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();
            
            $invalidPlayers = array_diff($allPlayers, $activeMembers);
            if (!empty($invalidPlayers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số cầu thủ không phải là thành viên active của câu lạc bộ. Vui lòng chỉ chọn các thành viên đã được duyệt.',
                    'invalid_players' => $invalidPlayers
                ], 422);
            }

            // Lấy hoặc tạo teams sử dụng helper method
            [$teamA, $teamB] = $this->getOrCreateTeams($match->id);

            DB::beginTransaction();

            // Xóa tất cả thành viên khỏi các đội
            \DB::table('team_players')->where('team_id', $teamA->id)->delete();
            \DB::table('team_players')->where('team_id', $teamB->id)->delete();

            // Thêm thành viên cho đội A
            if (!empty($teamAPlayers)) {
                foreach ($teamAPlayers as $playerId) {
                    \DB::table('team_players')->insert([
                        'team_id' => $teamA->id,
                        'user_id' => $playerId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                \Log::info('MatchController::updateTeams - Added players to Team A:', ['players' => $teamAPlayers]);
            }

            // Thêm thành viên cho đội B
            if (!empty($teamBPlayers)) {
                foreach ($teamBPlayers as $playerId) {
                    \DB::table('team_players')->insert([
                        'team_id' => $teamB->id,
                        'user_id' => $playerId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                \Log::info('MatchController::updateTeams - Added players to Team B:', ['players' => $teamBPlayers]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành viên thành công',
                'data' => [
                    'teamA' => [
                        'id' => $teamA->id,
                        'name' => $teamA->name,
                        'players' => \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->join('user_clubs', function($join) use ($clubId) {
                                $join->on('users.id', '=', 'user_clubs.user_id')
                                     ->where('user_clubs.club_id', '=', $clubId)
                                     ->where('user_clubs.is_active', '=', true)
                                     ->where('user_clubs.status', '=', 'active');
                            })
                            ->where('team_players.team_id', $teamA->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.zalo_avatar')
                            ->distinct()
                            ->get()
                    ],
                    'teamB' => [
                        'id' => $teamB->id,
                        'name' => $teamB->name,
                        'players' => \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->join('user_clubs', function($join) use ($clubId) {
                                $join->on('users.id', '=', 'user_clubs.user_id')
                                     ->where('user_clubs.club_id', '=', $clubId)
                                     ->where('user_clubs.is_active', '=', true)
                                     ->where('user_clubs.status', '=', 'active');
                            })
                            ->where('team_players.team_id', $teamB->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.zalo_avatar')
                            ->distinct()
                            ->get()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bắt đầu trận đấu
     */
    public function startMatch(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            if ($match->status !== 'upcoming') {
                return response()->json([
                    'success' => false,
                    'message' => 'Trận đấu không thể bắt đầu'
                ], 400);
            }

            $match->update(['status' => 'ongoing']);

            return response()->json([
                'success' => true,
                'message' => 'Trận đấu đã bắt đầu'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy trận đấu (chuyển về trạng thái upcoming)
     */
    public function cancelMatch(Request $request, $id): JsonResponse
    {
        try {
            // Log request data để debug
            \Log::info('MatchController::cancelMatch - Request data:', [
                'match_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            
            $validator = Validator::make($request->all(), [
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            // Kiểm tra trạng thái match
            if ($match->status !== 'ongoing') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy trận đấu đang diễn ra'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Cập nhật trạng thái match thành upcoming
                $match->update(['status' => 'upcoming']);

                \Log::info('MatchController::cancelMatch - Match cancelled successfully:', [
                    'match_id' => $match->id,
                    'old_status' => 'ongoing',
                    'new_status' => 'upcoming'
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Trận đấu đã được hủy thành công'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('MatchController::cancelMatch - Exception occurred:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật kết quả trận đấu
     */
    public function updateResult(Request $request, $id): JsonResponse
    {
        try {
            // Log request data để debug
            \Log::info('MatchController::updateResult - Request data:', [
                'match_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            
            $validator = Validator::make($request->all(), [
                'teamAScore' => 'required|integer|min:0',
                'teamBScore' => 'required|integer|min:0',
                'winner' => 'required|in:teamA,teamB',
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Không cho phép điểm số bằng nhau (phải có đội thắng)
            if ($request->teamAScore === $request->teamBScore) {
                return response()->json([
                    'success' => false,
                    'message' => 'Điểm số không được bằng nhau. Phải có đội thắng cuộc.'
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            // Kiểm tra trạng thái match
            if (!in_array($match->status, ['ongoing', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể cập nhật kết quả cho trận đấu đang diễn ra hoặc đã hoàn thành'
                ], 422);
            }

            DB::beginTransaction();

            // Cập nhật kết quả
            // Cập nhật trạng thái match thành completed (chỉ khi đang từ ongoing)
            if ($match->status === 'ongoing') {
                $match->update(['status' => 'completed']);
            }

            // Lấy hoặc tạo teams sử dụng helper method
            [$teamA, $teamB] = $this->getOrCreateTeams($match->id);
            
            \Log::info('MatchController::updateResult - Teams ready for update:', [
                'match_id' => $match->id,
                'teamA' => ['id' => $teamA->id, 'name' => $teamA->name],
                'teamB' => ['id' => $teamB->id, 'name' => $teamB->name]
            ]);
            
            // Cập nhật điểm số cho Team A
            \Log::info('MatchController::updateResult - Updating Team A:', [
                'team_id' => $teamA->id,
                'old_score' => $teamA->score,
                'new_score' => $request->teamAScore,
                'old_is_winner' => $teamA->is_winner,
                'new_is_winner' => $request->winner === 'teamA'
            ]);
            
            $updateResultA = $teamA->update([
                'score' => $request->teamAScore,
                'is_winner' => $request->winner === 'teamA'
            ]);
            
            \Log::info('MatchController::updateResult - Team A update result:', ['success' => $updateResultA]);

            // Cập nhật điểm số cho Team B
            \Log::info('MatchController::updateResult - Updating Team B:', [
                'team_id' => $teamB->id,
                'old_score' => $teamB->score,
                'old_is_winner' => $teamB->is_winner,
                'new_score' => $request->teamBScore,
                'new_is_winner' => $request->winner === 'teamB'
            ]);
            
            $updateResultB = $teamB->update([
                'score' => $request->teamBScore,
                'is_winner' => $request->winner === 'teamB'
            ]);
            
            \Log::info('MatchController::updateResult - Team B update result:', ['success' => $updateResultB]);

            // Log để debug
            \Log::info('MatchController::updateResult - Updated teams:', [
                'match_id' => $match->id,
                'teamA_score' => $request->teamAScore,
                'teamB_score' => $request->teamBScore,
                'winner' => $request->winner,
                'teamA_is_winner' => $request->winner === 'teamA',
                'teamB_is_winner' => $request->winner === 'teamB'
            ]);

            // Tạo giao dịch quỹ cho đội thua và lấy số lượng giao dịch đã tạo
            $transactionsCount = $this->createFundTransactionsForLosers($match, $request->winner, $teamA, $teamB);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật kết quả thành công và đã tạo ' . $transactionsCount . ' giao dịch quỹ thu từ đội thua (chờ nộp)'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo giao dịch quỹ thu từ đội thua (trạng thái pending - chưa nộp)
     */
    private function createFundTransactionsForLosers($match, $winner, $teamA, $teamB): int
    {
        try {
            // Xác định đội thua
            $loserTeam = ($winner === 'teamA') ? $teamB : $teamA;
            $winnerTeam = ($winner === 'teamA') ? $teamA : $teamB;
            
            \Log::info('MatchController::createFundTransactionsForLosers - Creating income transactions from losers:', [
                'match_id' => $match->id,
                'winner_team' => $winnerTeam->name,
                'loser_team' => $loserTeam->name,
                'bet_amount' => $match->bet_amount
            ]);

            // Lấy danh sách cầu thủ của đội thua (chỉ lấy active members)
            $loserPlayers = \DB::table('team_players')
                ->join('users', 'team_players.user_id', '=', 'users.id')
                ->join('user_clubs', function($join) use ($match) {
                    $join->on('users.id', '=', 'user_clubs.user_id')
                         ->where('user_clubs.club_id', '=', $match->club_id)
                         ->where('user_clubs.is_active', '=', true)
                         ->where('user_clubs.status', '=', 'active');
                })
                ->where('team_players.team_id', $loserTeam->id)
                ->select('users.id', 'users.name')
                ->distinct()
                ->get();

            \Log::info('MatchController::createFundTransactionsForLosers - Loser team players (will pay fund):', [
                'team_id' => $loserTeam->id,
                'players_count' => $loserPlayers->count(),
                'players' => $loserPlayers->toArray()
            ]);

            if ($loserPlayers->count() === 0) {
                \Log::warning('MatchController::createFundTransactionsForLosers - No players found in loser team');
                return 0;
            }

            // Tính số tiền mỗi người phải nộp (chia đều)
            $amountPerPlayer = $match->bet_amount / $loserPlayers->count();
            
            \Log::info('MatchController::createFundTransactionsForLosers - Fund amount calculation:', [
                'total_bet_amount' => $match->bet_amount,
                'players_count' => $loserPlayers->count(),
                'amount_per_player' => $amountPerPlayer
            ]);

            // Tạo giao dịch quỹ thu từ từng cầu thủ của đội thua
            foreach ($loserPlayers as $player) {
                $transactionData = [
                    'club_id' => $match->club_id,
                    'user_id' => $player->id,
                    'type' => 'income', // Loại giao dịch: thu (nộp quỹ)
                    'amount' => $amountPerPlayer,
                    'description' => "Thu quỹ trận đấu: {$match->title} - Đội {$loserTeam->name} thua",
                    'transaction_date' => now()->format('Y-m-d'),
                    'status' => 'pending', // Trạng thái: chưa nộp
                    'match_id' => $match->id,
                    'notes' => "Trận đấu ID: {$match->id}, Đội thua: {$loserTeam->name}, Cầu thủ: {$player->name} - Chờ nộp quỹ",
                    'created_by' => $player->id, // Sử dụng user_id của cầu thủ
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                \Log::info('MatchController::createFundTransactionsForLosers - Creating income transaction for player:', [
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                    'transaction_data' => $transactionData
                ]);

                // Tạo giao dịch quỹ
                $transactionId = \DB::table('fund_transactions')->insertGetId($transactionData);
                
                \Log::info('MatchController::createFundTransactionsForLosers - Income transaction created successfully:', [
                    'transaction_id' => $transactionId,
                    'player_id' => $player->id,
                    'amount' => $amountPerPlayer
                ]);
            }

            \Log::info('MatchController::createFundTransactionsForLosers - All transactions created successfully', [
                'match_id' => $match->id,
                'match_title' => $match->title,
                'loser_team' => $loserTeam->name,
                'total_transactions' => $loserPlayers->count(),
                'total_amount' => $match->bet_amount,
                'amount_per_player' => $amountPerPlayer
            ]);

            // Trả về số lượng giao dịch đã tạo
            return $loserPlayers->count();

        } catch (\Exception $e) {
            \Log::error('MatchController::createFundTransactionsForLosers - Exception occurred:', [
                'match_id' => $match->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Không throw exception để không ảnh hưởng đến việc cập nhật kết quả
            // Chỉ log lỗi và trả về 0
            return 0;
        }
    }

    /**
     * Xóa trận đấu
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Gom dữ liệu input + query để validator không bỏ sót club_id trên DELETE
            $payload = $request->all();
            if (!$payload && $request->query('club_id')) {
                $payload['club_id'] = $request->query('club_id');
            }

            $validator = Validator::make($payload, [
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, query, header hoặc từ zalo_gid
            $clubId = $request->input('club_id')
                ?? $request->query('club_id')
                ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            DB::beginTransaction();

            // Xóa team players + teams để đảm bảo không còn dữ liệu mồ côi
            $teamIds = Team::where('match_id', $match->id)->pluck('id');
            if ($teamIds->isNotEmpty()) {
                DB::table('team_players')->whereIn('team_id', $teamIds)->delete();
                Team::whereIn('id', $teamIds)->delete();
            }

            // Xóa giao dịch quỹ liên quan đến trận đấu nếu bảng có cột match_id
            if (Schema::hasColumn('fund_transactions', 'match_id')) {
                $transactionIds = FundTransaction::where('match_id', $match->id)->pluck('id');
                if ($transactionIds->isNotEmpty()) {
                    FundTransactionPaymentProof::whereIn('fund_transaction_id', $transactionIds)->delete();
                    FundTransaction::whereIn('id', $transactionIds)->delete();
                }
            }

            $deleted = $match->delete();

            if (!$deleted) {
                throw new \RuntimeException('Không thể xóa trận đấu. Vui lòng thử lại.');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa trận đấu thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết trận đấu
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                        }
                    }
                }
            }
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            $match = GameMatch::with(['teams.players', 'creator'])
                ->where('club_id', $clubId)
                ->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            // Lấy danh sách active member IDs để filter players
            $activeMemberIds = \App\Models\UserClub::where('club_id', $clubId)
                ->where('is_active', true)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();

            $matchData = [
                'id' => $match->id,
                'title' => $match->title,
                'date' => $match->match_date->format('Y-m-d'),
                'time' => $match->time,
                'location' => $match->location,
                'description' => $match->description,
                'status' => $match->status,
                'betAmount' => (float) $match->bet_amount,
                'createdBy' => $match->creator->name ?? 'Unknown',
                'createdAt' => $match->created_at->format('Y-m-d'),
                'teamA' => [
                    'id' => $match->teams->where('name', 'like', '%A%')->first()?->id,
                    'name' => 'Đội A',
                    'players' => $match->teams->where('name', 'like', '%A%')->first()?->players
                        ->filter(function ($player) use ($activeMemberIds) {
                            return in_array($player->id, $activeMemberIds);
                        })
                        ->map(function ($player) {
                            return [
                                'id' => $player->id,
                                'name' => $player->name,
                                'avatar' => $player->avatar,
                                'phone' => $player->phone,
                                'zalo_avatar' => $player->zalo_avatar ?? null
                            ];
                        })
                        ->values() ?? [],
                    'score' => $match->teams->where('name', 'like', '%A%')->first()?->score,
                    'isWinner' => $match->teams->where('name', 'like', '%A%')->first()?->is_winner
                ],
                'teamB' => [
                    'id' => $match->teams->where('name', 'like', '%B%')->first()?->id,
                    'name' => 'Đội B',
                    'players' => $match->teams->where('name', 'like', '%B%')->first()?->players
                        ->filter(function ($player) use ($activeMemberIds) {
                            return in_array($player->id, $activeMemberIds);
                        })
                        ->map(function ($player) {
                            return [
                                'id' => $player->id,
                                'name' => $player->name,
                                'avatar' => $player->avatar,
                                'phone' => $player->phone,
                                'zalo_avatar' => $player->zalo_avatar ?? null
                            ];
                        })
                        ->values() ?? [],
                    'score' => $match->teams->where('name', 'like', '%B%')->first()?->score,
                    'isWinner' => $match->teams->where('name', 'like', '%B%')->first()?->is_winner
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $matchData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách thành viên của club để chọn cho đội
     */
    public function getClubMembers(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Lấy club_id từ request, header hoặc từ zalo_gid
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                $zaloGid = $request->header('X-Zalo-GID') ?? $request->input('zalo_gid');
                
                if ($zaloGid) {
                    $user = \App\Models\User::where('zalo_gid', $zaloGid)->first();
                    if ($user) {
                        // Tìm club đầu tiên của user
                        $userClub = \App\Models\UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                            \Log::info('MatchController::getClubMembers - UserClub found:', ['club_id' => $clubId]);
                        } else {
                            \Log::warning('MatchController::getClubMembers - No active UserClub found for user:', ['user_id' => $user->id]);
                        }
                    } else {
                        \Log::warning('MatchController::getClubMembers - No user found for zalo_gid:', ['zalo_gid' => $zaloGid]);
                    }
                }
            }
            
            if (!$clubId) {
                \Log::error('MatchController::getClubMembers - No club_id determined');
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xác định club. Vui lòng chọn club trước.'
                ], 400);
            }

            // Chỉ lấy các user đã active (is_active = true và status = 'active')
            // Sử dụng whereHas('userClubs') thay vì whereHas('clubs') để filter đúng trên bảng user_clubs
            $users = User::whereHas('userClubs', function($query) use ($clubId) {
                    $query->where('club_id', $clubId)
                          ->where('is_active', true)
                          ->where('status', 'active');
                })
                ->select('id', 'name', 'avatar', 'phone', 'zalo_avatar')
                ->get()
                ->map(function ($user) use ($clubId) {
                    // Lấy user_club relationship để có thông tin role
                    $userClub = \App\Models\UserClub::where('user_id', $user->id)
                        ->where('club_id', $clubId)
                        ->where('is_active', true)
                        ->where('status', 'active')
                        ->first();
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar,
                        'zalo_avatar' => $user->zalo_avatar,
                        'phone' => $user->phone,
                        'role' => $userClub ? $userClub->role : 'member'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
