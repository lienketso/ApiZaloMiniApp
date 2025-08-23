<?php
/**
 * Test kiểm tra status membership trong bảng user_clubs
 * Kiểm tra xem user_id = 5, club_id = 5 có status gì
 */

require_once 'vendor/autoload.php';

try {
    // Kết nối database
    $host = 'localhost';
    $dbname = 'club';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Kết nối database thành công\n\n";
    
    // Kiểm tra bảng user_clubs
    echo "🔍 Kiểm tra bảng user_clubs:\n";
    $stmt = $pdo->query("SELECT * FROM user_clubs WHERE user_id = 5 AND club_id = 5");
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($membership) {
        echo "📋 Tìm thấy membership:\n";
        echo "  - ID: " . $membership['id'] . "\n";
        echo "  - User ID: " . $membership['user_id'] . "\n";
        echo "  - Club ID: " . $membership['club_id'] . "\n";
        echo "  - Status: " . $membership['status'] . "\n";
        echo "  - Role: " . $membership['role'] . "\n";
        echo "  - Joined Date: " . $membership['joined_date'] . "\n";
        echo "  - Is Active: " . $membership['is_active'] . "\n";
        echo "  - Notes: " . $membership['notes'] . "\n";
        echo "  - Approved At: " . $membership['approved_at'] . "\n";
        echo "  - Approved By: " . $membership['approved_by'] . "\n";
        echo "  - Rejection Reason: " . $membership['rejection_reason'] . "\n";
    } else {
        echo "❌ Không tìm thấy membership cho user_id = 5, club_id = 5\n";
    }
    
    echo "\n";
    
    // Kiểm tra tất cả memberships của user_id = 5
    echo "🔍 Tất cả memberships của user_id = 5:\n";
    $stmt = $pdo->query("SELECT * FROM user_clubs WHERE user_id = 5");
    $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($memberships) {
        foreach ($memberships as $membership) {
            echo "  - Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . "\n";
        }
    } else {
        echo "  ❌ Không có membership nào cho user_id = 5\n";
    }
    
    echo "\n";
    
    // Kiểm tra tất cả memberships của club_id = 5
    echo "🔍 Tất cả memberships của club_id = 5:\n";
    $stmt = $pdo->query("SELECT * FROM user_clubs WHERE club_id = 5");
    $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($memberships) {
        foreach ($memberships as $membership) {
            echo "  - User ID: " . $membership['user_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . "\n";
        }
    } else {
        echo "  ❌ Không có membership nào cho club_id = 5\n";
    }
    
    echo "\n";
    
    // Kiểm tra tất cả dữ liệu trong bảng user_clubs
    echo "🔍 Tất cả dữ liệu trong bảng user_clubs:\n";
    $stmt = $pdo->query("SELECT * FROM user_clubs ORDER BY user_id, club_id");
    $allMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allMemberships) {
        foreach ($allMemberships as $membership) {
            echo "  - ID: " . $membership['id'] . " | User ID: " . $membership['user_id'] . " | Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . "\n";
        }
    } else {
        echo "  ❌ Bảng user_clubs trống\n";
    }
    
    echo "\n";
    
    // Test API endpoint check-status
    echo "🔍 Test API endpoint /user-clubs/check-status:\n";
    
    // Test với user_id = 1, club_id = 1
    $testUrl = "http://localhost/club/public/api/user-clubs/check-status?club_id=1&user_id=1";
    echo "  Testing URL: $testUrl\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  HTTP Code: $httpCode\n";
    echo "  Response: $response\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (isset($data['data'])) {
                echo "  Status: " . $data['data']['status'] . "\n";
                echo "  Role: " . $data['data']['role'] . "\n";
            }
        }
    }
    
    echo "\n";
    
    // Kiểm tra cấu trúc bảng user_clubs
    echo "🔍 Cấu trúc bảng user_clubs:\n";
    $stmt = $pdo->query("DESCRIBE user_clubs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " | Type: " . $column['Type'] . " | Null: " . $column['Null'] . " | Default: " . $column['Default'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n✅ Test hoàn tất\n";
?>
