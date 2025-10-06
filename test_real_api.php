<?php

// Test script để kiểm tra API thực tế
$baseUrl = 'https://api.lienketso.vn/public/api';

echo "=== Testing Real API ===\n\n";

// Test 1: Kiểm tra API có hoạt động không
echo "1. Testing basic API...\n";
$testUrl = $baseUrl . '/test';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json',
        'timeout' => 30
    ]
]);

$response = file_get_contents($testUrl, false, $context);
echo "Response: " . $response . "\n\n";

// Test 2: Kiểm tra getAvailableClubs với user_id = 1
echo "2. Testing getAvailableClubs with user_id=1...\n";
$availableUrl = $baseUrl . '/clubs/available?user_id=1';
$response = file_get_contents($availableUrl, false, $context);
$data = json_decode($response, true);

if ($data) {
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    
    if (isset($data['data'])) {
        echo "Joined clubs count: " . count($data['data']['joined_clubs'] ?? []) . "\n";
        echo "Available clubs count: " . count($data['data']['available_clubs'] ?? []) . "\n";
        
        if (!empty($data['data']['joined_clubs'])) {
            echo "First joined club: " . ($data['data']['joined_clubs'][0]['name'] ?? 'N/A') . "\n";
        }
        if (!empty($data['data']['available_clubs'])) {
            echo "First available club: " . ($data['data']['available_clubs'][0]['name'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "Failed to decode JSON response\n";
    echo "Raw response: " . $response . "\n";
}

echo "\n";

// Test 3: Kiểm tra getDashboardData với user_id = 1
echo "3. Testing getDashboardData with user_id=1...\n";
$dashboardUrl = $baseUrl . '/clubs/dashboard?user_id=1';
$response = file_get_contents($dashboardUrl, false, $context);
$data = json_decode($response, true);

if ($data) {
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    
    if (isset($data['data'])) {
        echo "User: " . (isset($data['data']['user']) ? 'Yes' : 'No') . "\n";
        echo "Joined clubs count: " . count($data['data']['joined_clubs'] ?? []) . "\n";
        echo "Available clubs count: " . count($data['data']['available_clubs'] ?? []) . "\n";
        echo "Pending invitations count: " . count($data['data']['pending_invitations'] ?? []) . "\n";
    }
} else {
    echo "Failed to decode JSON response\n";
    echo "Raw response: " . $response . "\n";
}

echo "\n=== Test completed ===\n";
