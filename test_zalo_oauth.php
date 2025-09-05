<?php

require_once 'vendor/autoload.php';

use App\Services\ZaloNotificationService;

echo "🔐 Test Zalo OAuth v4 Functions\n";
echo "================================\n\n";

// Khởi tạo service
$notificationService = new ZaloNotificationService();

// Lấy thông tin từ .env
$appId = $_ENV['ZALO_APP_ID'] ?? getenv('ZALO_APP_ID');
$appSecret = $_ENV['ZALO_APP_SECRET'] ?? getenv('ZALO_APP_SECRET');
$redirectUri = 'https://your-domain.com/callback'; // Thay đổi thành domain thực tế

echo "📋 Configuration:\n";
echo "App ID: " . ($appId ?: 'NOT SET') . "\n";
echo "App Secret: " . ($appSecret ? '***SET***' : 'NOT SET') . "\n";
echo "Redirect URI: $redirectUri\n\n";

if (!$appId || !$appSecret) {
    echo "❌ Vui lòng cấu hình ZALO_APP_ID và ZALO_APP_SECRET trong file .env\n";
    exit(1);
}

// Test 1: Tạo URL xác thực
echo "1. Test tạo URL xác thực OAuth v4:\n";
echo "----------------------------------\n";

$state = uniqid('test_');
$authUrl = $notificationService->getAuthUrl($appId, $redirectUri, $state);

echo "✅ Auth URL created successfully!\n";
echo "URL: $authUrl\n";
echo "State: $state\n\n";

// Test 2: Test với code giả (sẽ thất bại nhưng để test format)
echo "2. Test lấy Access Token (với code giả):\n";
echo "----------------------------------------\n";

$fakeCode = 'fake_authorization_code_for_testing';
$result = $notificationService->getAccessToken($appId, $appSecret, $redirectUri, $fakeCode);

if ($result['success']) {
    echo "✅ Access token retrieved successfully!\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Expected failure (using fake code):\n";
    echo "Error: " . $result['message'] . "\n";
    echo "Error Code: " . ($result['error'] ?? 'N/A') . "\n";
}
echo "\n";

// Test 3: Test với refresh token giả
echo "3. Test refresh Access Token (với refresh token giả):\n";
echo "-----------------------------------------------------\n";

$fakeRefreshToken = 'fake_refresh_token_for_testing';
$result = $notificationService->refreshAccessToken($appId, $appSecret, $fakeRefreshToken);

if ($result['success']) {
    echo "✅ Access token refreshed successfully!\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Expected failure (using fake refresh token):\n";
    echo "Error: " . $result['message'] . "\n";
    echo "Error Code: " . ($result['error'] ?? 'N/A') . "\n";
}
echo "\n";

// Test 4: Test với access token giả
echo "4. Test lấy thông tin người dùng (với access token giả):\n";
echo "--------------------------------------------------------\n";

$fakeAccessToken = 'fake_access_token_for_testing';
$result = $notificationService->getUserInfo($fakeAccessToken);

if ($result['success']) {
    echo "✅ User info retrieved successfully!\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Expected failure (using fake access token):\n";
    echo "Error: " . $result['message'] . "\n";
    echo "Error Code: " . ($result['error'] ?? 'N/A') . "\n";
}
echo "\n";

echo "📚 Hướng dẫn sử dụng thực tế:\n";
echo "==============================\n";
echo "1. Sử dụng Auth URL để người dùng xác thực\n";
echo "2. Người dùng sẽ được chuyển hướng về redirect_uri với code\n";
echo "3. Sử dụng code để lấy access_token và refresh_token\n";
echo "4. Sử dụng access_token để gọi các API khác\n";
echo "5. Khi access_token hết hạn, sử dụng refresh_token để lấy token mới\n\n";

echo "🔗 API Endpoints để test:\n";
echo "=========================\n";
echo "POST /api/zalo/oauth/auth-url\n";
echo "POST /api/zalo/oauth/access-token\n";
echo "POST /api/zalo/oauth/refresh-token\n";
echo "POST /api/zalo/oauth/user-info\n\n";

echo "📝 Example cURL commands:\n";
echo "=========================\n";

echo "1. Tạo Auth URL:\n";
echo "curl -X POST http://localhost/club/public/api/zalo/oauth/auth-url \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"redirect_uri\": \"https://your-domain.com/callback\", \"state\": \"test123\"}'\n\n";

echo "2. Lấy Access Token:\n";
echo "curl -X POST http://localhost/club/public/api/zalo/oauth/access-token \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"code\": \"REAL_AUTHORIZATION_CODE\", \"redirect_uri\": \"https://your-domain.com/callback\"}'\n\n";

echo "3. Refresh Access Token:\n";
echo "curl -X POST http://localhost/club/public/api/zalo/oauth/refresh-token \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"refresh_token\": \"REAL_REFRESH_TOKEN\"}'\n\n";

echo "4. Lấy thông tin người dùng:\n";
echo "curl -X POST http://localhost/club/public/api/zalo/oauth/user-info \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"access_token\": \"REAL_ACCESS_TOKEN\"}'\n\n";

echo "✅ Test completed!\n";

