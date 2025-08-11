<?php
/**
 * Test API không dùng Sanctum
 * File này để test các API endpoints không cần authentication
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

// Test các routes không cần auth
echo "=== TESTING API ROUTES KHÔNG DÙNG SANCTUM ===\n\n";

// 1. Test basic API
echo "1. Testing /test endpoint:\n";
$result = testApi($base_url . '/test');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 2. Test no auth endpoint
echo "2. Testing /test-no-auth endpoint:\n";
$result = testApi($base_url . '/test-no-auth');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 3. Test database connection
echo "3. Testing /test-db endpoint:\n";
$result = testApi($base_url . '/test-db');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 4. Test members query
echo "4. Testing /test-members endpoint:\n";
$result = testApi($base_url . '/test-members');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 5. Test clubs query
echo "5. Testing /test-clubs endpoint:\n";
$result = testApi($base_url . '/test-clubs');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 6. Test auth check
echo "6. Testing /auth/check endpoint:\n";
$result = testApi($base_url . '/auth/check');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

// 7. Test protected route (sẽ fail vì không có token)
echo "7. Testing protected route /members (sẽ fail vì không có token):\n";
$result = testApi($base_url . '/members');
if ($result['success']) {
    echo "   HTTP Code: " . $result['http_code'] . "\n";
    echo "   Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "   Error: " . $result['error'] . "\n";
}
echo "\n";

echo "=== TEST COMPLETED ===\n";
echo "Nếu các routes không auth hoạt động nhưng routes có auth vẫn lỗi 503,\n";
echo "thì vấn đề có thể là:\n";
echo "1. Sanctum middleware có vấn đề\n";
echo "2. Database connection trong protected routes\n";
echo "3. Model relationships hoặc queries\n";
echo "4. Middleware configuration\n";
