<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Start trial period for a club
     */
    public function startTrial(Club $club): bool
    {
        try {
            // Kiểm tra xem club đã từng dùng thử chưa
            if ($club->trial_expired_at && $club->trial_expired_at->isPast()) {
                throw new \Exception('Club đã hết hạn dùng thử');
            }

            // Bắt đầu dùng thử 1 tháng
            $club->update([
                'subscription_status' => 'trial',
                'trial_expired_at' => now()->addMonth(),
                'plan_id' => null,
                'subscription_expired_at' => null,
                'last_payment_at' => null
            ]);

            Log::info("Club {$club->id} đã bắt đầu dùng thử", [
                'club_id' => $club->id,
                'trial_expired_at' => $club->trial_expired_at
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Lỗi khi bắt đầu dùng thử cho club {$club->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate subscription for a club
     */
    public function activateSubscription(Club $club, int $planId, ?int $durationDays = null): bool
    {
        try {
            $plan = Plan::find($planId);
            if (!$plan) {
                throw new \Exception('Plan không tồn tại');
            }

            if (!$plan->isActive()) {
                throw new \Exception('Plan không còn hoạt động');
            }

            $duration = $durationDays ?? $plan->duration_days;
            
            $club->update([
                'subscription_status' => 'active',
                'plan_id' => $planId,
                'subscription_expired_at' => now()->addDays($duration),
                'trial_expired_at' => null,
                'last_payment_at' => now()
            ]);

            Log::info("Club {$club->id} đã kích hoạt gói {$plan->name}", [
                'club_id' => $club->id,
                'plan_id' => $planId,
                'subscription_expired_at' => $club->subscription_expired_at
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Lỗi khi kích hoạt gói cho club {$club->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel subscription for a club
     */
    public function cancelSubscription(Club $club): bool
    {
        try {
            $club->update([
                'subscription_status' => 'canceled'
            ]);

            Log::info("Club {$club->id} đã hủy gói", [
                'club_id' => $club->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Lỗi khi hủy gói cho club {$club->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and update subscription status
     */
    public function checkAndUpdateStatus(Club $club): void
    {
        try {
            $now = now();
            $statusChanged = false;

            // Kiểm tra trial
            if ($club->subscription_status === 'trial' && 
                $club->trial_expired_at && 
                $club->trial_expired_at->isPast()) {
                
                $club->update(['subscription_status' => 'expired']);
                $statusChanged = true;
                
                Log::info("Club {$club->id} đã hết hạn dùng thử");
            }

            // Kiểm tra subscription
            if ($club->subscription_status === 'active' && 
                $club->subscription_expired_at && 
                $club->subscription_expired_at->isPast()) {
                
                $club->update(['subscription_status' => 'expired']);
                $statusChanged = true;
                
                Log::info("Club {$club->id} đã hết hạn gói");
            }

            if ($statusChanged) {
                Log::info("Trạng thái subscription của club {$club->id} đã được cập nhật");
            }
        } catch (\Exception $e) {
            Log::error("Lỗi khi kiểm tra trạng thái subscription cho club {$club->id}: " . $e->getMessage());
        }
    }

    /**
     * Get available plans for club
     */
    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::where('is_active', true)->orderBy('price')->get();
    }

    /**
     * Get club subscription info
     */
    public function getClubSubscriptionInfo(Club $club): array
    {
        $this->checkAndUpdateStatus($club);

        $info = [
            'status' => $club->subscription_status,
            'can_access_premium' => $club->canAccessPremiumFeatures(),
            'trial_info' => null,
            'subscription_info' => null
        ];

        if ($club->isInTrial()) {
            $info['trial_info'] = [
                'expires_at' => $club->trial_expired_at,
                'days_remaining' => $club->trial_expired_at->diffInDays(now()),
                'is_expired' => false
            ];
        }

        if ($club->hasActiveSubscription()) {
            $info['subscription_info'] = [
                'plan' => $club->plan ? $club->plan->only(['id', 'name', 'price', 'billing_cycle']) : null,
                'expires_at' => $club->subscription_expired_at,
                'days_remaining' => $club->subscription_expired_at->diffInDays(now()),
                'last_payment' => $club->last_payment_at,
                'is_expired' => false
            ];
        }

        return $info;
    }

    /**
     * Check if club can perform action (based on subscription)
     */
    public function canPerformAction(Club $club, string $action): bool
    {
        $this->checkAndUpdateStatus($club);

        // Các action cơ bản luôn được phép
        $basicActions = ['view_members', 'view_events', 'view_finances'];
        
        if (in_array($action, $basicActions)) {
            return true;
        }

        // Các action premium cần subscription
        $premiumActions = [
            'create_event', 'edit_event', 'delete_event',
            'manage_members', 'advanced_finances', 'zalo_integration',
            'custom_reports', 'api_access'
        ];

        if (in_array($action, $premiumActions)) {
            return $club->canAccessPremiumFeatures();
        }

        return true;
    }
}
