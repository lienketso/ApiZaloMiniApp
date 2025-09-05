<?php

/**
 * Test script đơn giản để kiểm tra Zalo OA API (MIỄN PHÍ)
 * 
 * Không cần Laravel context, test trực tiếp với cURL
 * 
 * Cách sử dụng:
 * 1. Cấu hình ZALO_OA_ACCESS_TOKEN trong file .env
 * 2. Chạy: php test_zalo_simple.php
 */

// Load environment variables từ file .env
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "🧪 Testing Zalo OA API (MIỄN PHÍ) - Simple Version\n";
echo "================================================\n";
echo "📚 Dựa trên: https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new\n\n";

// Test 1: Kiểm tra cấu hình
echo "1. Kiểm tra cấu hình Zalo OA (MIỄN PHÍ):\n";
$accessToken = $_ENV['ZALO_OA_ACCESS_TOKEN'] ?? getenv('ZALO_OA_ACCESS_TOKEN');
$appId = $_ENV['ZALO_APP_ID'] ?? getenv('ZALO_APP_ID');
$oaId = $_ENV['ZALO_OA_ID'] ?? getenv('ZALO_OA_ID');

if (!$accessToken) {
    echo "❌ ZALO_OA_ACCESS_TOKEN chưa được cấu hình\n";
    echo "💡 Chỉ cần ZALO_OA_ACCESS_TOKEN là đủ để gửi broadcast miễn phí!\n";
    echo "📝 Tạo file .env với nội dung:\n";
    echo "ZALO_OA_ACCESS_TOKEN=your_token_here\n";
    echo "ZALO_APP_ID=your_app_id_here\n";
    echo "ZALO_OA_ID=your_oa_id_here\n\n";
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

// Test 2: Test gửi tin nhắn đến Zalo ID cụ thể (MIỄN PHÍ)
echo "2. Test gửi tin nhắn đến Zalo ID cụ thể (MIỄN PHÍ):\n";
$testZaloId = '5170627724267093288';
echo "Zalo ID test: $testZaloId\n";
echo "Bạn có muốn test gửi tin nhắn đến Zalo ID này? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
    echo "Gửi tin nhắn đến Zalo ID: $testZaloId\n";
    
    // Gửi tin nhắn text đơn giản (MIỄN PHÍ)
    $message = "🧪 Test thông báo từ Zalo OA!\n\nĐây là tin nhắn test để kiểm tra chức năng gửi thông báo miễn phí đến Zalo ID cụ thể.\n\nThời gian: " . date('Y-m-d H:i:s');
    
    $result = sendZaloMessage($accessToken, $testZaloId, $message);
    
    if ($result['success']) {
        echo "✅ Gửi tin nhắn thành công!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Gửi tin nhắn thất bại!\n";
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

// Test 3: Test gửi broadcast miễn phí
echo "\n3. Test gửi broadcast miễn phí:\n";
echo "Bạn có muốn test gửi broadcast đến tất cả người follow OA? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
    echo "Gửi broadcast message...\n";
    
    $message = "🧪 Test broadcast từ Zalo OA!\n\nĐây là tin nhắn test để kiểm tra chức năng gửi broadcast miễn phí.\n\nThời gian: " . date('Y-m-d H:i:s');
    
    $result = sendZaloBroadcast($accessToken, $message);
    
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

// Test 4: Test lấy token mới từ Zalo Business
echo "\n4. Test lấy token mới từ Zalo Business:\n";
echo "Bạn có muốn test lấy token mới từ Zalo Business? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
    echo "🔑 Lấy token mới từ Zalo Business...\n";
    
    // Lấy App ID và App Secret từ .env
    $appId = $_ENV['ZALO_APP_ID'] ?? getenv('ZALO_APP_ID');
    $appSecret = $_ENV['ZALO_APP_SECRET'] ?? getenv('ZALO_APP_SECRET');
    
    if (!$appId || !$appSecret) {
        echo "❌ ZALO_APP_ID hoặc ZALO_APP_SECRET chưa được cấu hình\n";
        echo "💡 Cần cấu hình để lấy token mới:\n";
        echo "ZALO_APP_ID=your_app_id_here\n";
        echo "ZALO_APP_SECRET=your_app_secret_here\n";
    } else {
        echo "App ID: $appId\n";
        echo "App Secret: " . substr($appSecret, 0, 10) . "...\n";
        
        // Tạo URL xác thực
        $redirectUri = 'https://api.lienketso.vn/callback'; // Thay đổi thành domain thực tế
        $state = uniqid('test_');
        $authUrl = "https://oauth.zaloapp.com/v4/oa/permission?app_id=$appId&redirect_uri=" . urlencode($redirectUri) . "&state=$state";
        
        echo "\n🔗 URL xác thực:\n";
        echo "$authUrl\n\n";
        
        echo "📝 Hướng dẫn:\n";
        echo "1. Mở URL trên trong trình duyệt\n";
        echo "2. Đăng nhập và cấp quyền cho ứng dụng\n";
        echo "3. Copy authorization code từ URL callback\n";
        echo "4. Nhập code vào đây để lấy access token\n\n";
        
        echo "Nhập authorization code (hoặc nhấn Enter để skip): ";
        $code = trim(fgets(STDIN));
        
        if ($code) {
            echo "Lấy access token với code: $code\n";
            
            $result = getZaloAccessToken($appId, $appSecret, $redirectUri, $code);
            
            if ($result['success']) {
                echo "✅ Lấy access token thành công!\n";
                echo "Access Token: " . $result['data']['access_token'] . "\n";
                echo "Refresh Token: " . $result['data']['refresh_token'] . "\n";
                echo "Expires In: " . $result['data']['expires_in'] . " giây\n";
                
                echo "\n💾 Cập nhật file .env:\n";
                echo "ZALO_OA_ACCESS_TOKEN=" . $result['data']['access_token'] . "\n";
                
                // Tự động cập nhật file .env
                echo "\nBạn có muốn tự động cập nhật file .env? (y/n): ";
                $updateConfirm = trim(fgets(STDIN));
                
                if (strtolower($updateConfirm) === 'y' || strtolower($updateConfirm) === 'yes') {
                    updateEnvFile('ZALO_OA_ACCESS_TOKEN', $result['data']['access_token']);
                    echo "✅ File .env đã được cập nhật!\n";
                }
            } else {
                echo "❌ Lấy access token thất bại!\n";
                echo "Error: " . $result['message'] . "\n";
                echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "⏭️  Skipped token generation\n";
        }
    }
} else {
    echo "⏭️  Skipped token generation\n";
}

// Test 5: Test refresh token (nếu có)
echo "\n5. Test refresh token (nếu có):\n";
$refreshToken = $_ENV['ZALO_REFRESH_TOKEN'] ?? getenv('ZALO_REFRESH_TOKEN');
if ($refreshToken) {
    echo "Refresh Token: " . substr($refreshToken, 0, 10) . "...\n";
    echo "Bạn có muốn test refresh token? (y/n): ";
    $confirm = trim(fgets(STDIN));
    
    if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
        $appId = $_ENV['ZALO_APP_ID'] ?? getenv('ZALO_APP_ID');
        $appSecret = $_ENV['ZALO_APP_SECRET'] ?? getenv('ZALO_APP_SECRET');
        
        if ($appId && $appSecret) {
            $result = refreshZaloAccessToken($appId, $appSecret, $refreshToken);
            
            if ($result['success']) {
                echo "✅ Refresh token thành công!\n";
                echo "New Access Token: " . $result['data']['access_token'] . "\n";
                echo "New Refresh Token: " . $result['data']['refresh_token'] . "\n";
                echo "Expires In: " . $result['data']['expires_in'] . " giây\n";
                
                // Tự động cập nhật file .env
                echo "\nBạn có muốn tự động cập nhật file .env? (y/n): ";
                $updateConfirm = trim(fgets(STDIN));
                
                if (strtolower($updateConfirm) === 'y' || strtolower($updateConfirm) === 'yes') {
                    updateEnvFile('ZALO_OA_ACCESS_TOKEN', $result['data']['access_token']);
                    updateEnvFile('ZALO_REFRESH_TOKEN', $result['data']['refresh_token']);
                    echo "✅ File .env đã được cập nhật!\n";
                }
            } else {
                echo "❌ Refresh token thất bại!\n";
                echo "Error: " . $result['message'] . "\n";
                echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "❌ ZALO_APP_ID hoặc ZALO_APP_SECRET chưa được cấu hình\n";
        }
    } else {
        echo "⏭️  Skipped refresh token test\n";
    }
} else {
    echo "⚠️  ZALO_REFRESH_TOKEN chưa được cấu hình\n";
    echo "💡 Refresh token sẽ được tạo khi lấy access token mới\n";
}

echo "\n6. Hướng dẫn lấy Zalo OA Access Token:\n";
echo "1. Truy cập: https://business.zalo.me/\n";
echo "2. Đăng nhập và chọn Official Account\n";
echo "3. Vào Cài đặt → Tích hợp → Lấy Access Token\n";
echo "4. Cập nhật ZALO_OA_ACCESS_TOKEN trong file .env\n\n";

echo "📚 Tài liệu tham khảo:\n";
echo "https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new\n\n";

echo "🎉 Test completed!\n";
echo "💡 Lưu ý: Sử dụng broadcast miễn phí thay vì gửi cá nhân để tiết kiệm chi phí!\n";
echo "🎯 Zalo ID test: 5170627724267093288\n";

/**
 * Gửi tin nhắn đến Zalo ID cụ thể
 */
function sendZaloMessage($accessToken, $zaloId, $message) {
    $url = 'https://openapi.zalo.me/v3.0/oa/message/cs';
    
    $payload = [
        'recipient' => [
            'user_id' => $zaloId
        ],
        'message' => [
            'text' => $message
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['error']) && $result['error'] === 0) {
        return [
            'success' => true,
            'message' => 'Tin nhắn đã được gửi thành công',
            'data' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Gửi tin nhắn thất bại',
            'error' => $result['error'] ?? $httpCode,
            'data' => $result
        ];
    }
}

/**
 * Gửi broadcast miễn phí
 */
function sendZaloBroadcast($accessToken, $message) {
    $url = 'https://openapi.zalo.me/v3.0/oa/message/broadcast';
    
    $payload = [
        'message' => [
            'text' => $message
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['error']) && $result['error'] === 0) {
        return [
            'success' => true,
            'message' => 'Broadcast đã được gửi thành công',
            'data' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Gửi broadcast thất bại',
            'error' => $result['error'] ?? $httpCode,
            'data' => $result
        ];
    }
}

/**
 * Lấy Access Token từ Authorization Code (OAuth v4)
 */
function getZaloAccessToken($appId, $appSecret, $redirectUri, $code) {
    $url = 'https://oauth.zaloapp.com/v4/access_token';
    
    $payload = [
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['access_token'])) {
        return [
            'success' => true,
            'message' => 'Access token retrieved successfully',
            'data' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to get access token',
            'error' => $result['error'] ?? $httpCode,
            'data' => $result
        ];
    }
}

/**
 * Làm mới Access Token bằng Refresh Token
 */
function refreshZaloAccessToken($appId, $appSecret, $refreshToken) {
    $url = 'https://oauth.zaloapp.com/v4/refresh_token';
    
    $payload = [
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'refresh_token' => $refreshToken
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['access_token'])) {
        return [
            'success' => true,
            'message' => 'Access token refreshed successfully',
            'data' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to refresh access token',
            'error' => $result['error'] ?? $httpCode,
            'data' => $result
        ];
    }
}

/**
 * Lấy thông tin người dùng từ Access Token
 */
function getZaloUserInfo($accessToken) {
    $url = 'https://graph.zalo.me/v2.0/me';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['id'])) {
        return [
            'success' => true,
            'message' => 'User info retrieved successfully',
            'data' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Failed to get user info',
            'error' => $result['error'] ?? $httpCode,
            'data' => $result
        ];
    }
}

/**
 * Cập nhật file .env
 */
function updateEnvFile($key, $value) {
    $envFile = '.env';
    
    if (!file_exists($envFile)) {
        // Tạo file .env mới
        file_put_contents($envFile, "$key=$value\n");
        return true;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;
    
    foreach ($lines as $index => $line) {
        if (strpos($line, $key . '=') === 0) {
            $lines[$index] = "$key=$value";
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        $lines[] = "$key=$value";
    }
    
    return file_put_contents($envFile, implode("\n", $lines) . "\n") !== false;
}
