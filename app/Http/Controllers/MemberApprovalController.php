<?php

namespace App\Http\Controllers;

use App\Models\UserClub;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MemberApprovalController extends Controller
{
    /**
     * Lấy danh sách thành viên chờ duyệt
     */
    public function getPendingMembers(Request $request): JsonResponse
    {
        try {
            $clubId = $request->input('club_id');
            $zaloGid = $request->input('zalo_gid');

            if (!$clubId || !$zaloGid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin club_id hoặc zalo_gid'
                ], 400);
            }

            // Kiểm tra user có phải admin của club không
            $adminMembership = UserClub::where('club_id', $clubId)
                ->whereHas('user', function ($query) use ($zaloGid) {
                    $query->where('zalo_gid', $zaloGid);
                })
                ->where('role', 'admin')
                ->where('status', 'active')
                ->first();

            if (!$adminMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền admin để duyệt thành viên'
                ], 403);
            }

            // Lấy danh sách thành viên chờ duyệt
            $pendingMembers = UserClub::where('club_id', $clubId)
                ->where('status', 'pending')
                ->with(['user', 'club'])
                ->orderBy('joined_date', 'asc')
                ->get();

            $formattedMembers = $pendingMembers->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'user_name' => $membership->user->name ?? 'Unknown',
                    'user_phone' => $membership->user->phone ?? 'N/A',
                    'user_avatar' => $membership->user->avatar ?? null,
                    'role' => $membership->role,
                    'joined_date' => $membership->joined_date,
                    'notes' => $membership->notes,
                    'club_name' => $membership->club->name ?? 'Unknown Club'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedMembers,
                'total' => $formattedMembers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting pending members:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách thành viên chờ duyệt'
            ], 500);
        }
    }

    /**
     * Admin duyệt thành viên
     */
    public function approveMember(Request $request): JsonResponse
    {
        try {
            $membershipId = $request->input('membership_id');
            $zaloGid = $request->input('zalo_gid');
            $notes = $request->input('notes');

            if (!$membershipId || !$zaloGid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin membership_id hoặc zalo_gid'
                ], 400);
            }

            // Lấy thông tin membership
            $membership = UserClub::with(['user', 'club'])->find($membershipId);
            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy yêu cầu tham gia'
                ], 404);
            }

            // Kiểm tra user có phải admin của club không
            $adminMembership = UserClub::where('club_id', $membership->club_id)
                ->whereHas('user', function ($query) use ($zaloGid) {
                    $query->where('zalo_gid', $zaloGid);
                })
                ->where('role', 'admin')
                ->where('status', 'active')
                ->first();

            if (!$adminMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền admin để duyệt thành viên'
                ], 403);
            }

            // Kiểm tra membership có đang pending không
            if ($membership->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu này không còn ở trạng thái chờ duyệt'
                ], 400);
            }

            // Thực hiện duyệt
            DB::beginTransaction();
            try {
                $membership->approve($adminMembership->user_id, $notes);
                $membership->activate(); // Kích hoạt thành viên

                DB::commit();

                Log::info('Member approved successfully:', [
                    'membership_id' => $membershipId,
                    'admin_id' => $adminMembership->user_id,
                    'user_id' => $membership->user_id,
                    'club_id' => $membership->club_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Đã duyệt thành viên thành công!',
                    'data' => [
                        'membership_id' => $membership->id,
                        'user_name' => $membership->user->name,
                        'club_name' => $membership->club->name,
                        'status' => 'approved',
                        'approved_at' => $membership->approved_at
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error approving member:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi duyệt thành viên'
            ], 500);
        }
    }

    /**
     * Admin từ chối thành viên
     */
    public function rejectMember(Request $request): JsonResponse
    {
        try {
            $membershipId = $request->input('membership_id');
            $zaloGid = $request->input('zalo_gid');
            $rejectionReason = $request->input('rejection_reason');

            if (!$membershipId || !$zaloGid || !$rejectionReason) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin membership_id, zalo_gid hoặc rejection_reason'
                ], 400);
            }

            // Lấy thông tin membership
            $membership = UserClub::with(['user', 'club'])->find($membershipId);
            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy yêu cầu tham gia'
                ], 404);
            }

            // Kiểm tra user có phải admin của club không
            $adminMembership = UserClub::where('club_id', $membership->club_id)
                ->whereHas('user', function ($query) use ($zaloGid) {
                    $query->where('zalo_gid', $zaloGid);
                })
                ->where('role', 'admin')
                ->where('status', 'active')
                ->first();

            if (!$adminMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền admin để từ chối thành viên'
                ], 403);
            }

            // Kiểm tra membership có đang pending không
            if ($membership->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu này không còn ở trạng thái chờ duyệt'
                ], 400);
            }

            // Thực hiện từ chối
            DB::beginTransaction();
            try {
                $membership->reject($adminMembership->user_id, $rejectionReason);

                DB::commit();

                Log::info('Member rejected successfully:', [
                    'membership_id' => $membershipId,
                    'admin_id' => $adminMembership->user_id,
                    'user_id' => $membership->user_id,
                    'club_id' => $membership->club_id,
                    'reason' => $rejectionReason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Đã từ chối thành viên thành công!',
                    'data' => [
                        'membership_id' => $membership->id,
                        'user_name' => $membership->user->name,
                        'club_name' => $membership->club->name,
                        'status' => 'rejected',
                        'rejection_reason' => $rejectionReason
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error rejecting member:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi từ chối thành viên'
            ], 500);
        }
    }

    /**
     * Lấy thống kê membership theo trạng thái
     */
    public function getMembershipStats(Request $request): JsonResponse
    {
        try {
            $clubId = $request->input('club_id');
            $zaloGid = $request->input('zalo_gid');

            if (!$clubId || !$zaloGid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin club_id hoặc zalo_gid'
                ], 400);
            }

            // Kiểm tra user có phải admin của club không
            $adminMembership = UserClub::where('club_id', $clubId)
                ->whereHas('user', function ($query) use ($zaloGid) {
                    $query->where('zalo_gid', $zaloGid);
                })
                ->where('role', 'admin')
                ->where('status', 'active')
                ->first();

            if (!$adminMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền admin để xem thống kê'
                ], 403);
            }

            // Lấy thống kê theo trạng thái
            $stats = UserClub::where('club_id', $clubId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $formattedStats = [
                'pending' => $stats->get('pending')->count ?? 0,
                'approved' => $stats->get('approved')->count ?? 0,
                'rejected' => $stats->get('rejected')->count ?? 0,
                'active' => $stats->get('active')->count ?? 0,
                'inactive' => $stats->get('inactive')->count ?? 0,
                'total' => UserClub::where('club_id', $clubId)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedStats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting membership stats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thống kê thành viên'
            ], 500);
        }
    }
}
