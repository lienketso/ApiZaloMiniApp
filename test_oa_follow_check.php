<?php
/**
 * Test script để kiểm tra API check OA follow status
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Services\ZaloNotificationService;

// Test trực tiếp service
echo "=== TEST ZALO OA FOLLOW CHECK SERVICE ===\n\n";

try {
    $zaloService = new ZaloNotificationService();
    
    // Test với một zalo_gid mẫu
    $testZaloGid = "5170627724267093288"; // Thay bằng zalo_gid thực tế để test
    
    echo "Testing with zalo_gid: $testZaloGid\n";
    echo "----------------------------------------\n";
    
    $result = $zaloService->checkUserFollowOA($testZaloGid);
    
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
    if ($result['success']) {
        echo "✅ Service hoạt động bình thường\n";
        if (isset($result['data']['is_following'])) {
            $status = $result['data']['is_following'] ? 'Đã follow' : 'Chưa follow';
            echo "Trạng thái follow OA: $status\n";
        }
    } else {
        echo "❌ Service có lỗi: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST API ENDPOINT ===\n\n";

// Test API endpoint
$apiUrl = 'http://localhost/club/public/api/zalo/oauth/check-follow';
$testData = [
    'zalo_gid' => '5170627724267093288' // Thay bằng zalo_gid thực tế
];

echo "Testing API endpoint: $apiUrl\n";
echo "Data: " . json_encode($testData) . "\n";
echo "----------------------------------------\n";

try {
    $response = Http::post($apiUrl, $testData);
    
    echo "Status Code: " . $response->status() . "\n";
    echo "Response:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";
    
    if ($response->successful()) {
        $data = $response->json();
        if ($data['success']) {
            echo "✅ API endpoint hoạt động bình thường\n";
            if (isset($data['data']['is_following'])) {
                $status = $data['data']['is_following'] ? 'Đã follow' : 'Chưa follow';
                echo "Trạng thái follow OA: $status\n";
            }
        } else {
            echo "❌ API trả về lỗi: " . $data['message'] . "\n";
        }
    } else {
        echo "❌ HTTP Error: " . $response->status() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
