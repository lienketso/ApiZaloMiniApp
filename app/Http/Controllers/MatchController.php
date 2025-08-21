<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\User;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

            $matches = GameMatch::with(['teams', 'creator'])
                ->where('club_id', $clubId)
                ->orderBy('match_date', 'desc')
                ->get()
                ->map(function ($match) {
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
                    
                    // Lấy players cho Team A
                    $teamAPlayers = [];
                    if ($teamA) {
                        $teamAPlayers = \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->where('team_players.team_id', $teamA->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.phone')
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
                    
                    // Lấy players cho Team B
                    $teamBPlayers = [];
                    if ($teamB) {
                        $teamBPlayers = \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->where('team_players.team_id', $teamB->id)
                            ->select('users.id', 'users.name', 'users.avatar', 'users.phone')
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
                'data' => $matches
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

            // Kiểm tra xem match có teams chưa
            $teams = Team::where('match_id', $match->id)->get();
            if ($teams->isEmpty()) {
                // Tạo teams mặc định nếu chưa có
                Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội A',
                ]);
                Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội B',
                ]);
                
                // Lấy teams mới tạo
                $teams = Team::where('match_id', $match->id)->get();
            }

            DB::beginTransaction();

            // Xóa tất cả thành viên khỏi các đội
            foreach ($teams as $team) {
                \DB::table('team_players')->where('team_id', $team->id)->delete();
            }

            // Thêm thành viên cho đội A
            $teamA = $teams->where('name', 'like', '%A%')->first();
            \Log::info('MatchController::updateTeams - Team A found:', ['team' => $teamA ? $teamA->toArray() : null]);
            
            if ($teamA && !empty($teamAPlayers)) {
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
            $teamB = $teams->where('name', 'like', '%B%')->first();
            \Log::info('MatchController::updateTeams - Team B found:', ['team' => $teamB ? $teamB->toArray() : null]);
            
            if ($teamB && !empty($teamBPlayers)) {
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

            // Kiểm tra và tạo teams nếu chưa có
            if (!$teamA) {
                $teamA = Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội A',
                ]);
                if (!empty($teamAPlayers)) {
                    $teamA->players()->attach($teamAPlayers);
                }
            }

            if (!$teamB) {
                $teamB = Team::create([
                    'match_id' => $match->id,
                    'name' => 'Đội B',
                ]);
                if (!empty($teamBPlayers)) {
                    $teamB->players()->attach($teamBPlayers);
                }
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
                            ->where('team_players.team_id', $teamA->id)
                            ->select('users.id', 'users.name', 'users.avatar')
                            ->get()
                    ],
                    'teamB' => [
                        'id' => $teamB->id,
                        'name' => $teamB->name,
                        'players' => \DB::table('team_players')
                            ->join('users', 'team_players.user_id', '=', 'users.id')
                            ->where('team_players.team_id', $teamB->id)
                            ->select('users.id', 'users.name', 'users.avatar')
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

            DB::beginTransaction();

            // Cập nhật kết quả
            $match->update(['status' => 'completed']);

            // Cập nhật điểm số cho các đội
            $teams = Team::where('match_id', $match->id)->get();
            
            \Log::info('MatchController::updateResult - Found teams:', [
                'match_id' => $match->id,
                'total_teams' => $teams->count(),
                'teams' => $teams->map(function($t) { 
                    return ['id' => $t->id, 'name' => $t->name, 'score' => $t->score, 'is_winner' => $t->is_winner]; 
                })->toArray()
            ]);
            
            $teamA = $teams->where('name', 'like', '%A%')->first();
            if ($teamA) {
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
            } else {
                \Log::warning('MatchController::updateResult - Team A not found');
            }

            $teamB = $teams->where('name', 'like', '%B%')->first();
            if ($teamB) {
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
            } else {
                \Log::warning('MatchController::updateResult - Team B not found');
            }

            // Kiểm tra xem có cần tạo teams nếu chưa có không
            if (!$teamA || !$teamB) {
                \Log::info('MatchController::updateResult - Creating missing teams');
                
                if (!$teamA) {
                    $teamA = Team::create([
                        'match_id' => $match->id,
                        'name' => 'Đội A',
                        'score' => $request->teamAScore,
                        'is_winner' => $request->winner === 'teamA'
                    ]);
                    \Log::info('MatchController::updateResult - Created Team A:', ['id' => $teamA->id]);
                }
                
                if (!$teamB) {
                    $teamB = Team::create([
                        'match_id' => $match->id,
                        'name' => 'Đội B',
                        'score' => $request->teamBScore,
                        'is_winner' => $request->winner === 'teamB'
                    ]);
                    \Log::info('MatchController::updateResult - Created Team B:', ['id' => $teamB->id]);
                }
            }

            // Log để debug
            \Log::info('MatchController::updateResult - Updated teams:', [
                'match_id' => $match->id,
                'teamA_score' => $request->teamAScore,
                'teamB_score' => $request->teamBScore,
                'winner' => $request->winner,
                'teamA_is_winner' => $request->winner === 'teamA',
                'teamB_is_winner' => $request->winner === 'teamB'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật kết quả thành công'
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
     * Xóa trận đấu
     */
    public function destroy(Request $request, $id): JsonResponse
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

            $match->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa trận đấu thành công'
            ]);
        } catch (\Exception $e) {
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
                    'players' => $match->teams->where('name', 'like', '%A%')->first()?->players->map(function ($player) {
                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'avatar' => $player->avatar,
                            'phone' => $player->phone
                        ];
                    }) ?? [],
                    'score' => $match->teams->where('name', 'like', '%A%')->first()?->score,
                    'isWinner' => $match->teams->where('name', 'like', '%A%')->first()?->is_winner
                ],
                'teamB' => [
                    'id' => $match->teams->where('name', 'like', '%B%')->first()?->id,
                    'name' => 'Đội B',
                    'players' => $match->teams->where('name', 'like', '%B%')->first()?->players->map(function ($player) {
                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'avatar' => $player->avatar,
                            'phone' => $player->phone
                        ];
                    }) ?? [],
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

            $users = User::whereHas('clubs', function($query) use ($clubId) {
                    $query->where('club_id', $clubId);
                })
                ->select('id', 'name', 'avatar', 'phone')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar,
                        'phone' => $user->phone,
                        'role' => $user->clubs->first()->pivot->role ?? 'member'
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
