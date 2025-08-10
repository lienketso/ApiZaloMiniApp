<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Member;
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
            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            $matches = GameMatch::with(['teams.players', 'creator'])
                ->where('club_id', $clubId)
                ->orderBy('match_date', 'desc')
                ->get()
                ->map(function ($match) {
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
                            'id' => $match->teams->where('name', 'like', '%A%')->first()?->id,
                            'name' => 'Đội A',
                            'players' => $match->teams->where('name', 'like', '%A%')->first()?->players->map(function ($player) {
                                return [
                                    'id' => $player->id,
                                    'name' => $player->name,
                                    'avatar' => $player->avatar,
                                    'role' => $player->role
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
                                    'role' => $player->role
                                ];
                            }) ?? [],
                            'score' => $match->teams->where('name', 'like', '%B%')->first()?->score,
                            'isWinner' => $match->teams->where('name', 'like', '%B%')->first()?->is_winner
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
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'time' => 'nullable|string',
                'location' => 'nullable|string',
                'description' => 'nullable|string',
                'betAmount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            DB::beginTransaction();

            // Tạo trận đấu
            $match = GameMatch::create([
                'club_id' => $clubId,
                'title' => $request->title,
                'match_date' => $request->date,
                'time' => $request->time,
                'location' => $request->location,
                'description' => $request->description,
                'status' => 'upcoming',
                'bet_amount' => $request->betAmount,
                'created_by' => $request->user()->id,
            ]);

            // Tạo 2 đội mặc định
            Team::create([
                'match_id' => $match->id,
                'name' => 'Đội A',
            ]);

            Team::create([
                'match_id' => $match->id,
                'name' => 'Đội B',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo trận đấu thành công',
                'data' => $match
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
     * Cập nhật thông tin trận đấu
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'date' => 'required|date',
                'time' => 'nullable|string',
                'location' => 'nullable|string',
                'description' => 'nullable|string',
                'betAmount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            $match = GameMatch::where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            $match->update([
                'title' => $request->title,
                'match_date' => $request->date,
                'time' => $request->time,
                'location' => $request->location,
                'description' => $request->description,
                'bet_amount' => $request->betAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trận đấu thành công',
                'data' => $match
            ]);
        } catch (\Exception $e) {
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
                'teamAPlayers.*' => 'exists:members,id',
                'teamBPlayers.*' => 'exists:members,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            $match = GameMatch::with('teams')->where('club_id', $clubId)->find($id);
            
            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy trận đấu'
                ], 404);
            }

            DB::beginTransaction();

            // Xóa tất cả thành viên khỏi các đội
            foreach ($match->teams as $team) {
                $team->players()->detach();
            }

            // Thêm thành viên cho đội A
            $teamA = $match->teams->where('name', 'like', '%A%')->first();
            if ($teamA && $request->teamAPlayers) {
                $teamA->players()->attach($request->teamAPlayers);
            }

            // Thêm thành viên cho đội B
            $teamB = $match->teams->where('name', 'like', '%B%')->first();
            if ($teamB && $request->teamBPlayers) {
                $teamB->players()->attach($request->teamBPlayers);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành viên thành công'
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
            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
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
            $validator = Validator::make($request->all(), [
                'teamAScore' => 'required|integer|min:0',
                'teamBScore' => 'required|integer|min:0',
                'winner' => 'required|in:teamA,teamB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->teamAScore === $request->teamBScore) {
                return response()->json([
                    'success' => false,
                    'message' => 'Điểm số không được bằng nhau'
                ], 422);
            }

            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            $match = GameMatch::with('teams')->where('club_id', $clubId)->find($id);
            
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
            $teamA = $match->teams->where('name', 'like', '%A%')->first();
            if ($teamA) {
                $teamA->update([
                    'score' => $request->teamAScore,
                    'is_winner' => $request->winner === 'teamA'
                ]);
            }

            $teamB = $match->teams->where('name', 'like', '%B%')->first();
            if ($teamB) {
                $teamB->update([
                    'score' => $request->teamBScore,
                    'is_winner' => $request->winner === 'teamB'
                ]);
            }

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
            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
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
     * Lấy danh sách thành viên của club để chọn cho đội
     */
    public function getClubMembers(Request $request): JsonResponse
    {
        try {
            $clubId = $request->user()->club_id;
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa thuộc club nào'
                ], 400);
            }

            $members = Member::where('club_id', $clubId)
                ->select('id', 'name', 'avatar', 'role')
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatar' => $member->avatar,
                        'role' => $member->role
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $members
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
