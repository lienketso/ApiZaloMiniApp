<?php
/**
 * Debug script để kiểm tra dữ liệu club và membership
 */

require_once 'vendor/autoload.php';

try {
    // Kết nối database
    $host = '127.0.0.1';
    $dbname = 'club';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4;unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Kết nối database thành công\n\n";
    
    // 1. Kiểm tra dữ liệu trong bảng user_clubs
    echo "🔍 1. Dữ liệu trong bảng user_clubs:\n";
    $stmt = $pdo->query("SELECT * FROM user_clubs ORDER BY user_id, club_id");
    $allMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allMemberships) {
        foreach ($allMemberships as $membership) {
            echo "  - ID: " . $membership['id'] . " | User ID: " . $membership['user_id'] . " | Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . " | Is Active: " . $membership['is_active'] . "\n";
        }
    } else {
        echo "  ❌ Bảng user_clubs trống\n";
    }
    
    echo "\n";
    
    // 2. Kiểm tra user_id = 1 có membership gì
    echo "🔍 2. Memberships của user_id = 1:\n";
    $stmt = $pdo->prepare("SELECT * FROM user_clubs WHERE user_id = ?");
    $stmt->execute([1]);
    $user1Memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($user1Memberships) {
        foreach ($user1Memberships as $membership) {
            echo "  - Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . " | Is Active: " . $membership['is_active'] . "\n";
        }
    } else {
        echo "  ❌ User ID 1 không có membership nào\n";
    }
    
    echo "\n";
    
    // 3. Kiểm tra clubs có tồn tại không
    echo "🔍 3. Clubs trong database:\n";
    $stmt = $pdo->query("SELECT id, name, is_setup FROM clubs ORDER BY id");
    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($clubs) {
        foreach ($clubs as $club) {
            echo "  - Club ID: " . $club['id'] . " | Name: " . $club['name'] . " | Is Setup: " . $club['is_setup'] . "\n";
        }
    } else {
        echo "  ❌ Không có club nào\n";
    }
    
    echo "\n";
    
    // 4. Kiểm tra logic query trong ClubController
    echo "🔍 4. Logic query trong ClubController:\n";
    
    // Simulate logic từ ClubController
    $userId = 1;
    
    // Lấy tất cả memberships của user (không filter theo is_active)
    $stmt = $pdo->prepare("SELECT * FROM user_clubs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Query: SELECT * FROM user_clubs WHERE user_id = $userId\n";
    echo "  Kết quả: " . count($userMemberships) . " memberships\n";
    
    if ($userMemberships) {
        foreach ($userMemberships as $membership) {
            echo "    - Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . "\n";
        }
    }
    
    // Lấy club IDs từ memberships
    $joinedClubIds = array_column($userMemberships, 'club_id');
    echo "  Joined Club IDs: " . implode(', ', $joinedClubIds) . "\n";
    
    // Lấy clubs có is_setup = true
    $stmt = $pdo->prepare("SELECT id FROM clubs WHERE is_setup = ?");
    $stmt->execute([true]);
    $setupClubs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "  Setup Clubs: " . implode(', ', $setupClubs) . "\n";
    
    // Lấy joined clubs
    $joinedClubs = array_intersect($joinedClubIds, $setupClubs);
    echo "  Final Joined Clubs: " . implode(', ', $joinedClubs) . "\n";
    
    echo "\n";
    
    // 5. So sánh với API response
    echo "🔍 5. So sánh với API response:\n";
    echo "  API joined_clubs: [] (rỗng)\n";
    echo "  Database joined_clubs: " . implode(', ', $joinedClubs) . "\n";
    
    if (empty($joinedClubs)) {
        echo "  ❌ VẤN ĐỀ: Database có memberships nhưng joined_clubs rỗng!\n";
        echo "  Nguyên nhân có thể:\n";
        echo "    1. Clubs không có is_setup = true\n";
        echo "    2. Logic query trong ClubController sai\n";
        echo "    3. Cache hoặc thay đổi chưa được deploy\n";
    } else {
        echo "  ✅ Database có dữ liệu, vấn đề ở API\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug hoàn tất\n";
?>
