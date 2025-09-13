<?php

require_once 'vendor/autoload.php';

// Load environment variables
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Zalo configuration
$appId = $_ENV['ZALO_APP_ID'] ?? '';
$appSecret = $_ENV['ZALO_APP_SECRET'] ?? '';
$redirectUri = 'https://api.lienketso.vn/callback'; // URL thực tế của bạn

if (empty($appId) || empty($appSecret)) {
    echo "❌ Thiếu ZALO_APP_ID hoặc ZALO_APP_SECRET trong file .env\n";
    exit(1);
}

echo "🔧 Zalo OAuth v4 - Lấy Authorization Code\n";
echo "==========================================\n\n";

// Tạo URL xác thực
$state = uniqid();
$authUrl = "https://oauth.zaloapp.com/v4/oa/permission?" . http_build_query([
    'app_id' => $appId,
    'redirect_uri' => urlencode($redirectUri),
    'state' => $state
]);

echo "📋 Thông tin cấu hình:\n";
echo "App ID: {$appId}\n";
echo "Redirect URI: {$redirectUri}\n";
echo "State: {$state}\n\n";

echo "🔗 Bước 1: Truy cập URL xác thực sau:\n";
echo "{$authUrl}\n\n";

echo "📱 Bước 2: Đăng nhập và cấp quyền cho ứng dụng\n";
echo "📋 Bước 3: Copy authorization code từ URL callback\n";
echo "   (URL sẽ có dạng: {$redirectUri}?code=XXXXX&state={$state})\n\n";

echo "Nhập authorization code: ";
$handle = fopen("php://stdin", "r");
$authCode = trim(fgets($handle));
fclose($handle);

if (empty($authCode)) {
    echo "❌ Authorization code không được để trống!\n";
    exit(1);
}

echo "\n🔄 Đang lấy access token...\n";

// Lấy access token
$url = 'https://oauth.zaloapp.com/v4/access_token';
$payload = [
    'app_id' => $appId,
    'app_secret' => $appSecret,
    'redirect_uri' => $redirectUri,
    'code' => $authCode
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['access_token'])) {
    echo "✅ Lấy access token thành công!\n\n";
    
    echo "📋 Thông tin token:\n";
    echo "Access Token: " . substr($data['access_token'], 0, 20) . "...\n";
    echo "Refresh Token: " . (isset($data['refresh_token']) ? substr($data['refresh_token'], 0, 20) . "..." : "Không có") . "\n";
    echo "Expires In: " . ($data['expires_in'] ?? 'Không có') . " giây\n\n";
    
    // Cập nhật file .env
    echo "🔄 Đang cập nhật file .env...\n";
    
    $envContent = file_get_contents('.env');
    
    // Cập nhật hoặc thêm ZALO_OA_ACCESS_TOKEN
    if (strpos($envContent, 'ZALO_OA_ACCESS_TOKEN=') !== false) {
        $envContent = preg_replace('/ZALO_OA_ACCESS_TOKEN=.*/', 'ZALO_OA_ACCESS_TOKEN=' . $data['access_token'], $envContent);
    } else {
        $envContent .= "\nZALO_OA_ACCESS_TOKEN=" . $data['access_token'];
    }
    
    // Cập nhật hoặc thêm ZALO_OA_REFRESH_TOKEN
    if (isset($data['refresh_token'])) {
        if (strpos($envContent, 'ZALO_OA_REFRESH_TOKEN=') !== false) {
            $envContent = preg_replace('/ZALO_OA_REFRESH_TOKEN=.*/', 'ZALO_OA_REFRESH_TOKEN=' . $data['refresh_token'], $envContent);
        } else {
            $envContent .= "\nZALO_OA_REFRESH_TOKEN=" . $data['refresh_token'];
        }
    }
    
    file_put_contents('.env', $envContent);
    echo "✅ Đã cập nhật file .env thành công!\n\n";
    
    // Test gửi tin nhắn
    echo "🧪 Đang test gửi tin nhắn...\n";
    
    $testUserId = "5170627724267093288";
    $testMessage = "Xin chào, đây là tin nhắn test từ Laravel!";
    
    $testUrl = "https://openapi.zalo.me/v3.0/oa/message/cs";
    $testPayload = [
        "recipient" => [
            "user_id" => $testUserId
        ],
        "message" => [
            "text" => $testMessage
        ]
    ];
    
    $testCh = curl_init();
    curl_setopt($testCh, CURLOPT_URL, $testUrl);
    curl_setopt($testCh, CURLOPT_POST, true);
    curl_setopt($testCh, CURLOPT_POSTFIELDS, json_encode($testPayload));
    curl_setopt($testCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($testCh, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'access_token' => $data['access_token']
    ]);
    
    $testResponse = curl_exec($testCh);
    $testHttpCode = curl_getinfo($testCh, CURLINFO_HTTP_CODE);
    curl_close($testCh);
    
    $testData = json_decode($testResponse, true);
    
    if ($testHttpCode === 200 && isset($testData['error']) && $testData['error'] === 0) {
        echo "✅ Test gửi tin nhắn thành công!\n";
        echo "📱 Tin nhắn đã được gửi đến user ID: {$testUserId}\n";
    } else {
        echo "❌ Test gửi tin nhắn thất bại!\n";
        echo "Response: " . $testResponse . "\n";
    }
    
} else {
    echo "❌ Lấy access token thất bại!\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n";
    
    if (isset($data['error'])) {
        echo "Error: " . $data['error'] . "\n";
        echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n🎉 Hoàn thành!\n";
