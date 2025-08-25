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
                'period' => 'nullable|string|in:all,week,month,year',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->input('club_id');
            $period = $request->input('period', 'all');

            // Tính số trận thắng của mỗi user
            $query = DB::table('matches as m')
                ->join('teams as t', function($join) {
                    $join->on('m.id', '=', 't.match_id')
                         ->where('t.is_winner', '=', true);
                })
                ->join('team_players as tp', 't.id', '=', 'tp.team_id')
                ->join('users as u', 'tp.user_id', '=', 'u.id')
                ->where('m.club_id', $clubId)
                ->where('m.status', 'completed');

            // Lọc theo thời gian dựa trên trường date của matches
            if ($period !== 'all') {
                $query = $this->applyTimeFilter($query, 'm.date', $period);
            }

            $winsLeaderboard = $query->select(
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
                'period' => 'nullable|string|in:all,week,month,year',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->input('club_id');
            $period = $request->input('period', 'all');

            // Tính tổng số tiền nộp quỹ của mỗi user
            $query = DB::table('fund_transactions as ft')
                ->join('users as u', 'ft.user_id', '=', 'u.id')
                ->where('ft.club_id', $clubId)
                ->where('ft.type', 'income')
                ->where('ft.status', 'completed');

            // Lọc theo thời gian dựa trên trường created_at của fund_transactions
            if ($period !== 'all') {
                $query = $this->applyTimeFilter($query, 'ft.created_at', $period);
            }

            $fundLeaderboard = $query->select(
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
                'period' => 'nullable|string|in:all,week,month,year',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->input('club_id');
            $period = $request->input('period', 'all');

            // Tính số lần điểm danh của mỗi user
            $query = DB::table('attendance as a')
                ->join('users as u', 'a.user_id', '=', 'u.id')
                ->join('events as e', 'a.event_id', '=', 'e.id')
                ->where('e.club_id', $clubId)
                ->where('a.status', 'present');

            // Lọc theo thời gian dựa trên trường date của events
            if ($period !== 'all') {
                $query = $this->applyTimeFilter($query, 'e.date', $period);
            }

            $attendanceLeaderboard = $query->select(
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

    /**
     * Áp dụng bộ lọc thời gian cho query
     */
    private function applyTimeFilter($query, $dateColumn, $period)
    {
        $now = now();
        
        switch ($period) {
            case 'week':
                // Tuần này (từ thứ 2 đến chủ nhật)
                $startOfWeek = $now->startOfWeek();
                $endOfWeek = $now->endOfWeek();
                return $query->whereBetween($dateColumn, [$startOfWeek, $endOfWeek]);
                
            case 'month':
                // Tháng này
                $startOfMonth = $now->startOfMonth();
                $endOfMonth = $now->endOfMonth();
                return $query->whereBetween($dateColumn, [$startOfMonth, $endOfMonth]);
                
            case 'year':
                // Năm nay
                $startOfYear = $now->startOfYear();
                $endOfYear = $now->endOfYear();
                return $query->whereBetween($dateColumn, [$startOfYear, $endOfYear]);
                
            default:
                return $query;
        }
    }
}
