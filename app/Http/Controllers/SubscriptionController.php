<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get available plans
     */
    public function getPlans(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAvailablePlans();
            
            return response()->json([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách gói: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get club subscription info
     */
    public function getClubSubscriptionInfo(Request $request, $clubId): JsonResponse
    {
        try {
            $club = Club::findOrFail($clubId);
            
            // Kiểm tra quyền truy cập
            if (!$this->canAccessClub($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập câu lạc bộ này'
                ], 403);
            }

            $subscriptionInfo = $this->subscriptionService->getClubSubscriptionInfo($club);
            
            return response()->json([
                'success' => true,
                'data' => $subscriptionInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start trial for club
     */
    public function startTrial(Request $request, $clubId): JsonResponse
    {
        try {
            $club = Club::findOrFail($clubId);
            
            // Kiểm tra quyền truy cập
            if (!$this->canAccessClub($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập câu lạc bộ này'
                ], 403);
            }

            // Kiểm tra xem club đã dùng thử chưa
            if ($club->trial_expired_at && $club->trial_expired_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Câu lạc bộ đã hết hạn dùng thử'
                ], 400);
            }

            $success = $this->subscriptionService->startTrial($club);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã bắt đầu dùng thử thành công',
                    'data' => $this->subscriptionService->getClubSubscriptionInfo($club)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể bắt đầu dùng thử'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu dùng thử: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate subscription for club
     */
    public function activateSubscription(Request $request, $clubId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|exists:plans,id',
                'duration_days' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $club = Club::findOrFail($clubId);
            
            // Kiểm tra quyền truy cập
            if (!$this->canAccessClub($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập câu lạc bộ này'
                ], 403);
            }

            $success = $this->subscriptionService->activateSubscription(
                $club, 
                $request->plan_id, 
                $request->duration_days
            );
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã kích hoạt gói thành công',
                    'data' => $this->subscriptionService->getClubSubscriptionInfo($club)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể kích hoạt gói'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi kích hoạt gói: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription for club
     */
    public function cancelSubscription(Request $request, $clubId): JsonResponse
    {
        try {
            $club = Club::findOrFail($clubId);
            
            // Kiểm tra quyền truy cập
            if (!$this->canAccessClub($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập câu lạc bộ này'
                ], 403);
            }

            $success = $this->subscriptionService->cancelSubscription($club);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã hủy gói thành công',
                    'data' => $this->subscriptionService->getClubSubscriptionInfo($club)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy gói'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy gói: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if club can perform action
     */
    public function checkActionPermission(Request $request, $clubId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $club = Club::findOrFail($clubId);
            
            // Kiểm tra quyền truy cập
            if (!$this->canAccessClub($club)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền truy cập câu lạc bộ này'
                ], 403);
            }

            $canPerform = $this->subscriptionService->canPerformAction($club, $request->action);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'can_perform' => $canPerform,
                    'action' => $request->action,
                    'club_id' => $clubId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi kiểm tra quyền: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can access club
     */
    private function canAccessClub(Club $club): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Người tạo club luôn có quyền truy cập
        if ($club->created_by === $user->id) {
            return true;
        }

        // Kiểm tra xem user có phải là thành viên của club không
        return $club->users()->where('user_id', $user->id)->exists();
    }
}
