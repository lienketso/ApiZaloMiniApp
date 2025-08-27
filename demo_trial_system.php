<?php

/**
 * Demo chức năng dùng thử (Trial System)
 * Chạy file này để test các tính năng cơ bản
 */

require_once 'vendor/autoload.php';

// Khởi tạo Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Club;
use App\Models\Plan;
use App\Services\SubscriptionService;

echo "=== DEMO CHỨC NĂNG DÙNG THỬ ===\n\n";

try {
    $subscriptionService = new SubscriptionService();
    
    // 1. Hiển thị danh sách gói
    echo "1. DANH SÁCH CÁC GÓI:\n";
    $plans = $subscriptionService->getAvailablePlans();
    foreach ($plans as $plan) {
        echo "   - {$plan->name}: " . number_format($plan->price, 0, ',', '.') . " VNĐ ({$plan->billing_cycle})\n";
        echo "     Mô tả: {$plan->description}\n";
        echo "     Tính năng: " . implode(', ', $plan->features) . "\n\n";
    }
    
    // 2. Hiển thị trạng thái các clubs
    echo "2. TRẠNG THÁI CÁC CLUBS:\n";
    $clubs = Club::all();
    foreach ($clubs as $club) {
        $subscriptionInfo = $subscriptionService->getClubSubscriptionInfo($club);
        echo "   - {$club->name}:\n";
        echo "     Status: {$subscriptionInfo['status']}\n";
        echo "     Can access premium: " . ($subscriptionInfo['can_access_premium'] ? 'YES' : 'NO') . "\n";
        
        if ($subscriptionInfo['trial_info']) {
            echo "     Trial expires: " . $subscriptionInfo['trial_info']['expires_at']->format('Y-m-d H:i:s') . "\n";
            echo "     Days remaining: {$subscriptionInfo['trial_info']['days_remaining']}\n";
        }
        
        if ($subscriptionInfo['subscription_info']) {
            echo "     Plan: {$subscriptionInfo['subscription_info']['plan']['name']}\n";
            echo "     Expires: " . $subscriptionInfo['subscription_info']['expires_at']->format('Y-m-d H:i:s') . "\n";
        }
        echo "\n";
    }
    
    // 3. Demo bắt đầu dùng thử
    echo "3. DEMO BẮT ĐẦU DÙNG THỬ:\n";
    $clubToTest = Club::where('subscription_status', 'trial')
                      ->whereNull('trial_expired_at')
                      ->first();
    
    if ($clubToTest) {
        echo "   - Club được chọn: {$clubToTest->name}\n";
        echo "   - Trạng thái trước: {$clubToTest->subscription_status}\n";
        echo "   - Bắt đầu dùng thử...\n";
        
        $result = $subscriptionService->startTrial($clubToTest);
        if ($result) {
            $clubToTest->refresh();
            echo "   - ✓ Dùng thử thành công!\n";
            echo "   - Trạng thái sau: {$clubToTest->subscription_status}\n";
            echo "   - Hết hạn: " . $clubToTest->trial_expired_at->format('Y-m-d H:i:s') . "\n";
            echo "   - Có thể truy cập premium: " . ($clubToTest->canAccessPremiumFeatures() ? 'YES' : 'NO') . "\n";
        } else {
            echo "   - ✗ Không thể bắt đầu dùng thử\n";
        }
    } else {
        echo "   - Không có club nào phù hợp để test dùng thử\n";
    }
    echo "\n";
    
    // 4. Demo kích hoạt gói
    echo "4. DEMO KÍCH HOẠT GÓI:\n";
    $clubToUpgrade = Club::where('subscription_status', 'trial')
                         ->whereNotNull('trial_expired_at')
                         ->first();
    
    if ($clubToUpgrade) {
        $basicPlan = Plan::where('name', 'Basic')->first();
        echo "   - Club được chọn: {$clubToUpgrade->name}\n";
        echo "   - Gói được chọn: {$basicPlan->name} - " . number_format($basicPlan->price, 0, ',', '.') . " VNĐ\n";
        echo "   - Kích hoạt gói...\n";
        
        $result = $subscriptionService->activateSubscription($clubToUpgrade, $basicPlan->id);
        if ($result) {
            $clubToUpgrade->refresh();
            echo "   - ✓ Kích hoạt gói thành công!\n";
            echo "   - Trạng thái mới: {$clubToUpgrade->subscription_status}\n";
            echo "   - Gói hiện tại: {$clubToUpgrade->plan->name}\n";
            echo "   - Hết hạn: " . $clubToUpgrade->subscription_expired_at->format('Y-m-d H:i:s') . "\n";
        } else {
            echo "   - ✗ Không thể kích hoạt gói\n";
        }
    } else {
        echo "   - Không có club nào phù hợp để test kích hoạt gói\n";
    }
    echo "\n";
    
    // 5. Demo kiểm tra quyền
    echo "5. DEMO KIỂM TRA QUYỀN:\n";
    $testClub = Club::first();
    if ($testClub) {
        echo "   - Club test: {$testClub->name}\n";
        echo "   - Trạng thái: {$testClub->subscription_status}\n";
        
        $actions = [
            'view_members',
            'create_event', 
            'manage_members',
            'zalo_integration',
            'custom_reports'
        ];
        
        foreach ($actions as $action) {
            $canPerform = $subscriptionService->canPerformAction($testClub, $action);
            echo "   - Action '{$action}': " . ($canPerform ? '✓ ALLOWED' : '✗ DENIED') . "\n";
        }
    }
    echo "\n";
    
    // 6. Demo hủy gói
    echo "6. DEMO HỦY GÓI:\n";
    $clubToCancel = Club::where('subscription_status', 'active')->first();
    if ($clubToCancel) {
        echo "   - Club được chọn: {$clubToCancel->name}\n";
        echo "   - Trạng thái trước: {$clubToCancel->subscription_status}\n";
        echo "   - Hủy gói...\n";
        
        $result = $subscriptionService->cancelSubscription($clubToCancel);
        if ($result) {
            $clubToCancel->refresh();
            echo "   - ✓ Hủy gói thành công!\n";
            echo "   - Trạng thái mới: {$clubToCancel->subscription_status}\n";
            echo "   - Có thể truy cập premium: " . ($clubToCancel->canAccessPremiumFeatures() ? 'YES' : 'NO') . "\n";
        } else {
            echo "   - ✗ Không thể hủy gói\n";
        }
    } else {
        echo "   - Không có club nào đang active để test hủy gói\n";
    }
    echo "\n";
    
    echo "=== DEMO HOÀN THÀNH ===\n";
    echo "\nĐể test API endpoints, hãy:\n";
    echo "1. Đăng nhập vào hệ thống\n";
    echo "2. Test các endpoints:\n";
    echo "   - GET https://api.lienketso.vn/public/api/subscription/plans\n";
    echo "   - POST https://api.lienketso.vn/public/api/subscription/club/{clubId}/trial\n";
    echo "   - POST https://api.lienketso.vn/public/api/subscription/club/{clubId}/activate\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
