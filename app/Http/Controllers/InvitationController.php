<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Club;
use App\Models\User;
use App\Models\UserClub;
use App\Services\ZaloNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Admin tạo lời mời thành viên
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'club_id' => 'required|exists:clubs,id',
                'phone' => 'required|string|max:20',
                'zalo_gid' => 'required|string', // Để xác định admin
            ]);

            $clubId = $request->club_id;
            $phone = $request->phone;
            $zaloGid = $request->zalo_gid;

            // Kiểm tra user có phải là admin của club không
            $admin = User::where('zalo_gid', $zaloGid)->first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            // Kiểm tra quyền admin
            $userClub = UserClub::where('user_id', $admin->id)
                ->where('club_id', $clubId)
                ->where('role', 'admin')
                ->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền mời thành viên vào club này'
                ], 403);
            }

            // Kiểm tra xem đã có lời mời pending cho số điện thoại này chưa
            $existingInvitation = Invitation::where('club_id', $clubId)
                ->where('phone', $phone)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingInvitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đã có lời mời đang chờ xử lý cho số điện thoại này'
                ], 400);
            }

            // Tạo lời mời mới
            $invitation = Invitation::create([
                'club_id' => $clubId,
                'phone' => $phone,
                'invited_by' => $admin->id,
            ]);

            // Tạo link mời
            $inviteLink = config('app.url', 'https://your-app.com') . '/invite/' . $invitation->invite_token;

            // Tạm thời không gửi ZNS - chỉ tạo record trong DB
            $club = Club::find($clubId);
            Log::info('Invitation created without ZNS (temporary):', [
                'invitation_id' => $invitation->id,
                'phone' => $phone,
                'club_name' => $club->name ?? 'Unknown Club',
                'note' => 'ZNS temporarily disabled - user will be mapped when accessing Mini App'
            ]);

            Log::info('Invitation created:', [
                'invitation_id' => $invitation->id,
                'club_id' => $clubId,
                'phone' => $phone,
                'invited_by' => $admin->id,
                'invite_link' => $inviteLink,
                'zns_result' => $znsResult
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo lời mời thành công',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'phone' => $phone,
                    'invite_token' => $invitation->invite_token,
                    'invite_link' => $inviteLink,
                    'expires_at' => $invitation->expires_at,
                    'status' => $invitation->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating invitation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo lời mời: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực và xử lý lời mời
     */
    public function accept(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'invite_token' => 'required|string',
                'zalo_gid' => 'required|string',
            ]);

            $inviteToken = $request->invite_token;
            $zaloGid = $request->zalo_gid;

            // Tìm lời mời
            $invitation = Invitation::where('invite_token', $inviteToken)->first();
            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lời mời không hợp lệ'
                ], 404);
            }

            // Kiểm tra trạng thái lời mời
            if (!$invitation->canBeUsed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lời mời đã hết hạn hoặc không thể sử dụng'
                ], 400);
            }

            // Tìm hoặc tạo user
            $user = User::where('zalo_gid', $zaloGid)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập trước khi chấp nhận lời mời'
                ], 401);
            }

            // Kiểm tra xem user đã là thành viên của club này chưa
            $existingMembership = UserClub::where('user_id', $user->id)
                ->where('club_id', $invitation->club_id)
                ->first();

            if ($existingMembership) {
                // Nếu đã là thành viên, chỉ đánh dấu lời mời là accepted
                $invitation->markAsAccepted();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Bạn đã là thành viên của club này',
                    'data' => [
                        'club_id' => $invitation->club_id,
                        'club_name' => $invitation->club->name ?? 'Unknown Club'
                    ]
                ]);
            }

            // Thực hiện transaction để đảm bảo tính nhất quán
            DB::beginTransaction();
            try {
                // Cập nhật thông tin user nếu cần
                if (empty($user->phone) && $invitation->phone) {
                    $user->update(['phone' => $invitation->phone]);
                }

                // Thêm user vào club
                $userClub = UserClub::create([
                    'user_id' => $user->id,
                    'club_id' => $invitation->club_id,
                    'role' => 'member',
                    'joined_date' => now(),
                    'is_active' => true,
                ]);

                // Đánh dấu lời mời đã được chấp nhận
                $invitation->markAsAccepted();

                DB::commit();

                Log::info('Invitation accepted successfully:', [
                    'invitation_id' => $invitation->id,
                    'user_id' => $user->id,
                    'club_id' => $invitation->club_id,
                    'user_club_id' => $userClub->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Chào mừng bạn tham gia club!',
                    'data' => [
                        'club_id' => $invitation->club_id,
                        'club_name' => $invitation->club->name ?? 'Unknown Club',
                        'user_role' => 'member',
                        'joined_date' => $userClub->joined_date
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error accepting invitation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý lời mời: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách lời mời của club
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'club_id' => 'required|exists:clubs,id',
                'zalo_gid' => 'required|string',
            ]);

            $clubId = $request->club_id;
            $zaloGid = $request->zalo_gid;

            // Kiểm tra quyền admin
            $admin = User::where('zalo_gid', $zaloGid)->first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $userClub = UserClub::where('user_id', $admin->id)
                ->where('club_id', $clubId)
                ->where('role', 'admin')
                ->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem danh sách lời mời'
                ], 403);
            }

            // Lấy danh sách lời mời
            $invitations = Invitation::where('club_id', $clubId)
                ->with(['inviter:id,name', 'club:id,name'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($invitation) {
                    return [
                        'id' => $invitation->id,
                        'phone' => $invitation->phone,
                        'status' => $invitation->status,
                        'invite_token' => $invitation->invite_token,
                        'expires_at' => $invitation->expires_at,
                        'created_at' => $invitation->created_at,
                        'invited_by' => $invitation->inviter->name ?? 'Unknown',
                        'club_name' => $invitation->club->name ?? 'Unknown Club',
                        'can_be_used' => $invitation->canBeUsed(),
                        'is_expired' => $invitation->isExpired()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $invitations,
                'total' => $invitations->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting invitations:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách lời mời: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy lời mời
     */
    public function destroy($id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'zalo_gid' => 'required|string',
            ]);

            $zaloGid = $request->zalo_gid;

            // Tìm lời mời
            $invitation = Invitation::with('club')->findOrFail($id);

            // Kiểm tra quyền admin
            $admin = User::where('zalo_gid', $zaloGid)->first();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $userClub = UserClub::where('user_id', $admin->id)
                ->where('club_id', $invitation->club_id)
                ->where('role', 'admin')
                ->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền hủy lời mời này'
                ], 403);
            }

            // Hủy lời mời
            $invitation->delete();

            Log::info('Invitation cancelled:', [
                'invitation_id' => $id,
                'cancelled_by' => $admin->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy lời mời thành công'
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling invitation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy lời mời: ' . $e->getMessage()
            ], 500);
        }
    }
}
