<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use App\Models\UserClub;
use App\Models\Invitation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClubMembershipService
{
    /**
     * Kiểm tra và map user vào club bằng số điện thoại
     * 
     * @param string $phone Số điện thoại của user
     * @param string $zaloGid Zalo GID của user
     * @param int $clubId ID của club
     * @return array Kết quả xử lý
     */
    public function mapUserToClub(string $phone, string $zaloGid, int $clubId): array
    {
        try {
            Log::info('ClubMembershipService::mapUserToClub - Starting mapping process:', [
                'phone' => $phone,
                'zalo_gid' => $zaloGid,
                'club_id' => $clubId
            ]);

            // 1. Kiểm tra club có tồn tại không
            $club = Club::find($clubId);
            if (!$club) {
                Log::warning('Club not found:', ['club_id' => $clubId]);
                return [
                    'success' => false,
                    'message' => 'Câu lạc bộ không tồn tại',
                    'code' => 'CLUB_NOT_FOUND'
                ];
            }

            // 2. Tìm user theo zalo_gid
            $user = User::where('zalo_gid', $zaloGid)->first();
            if (!$user) {
                Log::warning('User not found with zalo_gid:', ['zalo_gid' => $zaloGid]);
                return [
                    'success' => false,
                    'message' => 'Người dùng chưa được đăng ký',
                    'code' => 'USER_NOT_FOUND'
                ];
            }

            // 3. Kiểm tra xem user đã là thành viên của club này chưa
            $existingMembership = UserClub::where('user_id', $user->id)
                ->where('club_id', $clubId)
                ->first();

            if ($existingMembership) {
                Log::info('User already a member of this club:', [
                    'user_id' => $user->id,
                    'club_id' => $clubId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Bạn đã là thành viên của câu lạc bộ này',
                    'code' => 'ALREADY_MEMBER',
                    'data' => [
                        'user_id' => $user->id,
                        'club_id' => $clubId,
                        'role' => $existingMembership->role,
                        'joined_date' => $existingMembership->joined_date
                    ]
                ];
            }

            // 4. Kiểm tra xem có invitation cho số điện thoại này không
            $invitation = Invitation::where('club_id', $clubId)
                ->where('phone', $phone)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($invitation) {
                Log::info('Found valid invitation for user:', [
                    'invitation_id' => $invitation->id,
                    'user_id' => $user->id,
                    'club_id' => $clubId
                ]);

                // Thực hiện transaction để đảm bảo tính nhất quán
                DB::beginTransaction();
                try {
                    // Cập nhật thông tin user nếu cần
                    if (empty($user->phone) || $user->phone !== $phone) {
                        $user->update(['phone' => $phone]);
                        Log::info('Updated user phone number:', [
                            'user_id' => $user->id,
                            'old_phone' => $user->phone,
                            'new_phone' => $phone
                        ]);
                    }

                    // Thêm user vào club
                    $userClub = UserClub::create([
                        'user_id' => $user->id,
                        'club_id' => $clubId,
                        'role' => 'member',
                        'joined_date' => now(),
                        'is_active' => true,
                        'notes' => 'Auto-joined via invitation by phone number'
                    ]);

                    // Đánh dấu invitation đã được chấp nhận
                    $invitation->markAsAccepted();

                    DB::commit();

                    Log::info('User successfully mapped to club via invitation:', [
                        'user_id' => $user->id,
                        'club_id' => $clubId,
                        'user_club_id' => $userClub->id,
                        'invitation_id' => $invitation->id
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Chào mừng bạn tham gia câu lạc bộ!',
                        'code' => 'JOINED_VIA_INVITATION',
                        'data' => [
                            'user_id' => $user->id,
                            'club_id' => $clubId,
                            'club_name' => $club->name,
                            'role' => 'member',
                            'joined_date' => $userClub->joined_date,
                            'invitation_id' => $invitation->id
                        ]
                    ];

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error during invitation processing:', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }

            } else {
                // Không có invitation - thông báo cần được mời
                Log::info('No valid invitation found for user:', [
                    'phone' => $phone,
                    'club_id' => $clubId
                ]);

                return [
                    'success' => false,
                    'message' => 'Bạn cần được mời để tham gia câu lạc bộ này. Vui lòng liên hệ admin.',
                    'code' => 'NO_INVITATION',
                    'data' => [
                        'club_name' => $club->name,
                        'phone' => $phone
                    ]
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error in ClubMembershipService::mapUserToClub:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone' => $phone,
                'zalo_gid' => $zaloGid,
                'club_id' => $clubId
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý yêu cầu tham gia câu lạc bộ',
                'code' => 'INTERNAL_ERROR',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kiểm tra trạng thái membership của user trong club
     */
    public function checkMembershipStatus(string $zaloGid, int $clubId): array
    {
        try {
            $user = User::where('zalo_gid', $zaloGid)->first();
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Người dùng không tồn tại',
                    'code' => 'USER_NOT_FOUND'
                ];
            }

            $membership = UserClub::where('user_id', $user->id)
                ->where('club_id', $clubId)
                ->first();

            if (!$membership) {
                return [
                    'success' => false,
                    'message' => 'Bạn chưa là thành viên của câu lạc bộ này',
                    'code' => 'NOT_MEMBER'
                ];
            }

            return [
                'success' => true,
                'message' => 'Bạn là thành viên của câu lạc bộ này',
                'code' => 'IS_MEMBER',
                'data' => [
                    'user_id' => $user->id,
                    'club_id' => $clubId,
                    'role' => $membership->role,
                    'joined_date' => $membership->joined_date,
                    'is_active' => $membership->is_active
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error checking membership status:', [
                'error' => $e->getMessage(),
                'zalo_gid' => $zaloGid,
                'club_id' => $clubId
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái thành viên',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Lấy danh sách club mà user có thể tham gia (có invitation)
     */
    public function getAvailableClubs(string $phone): array
    {
        try {
            $invitations = Invitation::where('phone', $phone)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with('club')
                ->get();

            $availableClubs = $invitations->map(function ($invitation) {
                return [
                    'club_id' => $invitation->club_id,
                    'club_name' => $invitation->club->name ?? 'Unknown Club',
                    'invitation_id' => $invitation->id,
                    'expires_at' => $invitation->expires_at,
                    'invited_by' => $invitation->inviter->name ?? 'Unknown'
                ];
            });

            return [
                'success' => true,
                'data' => $availableClubs,
                'total' => $availableClubs->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error getting available clubs:', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách câu lạc bộ có thể tham gia',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }
}
