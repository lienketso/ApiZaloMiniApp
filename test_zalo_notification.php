<?php

/**
 * Test script để kiểm tra chức năng gửi thông báo Zalo OA (MIỄN PHÍ)
 * 
 * Dựa trên hướng dẫn chính thức: https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new
 * 
 * Cách sử dụng:
 * 1. Đảm bảo đã cấu hình ZALO_OA_ACCESS_TOKEN trong .env
 * 2. Chạy: php test_zalo_notification.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ZaloNotificationService;

echo "🧪 Testing Zalo OA Notification Service (MIỄN PHÍ)\n";
echo "================================================\n";
echo "📚 Dựa trên: https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new\n\n";

// Test 1: Kiểm tra cấu hình
echo "1. Kiểm tra cấu hình Zalo OA (MIỄN PHÍ):\n";
$accessToken = config('services.zalo.oa_access_token');
$appId = config('services.zalo.app_id');
$oaId = config('services.zalo.oa_id');

if (!$accessToken) {
    echo "❌ ZALO_OA_ACCESS_TOKEN chưa được cấu hình\n";
    echo "💡 Chỉ cần ZALO_OA_ACCESS_TOKEN là đủ để gửi broadcast miễn phí!\n";
    exit(1);
}

echo "✅ ZALO_OA_ACCESS_TOKEN: " . substr($accessToken, 0, 10) . "...\n";
if ($appId) {
    echo "✅ ZALO_APP_ID: $appId\n";
} else {
    echo "⚠️  ZALO_APP_ID: Chưa cấu hình (không bắt buộc cho broadcast)\n";
}
if ($oaId) {
    echo "✅ ZALO_OA_ID: $oaId\n";
} else {
    echo "⚠️  ZALO_OA_ID: Chưa cấu hình (không bắt buộc cho broadcast)\n";
}
echo "\n";

// Test 2: Tạo service instance
echo "2. Tạo ZaloNotificationService instance:\n";
try {
    $notificationService = new ZaloNotificationService();
    echo "✅ Service created successfully\n\n";
} catch (Exception $e) {
    echo "❌ Error creating service: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test gửi broadcast miễn phí
echo "3. Test gửi broadcast miễn phí:\n";
echo "Bạn có muốn test gửi broadcast đến tất cả người follow OA? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
    echo "Gửi broadcast message...\n";
    
    $message = "🧪 Test thông báo điểm danh từ hệ thống!\n\nĐây là tin nhắn test để kiểm tra chức năng gửi thông báo miễn phí.";
    $result = $notificationService->sendBroadcastMessage($message, $appId ?? 'test_app', $oaId ?? 'test_oa');
    
    if ($result['success']) {
        echo "✅ Gửi broadcast thành công!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Gửi broadcast thất bại!\n";
        echo "Error: " . $result['message'] . "\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "⏭️  Skipped broadcast test\n";
}

// Test 4: Test gửi thông báo đến Zalo ID cụ thể (MIỄN PHÍ)
echo "\n4. Test gửi thông báo đến Zalo ID cụ thể (MIỄN PHÍ):\n";
$testZaloId = '5170627724267093288';
echo "Zalo ID test: $testZaloId\n";
echo "Bạn có muốn test gửi tin nhắn đến Zalo ID này? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
    echo "Gửi thông báo đến Zalo ID: $testZaloId\n";
    
    // Test gửi tin nhắn text đơn giản (MIỄN PHÍ)
    $message = "🧪 Test thông báo từ Zalo OA!\n\nĐây là tin nhắn test để kiểm tra chức năng gửi thông báo miễn phí đến Zalo ID cụ thể.\n\nThời gian: " . date('Y-m-d H:i:s');
    
    $result = $notificationService->sendCheckinNotification($testZaloId, $appId ?? 'test_app', $oaId ?? 'test_oa');
    
    if ($result['success']) {
        echo "✅ Gửi thông báo đến Zalo ID thành công!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Gửi thông báo đến Zalo ID thất bại!\n";
        echo "Error: " . $result['message'] . "\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Thông tin debug
        echo "\n🔍 Debug info:\n";
        echo "- Zalo ID: $testZaloId\n";
        echo "- App ID: " . ($appId ?? 'NOT SET') . "\n";
        echo "- OA ID: " . ($oaId ?? 'NOT SET') . "\n";
        echo "- Access Token: " . (substr($accessToken, 0, 10) . '...' ?? 'NOT SET') . "\n";
    }
} else {
    echo "⏭️  Skipped Zalo ID test\n";
}

// Test 5: Test gửi thông báo cá nhân (có phí) - Legacy
echo "\n5. Test gửi thông báo cá nhân (có phí) - Legacy:\n";
echo "Nhập zalo_gid khác để test gửi cá nhân (hoặc nhấn Enter để skip): ";
$zaloGid = trim(fgets(STDIN));

if ($zaloGid) {
    echo "Gửi thông báo cá nhân đến zalo_gid: $zaloGid\n";
    
    $result = $notificationService->sendCheckinNotification($zaloGid, $appId ?? 'test_app', $oaId ?? 'test_oa');
    
    if ($result['success']) {
        echo "✅ Gửi thông báo cá nhân thành công!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Gửi thông báo cá nhân thất bại!\n";
        echo "Error: " . $result['message'] . "\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "⏭️  Skipped personal notification test\n";
}

echo "\n6. Test API endpoint:\n";
echo "Bạn có thể test API endpoint bằng cách:\n\n";

echo "🎯 Test gửi thông báo đến Zalo ID cụ thể (MIỄN PHÍ):\n";
echo "curl -X POST http://localhost/club/public/api/notifications/test \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"zalo_gid\": \"5170627724267093288\"}'\n\n";

echo "🚀 Test gửi thông báo tự động (khuyến nghị):\n";
echo "curl -X POST http://localhost/club/public/api/notifications/send-attendance \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"club_id\": 1, \"zalo_gid\": \"5170627724267093288\", \"method\": \"auto\"}'\n\n";

echo "👤 Test gửi thông báo cá nhân (có phí):\n";
echo "curl -X POST http://localhost/club/public/api/notifications/send-attendance \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"club_id\": 1, \"zalo_gid\": \"5170627724267093288\", \"method\": \"personal\"}'\n\n";

echo "📢 Test gửi broadcast miễn phí:\n";
echo "curl -X POST http://localhost/club/public/api/notifications/send-attendance \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"club_id\": 1, \"zalo_gid\": \"5170627724267093288\", \"method\": \"broadcast\"}'\n\n";

echo "👥 Test gửi thông báo cá nhân (legacy):\n";
echo "curl -X POST http://localhost/club/public/api/notifications/send-attendance-members \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"club_id\": 1, \"zalo_gid\": \"5170627724267093288\"}'\n\n";

echo "🔗 Hướng dẫn lấy Zalo OA Access Token:\n";
echo "1. Truy cập: https://business.zalo.me/\n";
echo "2. Đăng nhập và chọn Official Account\n";
echo "3. Vào Cài đặt → Tích hợp → Lấy Access Token\n";
echo "4. Cập nhật ZALO_OA_ACCESS_TOKEN trong file .env\n\n";

echo "📚 Tài liệu tham khảo:\n";
echo "https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new\n\n";

echo "🎉 Test completed!\n";
echo "💡 Lưu ý: Sử dụng broadcast miễn phí thay vì gửi cá nhân để tiết kiệm chi phí!\n";
echo "🎯 Zalo ID test: 5170627724267093288\n";
