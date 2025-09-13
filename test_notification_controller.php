<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\NotificationController;
use App\Services\ZaloNotificationService;

echo "🧪 Test NotificationController\n";
echo "==============================\n\n";

// Tạo service và controller
$zaloService = new ZaloNotificationService();
$controller = new NotificationController($zaloService);

// Test 1: Kiểm tra trạng thái token
echo "1️⃣ Kiểm tra trạng thái token...\n";
$request = new \Illuminate\Http\Request();
$response = $controller->checkTokenStatus($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Token status: " . $data['message'] . "\n";
    if (isset($data['data'])) {
        echo "   - Has access token: " . ($data['data']['has_access_token'] ? 'Yes' : 'No') . "\n";
        echo "   - Has refresh token: " . ($data['data']['has_refresh_token'] ? 'Yes' : 'No') . "\n";
        echo "   - Last refreshed: " . ($data['data']['last_refreshed_at'] ?? 'Never') . "\n";
    }
} else {
    echo "❌ Token status: " . $data['message'] . "\n";
}

echo "\n";

// Test 2: Test gửi tin nhắn
echo "2️⃣ Test gửi tin nhắn...\n";
$testUserId = "5170627724267093288";
$request = new \Illuminate\Http\Request();
$request->merge(['zalo_gid' => $testUserId]);
$response = $controller->testNotification($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Test message sent successfully!\n";
    echo "   Response: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Test message failed: " . $data['message'] . "\n";
    if (isset($data['data'])) {
        echo "   Error details: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Test 3: Test broadcast message
echo "3️⃣ Test broadcast message...\n";
$request = new \Illuminate\Http\Request();
$request->merge([
    'club_id' => 1,
    'zalo_gid' => $testUserId,
    'method' => 'broadcast'
]);
$response = $controller->sendAttendanceNotification($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Broadcast message sent successfully!\n";
    echo "   Method: " . $data['data']['method'] . "\n";
    echo "   Club: " . $data['data']['club_name'] . "\n";
    echo "   Total members: " . $data['data']['total_members'] . "\n";
} else {
    echo "❌ Broadcast message failed: " . $data['message'] . "\n";
    if (isset($data['data'])) {
        echo "   Error details: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Test 4: Test personal message
echo "4️⃣ Test personal message...\n";
$request = new \Illuminate\Http\Request();
$request->merge([
    'club_id' => 1,
    'zalo_gid' => $testUserId,
    'method' => 'personal'
]);
$response = $controller->sendAttendanceNotification($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Personal message sent successfully!\n";
    echo "   Method: " . $data['data']['method'] . "\n";
    echo "   Club: " . $data['data']['club_name'] . "\n";
    echo "   Success count: " . $data['data']['success_count'] . "\n";
    echo "   Fail count: " . $data['data']['fail_count'] . "\n";
} else {
    echo "❌ Personal message failed: " . $data['message'] . "\n";
    if (isset($data['data'])) {
        echo "   Error details: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Test 5: Test auto method
echo "5️⃣ Test auto method...\n";
$request = new \Illuminate\Http\Request();
$request->merge([
    'club_id' => 1,
    'zalo_gid' => $testUserId,
    'method' => 'auto'
]);
$response = $controller->sendAttendanceNotification($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ Auto method completed successfully!\n";
    echo "   Method used: " . $data['data']['method'] . "\n";
    echo "   Club: " . $data['data']['club_name'] . "\n";
    echo "   Total members: " . $data['data']['total_members'] . "\n";
} else {
    echo "❌ Auto method failed: " . $data['message'] . "\n";
    if (isset($data['data'])) {
        echo "   Error details: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n🎉 Test completed!\n";
