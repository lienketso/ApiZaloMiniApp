<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ZaloNotificationService;
use App\Models\ZaloToken;

echo "🔄 Test Auto Refresh Token\n";
echo "==========================\n\n";

// Tạo service
$zaloService = new ZaloNotificationService();

// Test 1: Kiểm tra trạng thái token hiện tại
echo "1️⃣ Kiểm tra trạng thái token hiện tại...\n";
$token = ZaloToken::first();
if ($token) {
    echo "✅ Token found in database:\n";
    echo "   - Access token: " . (empty($token->access_token) ? 'Empty' : substr($token->access_token, 0, 20) . '...') . "\n";
    echo "   - Refresh token: " . (empty($token->refresh_token) ? 'Empty' : substr($token->refresh_token, 0, 20) . '...') . "\n";
    echo "   - Expires in: " . ($token->expires_in ?? 'Unknown') . " seconds\n";
    echo "   - Last refreshed: " . ($token->last_refreshed_at ?? 'Never') . "\n";
    
    if ($token->expires_in && $token->last_refreshed_at) {
        $secondsSinceRefresh = now()->diffInSeconds($token->last_refreshed_at);
        $isExpired = $secondsSinceRefresh > ($token->expires_in - 60);
        echo "   - Seconds since refresh: " . $secondsSinceRefresh . "\n";
        echo "   - Is expired (with 60s buffer): " . ($isExpired ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "❌ No token found in database\n";
}

echo "\n";

// Test 2: Test gửi tin nhắn (sẽ tự động kiểm tra và refresh token nếu cần)
echo "2️⃣ Test gửi tin nhắn (tự động kiểm tra token)...\n";
$testUserId = "5170627724267093288";
$result = $zaloService->testMessage($testUserId);

if ($result['success']) {
    echo "✅ Test message sent successfully!\n";
    echo "   Message: " . $result['message'] . "\n";
    if (isset($result['data'])) {
        echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Test message failed: " . $result['message'] . "\n";
    if (isset($result['data'])) {
        echo "   Error details: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Test 3: Test broadcast message
echo "3️⃣ Test broadcast message...\n";
$broadcastResult = $zaloService->sendBroadcastMessage("🧪 Test broadcast message từ Laravel với auto refresh token!");

if ($broadcastResult['success']) {
    echo "✅ Broadcast message sent successfully!\n";
    echo "   Message: " . $broadcastResult['message'] . "\n";
    if (isset($broadcastResult['data'])) {
        echo "   Response: " . json_encode($broadcastResult['data'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Broadcast message failed: " . $broadcastResult['message'] . "\n";
    if (isset($broadcastResult['data'])) {
        echo "   Error details: " . json_encode($broadcastResult['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Test 4: Kiểm tra trạng thái token sau khi gửi
echo "4️⃣ Kiểm tra trạng thái token sau khi gửi...\n";
$tokenAfter = ZaloToken::first();
if ($tokenAfter) {
    echo "✅ Token after sending:\n";
    echo "   - Access token: " . (empty($tokenAfter->access_token) ? 'Empty' : substr($tokenAfter->access_token, 0, 20) . '...') . "\n";
    echo "   - Last refreshed: " . ($tokenAfter->last_refreshed_at ?? 'Never') . "\n";
    
    if ($token && $tokenAfter) {
        $accessTokenChanged = $token->access_token !== $tokenAfter->access_token;
        $lastRefreshedChanged = $token->last_refreshed_at != $tokenAfter->last_refreshed_at;
        
        if ($accessTokenChanged || $lastRefreshedChanged) {
            echo "   - 🔄 Token was refreshed during the process!\n";
        } else {
            echo "   - ✅ Token was still valid, no refresh needed\n";
        }
    }
} else {
    echo "❌ No token found after sending\n";
}

echo "\n";

// Test 5: Test checkTokenStatus method
echo "5️⃣ Test checkTokenStatus method...\n";
$statusResult = $zaloService->checkTokenStatus();

if ($statusResult['success']) {
    echo "✅ Token status check successful!\n";
    echo "   Message: " . $statusResult['message'] . "\n";
    if (isset($statusResult['token_info'])) {
        echo "   Token info: " . json_encode($statusResult['token_info'], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Token status check failed: " . $statusResult['message'] . "\n";
}

echo "\n🎉 Auto refresh token test completed!\n";
