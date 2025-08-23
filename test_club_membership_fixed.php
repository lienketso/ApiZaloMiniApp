<?php
/**
 * Test script để kiểm tra ClubMembershipController@getAvailableClubs
 * Sửa lỗi "Undefined array key message"
 */

// Cấu hình
$apiUrl = 'https://api.lienketso.vn/public/api';
$phone = '0123456789'; // Số điện thoại test

echo "🧪 Testing ClubMembershipController@getAvailableClubs...\n";
echo "📡 API URL: {$apiUrl}/club-membership/available-clubs?phone={$phone}\n";
echo "📱 Phone: {$phone}\n\n";

// Test API
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "{$apiUrl}/club-membership/available-clubs?phone={$phone}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

echo "🔄 Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Response Status: HTTP {$httpCode}\n";

if ($error) {
    echo "❌ cURL Error: {$error}\n";
    exit(1);
}

if ($response === false) {
    echo "❌ No response received\n";
    exit(1);
}

echo "📥 Raw Response:\n";
echo $response . "\n\n";

// Parse JSON response
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON Parse Error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ JSON parsed successfully\n";
echo "📋 Response Structure:\n";
echo "- success: " . ($data['success'] ? 'true' : 'false') . "\n";

if (isset($data['message'])) {
    echo "- message: " . $data['message'] . "\n";
} else {
    echo "- message: ❌ MISSING\n";
}

if (isset($data['data'])) {
    echo "- data: " . (is_array($data['data']) ? 'array(' . count($data['data']) . ' items)' : 'not array') . "\n";
} else {
    echo "- data: ❌ MISSING\n";
}

if (isset($data['total'])) {
    echo "- total: " . $data['total'] . "\n";
} else {
    echo "- total: ❌ MISSING\n";
}

if (isset($data['error'])) {
    echo "- error: " . $data['error'] . "\n";
}

echo "\n🎯 Test completed!\n";

// Kiểm tra xem có lỗi "Undefined array key message" không
if (!isset($data['message'])) {
    echo "⚠️  WARNING: Response missing 'message' key - this could cause frontend errors\n";
} else {
    echo "✅ SUCCESS: Response has 'message' key - no more 'Undefined array key message' errors\n";
}
?>
