<?php

/**
 * Test file để kiểm tra tính năng Subscription System
 * Chạy file này để test các chức năng cơ bản
 */

require_once 'vendor/autoload.php';

use App\Models\Club;
use App\Models\Plan;
use App\Services\SubscriptionService;

// Khởi tạo Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST SUBSCRIPTION SYSTEM ===\n\n";

try {
    // Test 1: Kiểm tra bảng plans
    echo "1. Kiểm tra bảng plans:\n";
    $plans = Plan::all();
    if ($plans->count() > 0) {
        echo "   ✓ Có {$plans->count()} gói trong database\n";
        foreach ($plans as $plan) {
            echo "   - {$plan->name}: {$plan->price} VNĐ ({$plan->billing_cycle})\n";
        }
    } else {
        echo "   ✗ Không có gói nào trong database\n";
        echo "   Chạy: php artisan db:seed --class=PlanSeeder\n";
    }
    echo "\n";

    // Test 2: Kiểm tra bảng clubs có trường subscription
    echo "2. Kiểm tra bảng clubs:\n";
    $clubs = Club::all();
    if ($clubs->count() > 0) {
        echo "   ✓ Có {$clubs->count()} club trong database\n";
        
        $club = $clubs->first();
        echo "   - Club đầu tiên: {$club->name}\n";
        echo "   - Subscription status: {$club->subscription_status}\n";
        echo "   - Trial expired at: " . ($club->trial_expired_at ? $club->trial_expired_at->format('Y-m-d H:i:s') : 'null') . "\n";
        echo "   - Subscription expired at: " . ($club->subscription_expired_at ? $club->subscription_expired_at->format('Y-m-d H:i:s') : 'null') . "\n";
        echo "   - Plan ID: " . ($club->plan_id ?? 'null') . "\n";
    } else {
        echo "   ✗ Không có club nào trong database\n";
    }
    echo "\n";

    // Test 3: Test SubscriptionService
    echo "3. Test SubscriptionService:\n";
    if ($clubs->count() > 0) {
        $club = $clubs->first();
        $subscriptionService = new SubscriptionService();
        
        echo "   - Club: {$club->name}\n";
        
        // Kiểm tra trạng thái hiện tại
        $subscriptionInfo = $subscriptionService->getClubSubscriptionInfo($club);
        echo "   - Status: {$subscriptionInfo['status']}\n";
        echo "   - Can access premium: " . ($subscriptionInfo['can_access_premium'] ? 'Yes' : 'No') . "\n";
        
        if ($subscriptionInfo['trial_info']) {
            echo "   - Trial expires: " . $subscriptionInfo['trial_info']['expires_at']->format('Y-m-d H:i:s') . "\n";
            echo "   - Days remaining: {$subscriptionInfo['trial_info']['days_remaining']}\n";
        }
        
        if ($subscriptionInfo['subscription_info']) {
            echo "   - Plan: {$subscriptionInfo['subscription_info']['plan']['name']}\n";
            echo "   - Expires: " . $subscriptionInfo['subscription_info']['expires_at']->format('Y-m-d H:i:s') . "\n";
        }
        
        // Test các method khác
        echo "   - Is in trial: " . ($club->isInTrial() ? 'Yes' : 'No') . "\n";
        echo "   - Has active subscription: " . ($club->hasActiveSubscription() ? 'Yes' : 'No') . "\n";
        echo "   - Can access premium features: " . ($club->canAccessPremiumFeatures() ? 'Yes' : 'No') . "\n";
        
    } else {
        echo "   ✗ Không thể test vì không có club\n";
    }
    echo "\n";

    // Test 4: Test các action permissions
    echo "4. Test Action Permissions:\n";
    if ($clubs->count() > 0) {
        $club = $clubs->first();
        $subscriptionService = new SubscriptionService();
        
        $actions = [
            'view_members',
            'create_event',
            'manage_members',
            'zalo_integration'
        ];
        
        foreach ($actions as $action) {
            $canPerform = $subscriptionService->canPerformAction($club, $action);
            echo "   - {$action}: " . ($canPerform ? '✓ Allowed' : '✗ Denied') . "\n";
        }
    }
    echo "\n";

    // Test 5: Test bắt đầu trial
    echo "5. Test bắt đầu trial:\n";
    if ($clubs->count() > 0) {
        $club = $clubs->first();
        $subscriptionService = new SubscriptionService();
        
        // Chỉ test nếu club chưa có trial
        if (!$club->trial_expired_at || $club->trial_expired_at->isPast()) {
            echo "   - Club {$club->name} có thể bắt đầu trial\n";
            echo "   - Chạy: php artisan tinker\n";
            echo "   - \$club = App\\Models\\Club::find({$club->id});\n";
            echo "   - \$service = new App\\Services\\SubscriptionService();\n";
            echo "   - \$service->startTrial(\$club);\n";
        } else {
            echo "   - Club {$club->name} đang trong trial period\n";
            echo "   - Trial expires: " . $club->trial_expired_at->format('Y-m-d H:i:s') . "\n";
        }
    }
    echo "\n";

    echo "=== TEST COMPLETED ===\n";
    echo "\nĐể test đầy đủ, hãy:\n";
    echo "1. Chạy migration: php artisan migrate\n";
    echo "2. Chạy seeder: php artisan db:seed --class=PlanSeeder\n";
    echo "3. Test API endpoints:\n";
    echo "   - GET /api/subscription/plans\n";
    echo "   - GET /api/subscription/club/{clubId}\n";
    echo "   - POST /api/subscription/club/{clubId}/trial\n";
    echo "4. Chạy command: php artisan subscription:update-status\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
