<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Club;
use App\Models\User;
use App\Models\UserClub;
use App\Services\ZaloNotificationService;

class NotificationController extends Controller
{
    protected $zaloNotificationService;

    public function __construct(ZaloNotificationService $zaloNotificationService)
    {
        $this->zaloNotificationService = $zaloNotificationService;
    }

    /**
     * Gửi thông báo điểm danh cho thành viên trong câu lạc bộ
     * Sử dụng kết hợp broadcast miễn phí và gửi cá nhân hóa
     */
    public function sendAttendanceNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'club_id' => 'required|integer|exists:clubs,id',
                'zalo_gid' => 'required|string',
                'method' => 'nullable|string|in:broadcast,personal,auto'
            ]);

            $clubId = $validated['club_id'];
            $zaloGid = $validated['zalo_gid'];
            $method = $validated['method'] ?? 'auto';

            // Lấy thông tin club
            $club = Club::find($clubId);
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            // Lấy thông tin Zalo OA từ config
            $zaloAppId = env('ZALO_APP_ID');
            $zaloOaId = env('ZALO_OA_ID');

            if (!$zaloAppId || !$zaloOaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu hình Zalo OA chưa đầy đủ'
                ], 500);
            }

            // Lấy danh sách thành viên của club
            $members = User::whereHas('clubs', function($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })->whereNotNull('zalo_gid')->get();

            $totalMembers = $members->count();

            // Quyết định phương pháp gửi
            if ($method === 'auto') {
                // Tự động chọn phương pháp dựa trên số lượng thành viên
                if ($totalMembers <= 10) {
                    $method = 'personal'; // Ít thành viên: gửi cá nhân hóa
                } else {
                    $method = 'broadcast'; // Nhiều thành viên: dùng broadcast
                }
            }

            if ($method === 'broadcast') {
                // Sử dụng Tin Truyền thông OA - gửi broadcast miễn phí
                $message = "📢 Thông báo điểm danh từ câu lạc bộ {$club->name}!\n\nCó sự kiện điểm danh mới, hãy vào ứng dụng để tham gia!";
                
                $result = $this->zaloNotificationService->sendBroadcastMessage(
                    $message,
                    $zaloAppId,
                    $zaloOaId
                );

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => "Đã gửi thông báo broadcast đến tất cả người follow OA (có {$totalMembers} thành viên trong club)",
                        'data' => [
                            'method' => 'broadcast',
                            'club_name' => $club->name,
                            'total_members' => $totalMembers,
                            'zalo_response' => $result['data']
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gửi thông báo broadcast thất bại: ' . $result['message']
                    ], 500);
                }
            } else {
                // Gửi cá nhân hóa cho từng thành viên trong club
                $successCount = 0;
                $failCount = 0;
                $errors = [];

                foreach ($members as $member) {
                    try {
                        $result = $this->zaloNotificationService->sendCheckinNotification(
                            $member->zalo_gid,
                            $zaloAppId,
                            $zaloOaId
                        );

                        if ($result && isset($result['error']) && $result['error'] == 0) {
                            $successCount++;
                        } else {
                            $failCount++;
                            $errors[] = "Gửi thông báo cho {$member->name} thất bại: " . 
                                       ($result['message'] ?? 'Lỗi không xác định');
                        }
                    } catch (\Exception $e) {
                        $failCount++;
                        $errors[] = "Lỗi khi gửi thông báo cho {$member->name}: " . $e->getMessage();
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "Đã gửi thông báo cho {$successCount} thành viên trong câu lạc bộ",
                    'data' => [
                        'method' => 'personal',
                        'club_name' => $club->name,
                        'total_members' => $totalMembers,
                        'success_count' => $successCount,
                        'fail_count' => $failCount,
                        'errors' => $errors
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi thông báo điểm danh cho từng thành viên cụ thể (có phí)
     * Chỉ sử dụng khi cần gửi cá nhân hóa
     */
    public function sendAttendanceNotificationToMembers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'club_id' => 'required|integer|exists:clubs,id',
                'zalo_gid' => 'required|string'
            ]);

            $clubId = $validated['club_id'];
            $zaloGid = $validated['zalo_gid'];

            // Lấy thông tin club
            $club = Club::find($clubId);
            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            // Lấy tất cả thành viên của club
            $members = User::whereHas('clubs', function($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })->get();

            if ($members->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có thành viên nào trong câu lạc bộ'
                ], 400);
            }

            // Lấy thông tin Zalo OA từ config
            $zaloAppId = env('ZALO_APP_ID');
            $zaloOaId = env('ZALO_OA_ID');

            if (!$zaloAppId || !$zaloOaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu hình Zalo OA chưa đầy đủ'
                ], 500);
            }

            $successCount = 0;
            $failCount = 0;
            $errors = [];

            // Gửi thông báo cho từng thành viên
            foreach ($members as $member) {
                try {
                    // Kiểm tra xem member có zalo_gid không
                    if (!$member->zalo_gid) {
                        $failCount++;
                        $errors[] = "Thành viên {$member->name} không có zalo_gid";
                        continue;
                    }

                    // Gửi thông báo
                    $result = $this->zaloNotificationService->sendCheckinNotification(
                        $member->zalo_gid,
                        $zaloAppId,
                        $zaloOaId
                    );

                    if ($result && isset($result['error']) && $result['error'] == 0) {
                        $successCount++;
                    } else {
                        $failCount++;
                        $errors[] = "Gửi thông báo cho {$member->name} thất bại: " . 
                                   ($result['message'] ?? 'Lỗi không xác định');
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "Lỗi khi gửi thông báo cho {$member->name}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã gửi thông báo cho {$successCount} thành viên",
                'data' => [
                    'total_members' => $members->count(),
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test gửi thông báo cho một thành viên cụ thể
     */
    public function testNotification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'zalo_gid' => 'required|string'
            ]);

            $zaloGid = $validated['zalo_gid'];
            $zaloAppId = env('ZALO_APP_ID');
            $zaloOaId = env('ZALO_OA_ID');

            if (!$zaloAppId || !$zaloOaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu hình Zalo OA chưa đầy đủ'
                ], 500);
            }

            $result = $this->zaloNotificationService->sendCheckinNotification(
                $zaloGid,
                $zaloAppId,
                $zaloOaId
            );

            return response()->json([
                'success' => true,
                'message' => 'Test gửi thông báo thành công',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
