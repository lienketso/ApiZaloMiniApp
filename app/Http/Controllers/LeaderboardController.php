<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaderboardController extends Controller
{
    /**
     * Lấy bảng xếp hạng thắng nhiều nhất
     */
    public function getWinsLeaderboard(Request $request): JsonResponse
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

            $clubId = $request->input('club_id');

            // Tính số trận thắng của mỗi user
            $winsLeaderboard = DB::table('matches as m')
                ->join('teams as t', function($join) {
                    $join->on('m.id', '=', 't.match_id')
                         ->where('t.is_winner', '=', true);
                })
                ->join('team_players as tp', 't.id', '=', 'tp.team_id')
                ->join('users as u', 'tp.user_id', '=', 'u.id')
                ->where('m.club_id', $clubId)
                ->where('m.status', 'completed')
                ->select(
                    'u.id as userId',
                    'u.name as userName',
                    'u.avatar as userAvatar',
                    'u.phone as userPhone',
                    DB::raw('COUNT(*) as score')
                )
                ->groupBy('u.id', 'u.name', 'u.avatar', 'u.phone')
                ->orderBy('score', 'desc')
                ->limit(20)
                ->get();

            // Thêm rank
            $rankedLeaderboard = $winsLeaderboard->map(function($item, $index) {
                $item->rank = $index + 1;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $rankedLeaderboard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy bảng xếp hạng nộp quỹ nhiều nhất
     */
    public function getFundContributionsLeaderboard(Request $request): JsonResponse
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

            $clubId = $request->input('club_id');

            // Tính tổng số tiền nộp quỹ của mỗi user
            $fundLeaderboard = DB::table('fund_transactions as ft')
                ->join('users as u', 'ft.user_id', '=', 'u.id')
                ->where('ft.club_id', $clubId)
                ->where('ft.type', 'income')
                ->where('ft.status', 'completed')
                ->select(
                    'u.id as userId',
                    'u.name as userName',
                    'u.avatar as userAvatar',
                    'u.phone as userPhone',
                    DB::raw('SUM(ft.amount) as score')
                )
                ->groupBy('u.id', 'u.name', 'u.avatar', 'u.phone')
                ->orderBy('score', 'desc')
                ->limit(20)
                ->get();

            // Thêm rank
            $rankedLeaderboard = $fundLeaderboard->map(function($item, $index) {
                $item->rank = $index + 1;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $rankedLeaderboard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy bảng xếp hạng tham gia nhiều nhất
     */
    public function getAttendanceLeaderboard(Request $request): JsonResponse
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

            $clubId = $request->input('club_id');

            // Tính số lần điểm danh của mỗi user
            $attendanceLeaderboard = DB::table('attendance as a')
                ->join('users as u', 'a.user_id', '=', 'u.id')
                ->join('events as e', 'a.event_id', '=', 'e.id')
                ->where('e.club_id', $clubId)
                ->where('a.status', 'present')
                ->select(
                    'u.id as userId',
                    'u.name as userName',
                    'u.avatar as userAvatar',
                    'u.phone as userPhone',
                    DB::raw('COUNT(*) as score')
                )
                ->groupBy('u.id', 'u.name', 'u.avatar', 'u.phone')
                ->orderBy('score', 'desc')
                ->limit(20)
                ->get();

            // Thêm rank
            $rankedLeaderboard = $attendanceLeaderboard->map(function($item, $index) {
                $item->rank = $index + 1;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $rankedLeaderboard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
