<?php
/**
 * Test script cho ZMP Authentication API
 * Chạy: php test_zmp_auth.php
 */

// Cấu hình
$baseUrl = 'http://localhost:8000'; // Thay đổi nếu cần
$apiEndpoint = '/api/auth/zalo/auto-login';

// Test data
$testData = [
    'zalo_gid' => 'test_zmp_user_' . time()
];

echo "=== Testing ZMP Authentication API ===\n";
echo "Base URL: {$baseUrl}\n";
echo "Endpoint: {$apiEndpoint}\n";
echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Tạo cURL request
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . $apiEndpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true
]);

echo "Sending request...\n";

// Thực hiện request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Xử lý response
echo "HTTP Status Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response:\n";
    $responseData = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if (isset($responseData['success']) && $responseData['success']) {
            echo "\n✅ SUCCESS: User authentication successful!\n";
            if (isset($responseData['data']['user']['id'])) {
                echo "User ID: " . $responseData['data']['user']['id'] . "\n";
            }
            if (isset($responseData['data']['token'])) {
                echo "Token: " . substr($responseData['data']['token'], 0, 20) . "...\n";
            }
        } else {
            echo "\n❌ FAILED: " . ($responseData['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "Raw Response: {$response}\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
}

echo "\n=== Test completed ===\n";
?>
