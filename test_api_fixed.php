<?php
/**
 * Test API đã được sửa - sử dụng middleware class trực tiếp
 * File này để test các API endpoints đã được sửa
 */

// Cấu hình
$base_url = 'http://localhost/club/public/api';
$timeout = 30;

// Hàm test API
function testApi($url, $method = 'GET', $data = null, $headers = []) {
    global $timeout;
    
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $headers),
    ];
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'http_code' => $http_code
        ];
    }
    
    return [
        'success' => true,
        'http_code' => $http_code,
        'response' => json_decode($response, true),
        'raw_response' => $response
    ];
}

// Test các API đã được sửa
echo "=== TESTING API ĐÃ ĐƯỢC SỬA ===\n\n";

// 1. Test API members (đã được sửa)
echo "1. Testing /members endpoint (đã được sửa):\n";
$result = testApi($base_url . '/members');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " members found\n";
        if (count($result['response']) > 0) {
            echo "   First member: " . $result['response'][0]['name'] . "\n";
        }
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 2. Test API clubs (đã được sửa)
echo "2. Testing /clubs endpoint (đã được sửa):\n";
$result = testApi($base_url . '/clubs');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (isset($result['response']['success']) && $result['response']['success']) {
        echo "   Response: Club found - " . $result['response']['data']['name'] . "\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 3. Test API events (đã được sửa)
echo "3. Testing /events endpoint (đã được sửa):\n";
$result = testApi($base_url . '/events');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " events found\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 4. Test API club-members (đã được sửa)
echo "4. Testing /club-members endpoint (đã được sửa):\n";
$result = testApi($base_url . '/club-members');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " club members found\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 5. Test API fund-transactions (đã được sửa)
echo "5. Testing /fund-transactions endpoint (đã được sửa):\n";
$result = testApi($base_url . '/fund-transactions');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " fund transactions found\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 6. Test API matches (đã được sửa)
echo "6. Testing /matches endpoint (đã được sửa):\n";
$result = testApi($base_url . '/matches');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " matches found\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 7. Test API attendance (đã được sửa)
echo "7. Testing /attendance endpoint (đã được sửa):\n";
$result = testApi($base_url . '/attendance');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    if (is_array($result['response'])) {
        echo "   Response: " . count($result['response']) . " attendance records found\n";
    } else {
        echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

echo "=== TEST COMPLETED ===\n";
echo "Nếu tất cả các API đều hoạt động bình thường,\n";
echo "thì vấn đề đã được giải quyết thành công!\n";
echo "\n";
echo "Vấn đề ban đầu là middleware 'auth:sanctum' không được đăng ký đúng cách.\n";
echo "Giải pháp: Sử dụng middleware class trực tiếp:\n";
echo "\\Laravel\\Sanctum\\Http\\Middleware\\EnsureFrontendRequestsAreStateful::class\n";
echo "\n";
echo "Thay vì:\n";
echo "middleware('auth:sanctum')\n";
