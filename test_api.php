<?php
/**
 * Test script để kiểm tra API endpoints và xác định vấn đề lỗi 403
 */

$baseUrl = 'https://api.lienketso.vn/public/api';

echo "=== TESTING API ENDPOINTS ===\n\n";

// Test 1: Public endpoint
echo "1. Testing public endpoint /test:\n";
$response = testEndpoint($baseUrl . '/test', 'GET');
echo "Status: " . $response['status'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 2: Auth check endpoint
echo "2. Testing auth check endpoint /auth/check:\n";
$response = testEndpoint($baseUrl . '/auth/check', 'GET');
echo "Status: " . $response['status'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 3: Auto login endpoint
echo "3. Testing auto login endpoint /auth/zalo/auto-login:\n";
$testData = [
    'zalo_gid' => 'test_zalo_gid_' . time(),
    'name' => 'Test User',
    'phone' => '0123456789',
    'zalo_name' => 'Test User',
    'zalo_avatar' => ''
];
$response = testEndpoint($baseUrl . '/auth/zalo/auto-login', 'POST', $testData);
echo "Status: " . $response['status'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 4: Test protected endpoint without token
echo "4. Testing protected endpoint /members without token:\n";
$response = testEndpoint($baseUrl . '/members', 'GET');
echo "Status: " . $response['status'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 5: Test protected endpoint with invalid token
echo "5. Testing protected endpoint /members with invalid token:\n";
$response = testEndpoint($baseUrl . '/members', 'GET', null, 'Bearer invalid_token');
echo "Status: " . $response['status'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 6: Test protected endpoint with valid token (if we got one from auto-login)
if (isset($response['body']) && strpos($response['body'], 'token') !== false) {
    $data = json_decode($response['body'], true);
    if (isset($data['data']['token'])) {
        $token = $data['data']['token'];
        echo "6. Testing protected endpoint /members with valid token:\n";
        $response = testEndpoint($baseUrl . '/members', 'GET', null, 'Bearer ' . $token);
        echo "Status: " . $response['status'] . "\n";
        echo "Response: " . $response['body'] . "\n\n";
    }
}

function testEndpoint($url, $method, $data = null, $authHeader = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($authHeader) {
        $headers[] = 'Authorization: ' . $authHeader;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'CURL_ERROR', 'body' => $error];
    }
    
    return ['status' => $httpCode, 'body' => $response];
}

echo "=== TEST COMPLETED ===\n";
?>
