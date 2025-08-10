<?php

// Test script để kiểm tra API
$baseUrl = 'http://localhost:8000/api';

echo "Testing API endpoints...\n\n";

// Test public route
echo "1. Testing public route /test:\n";
$response = file_get_contents($baseUrl . '/test');
echo "Response: " . $response . "\n\n";

// Test auth check route
echo "2. Testing auth check route:\n";
$response = file_get_contents($baseUrl . '/auth/check');
echo "Response: " . $response . "\n\n";

// Test protected route without token
echo "3. Testing protected route without token:\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json',
    ]
]);
$response = file_get_contents($baseUrl . '/members', false, $context);
echo "Response: " . $response . "\n\n";

echo "Test completed!\n";
