<?php
/**
 * Test kiểm tra dữ liệu thực tế từ API và logic club-list.tsx
 * Kiểm tra API getAvailableClubs, user_clubs table, và logic render
 */

require_once 'vendor/autoload.php';

try {
    // Kết nối database
    $host = 'localhost';
    $dbname = 'apilks_club';
    $username = 'root';
    $password = '@Lks2025@';
    
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
    
    // 2. Kiểm tra API getAvailableClubs
    echo "🔍 2. Test API getAvailableClubs:\n";
    
    // Test với user_id = 1 (có membership)
    $testUrl = "https://api.lienketso.vn/public/api/clubs/available";
    echo "  Testing URL: $testUrl\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'user_id' => 1,
        'zalo_gid' => 'test_user_1'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  HTTP Code: $httpCode\n";
    echo "  Response: " . substr($response, 0, 500) . "...\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (isset($data['data'])) {
                echo "  Joined Clubs Count: " . (isset($data['data']['joined_clubs']) ? count($data['data']['joined_clubs']) : 'N/A') . "\n";
                echo "  Available Clubs Count: " . (isset($data['data']['available_clubs']) ? count($data['data']['available_clubs']) : 'N/A') . "\n";
                
                // Hiển thị chi tiết joined_clubs
                if (isset($data['data']['joined_clubs']) && is_array($data['data']['joined_clubs'])) {
                    echo "  Joined Clubs Details:\n";
                    foreach ($data['data']['joined_clubs'] as $index => $club) {
                        echo "    [$index] Club ID: " . $club['id'] . " | Name: " . $club['name'] . "\n";
                    }
                }
            }
        }
    }
    
    echo "\n";
    
    // 3. Kiểm tra API user-clubs/check-status cho từng membership
    echo "🔍 3. Test API user-clubs/check-status cho từng membership:\n";
    
    foreach ($allMemberships as $membership) {
        $testUrl = "https://api.lienketso.vn/public/api/user-clubs/check-status?club_id={$membership['club_id']}&user_id={$membership['user_id']}";
        echo "  Testing User ID: {$membership['user_id']}, Club ID: {$membership['club_id']}\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                echo "    ✅ Status: " . $data['data']['status'] . " | Role: " . $data['data']['role'] . " | Is Active: " . $data['data']['is_active'] . "\n";
            } else {
                echo "    ❌ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "    ❌ No response\n";
        }
    }
    
    echo "\n";
    
    // 4. So sánh dữ liệu database vs API response
    echo "🔍 4. So sánh dữ liệu Database vs API Response:\n";
    
    // Lấy user_id = 1 từ database
    $stmt = $pdo->prepare("SELECT * FROM user_clubs WHERE user_id = ?");
    $stmt->execute([1]);
    $dbMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Database memberships cho user_id = 1:\n";
    foreach ($dbMemberships as $membership) {
        echo "    - Club ID: " . $membership['club_id'] . " | Status: " . $membership['status'] . " | Role: " . $membership['role'] . "\n";
    }
    
    // So sánh với API response
    if (isset($data['data']['joined_clubs']) && is_array($data['data']['joined_clubs'])) {
        echo "  API joined_clubs cho user_id = 1:\n";
        foreach ($data['data']['joined_clubs'] as $club) {
            echo "    - Club ID: " . $club['id'] . " | Name: " . $club['name'] . "\n";
        }
        
        // Kiểm tra xem có match không
        echo "  So sánh:\n";
        foreach ($dbMemberships as $dbMembership) {
            $foundInApi = false;
            foreach ($data['data']['joined_clubs'] as $apiClub) {
                if ($apiClub['id'] == $dbMembership['club_id']) {
                    $foundInApi = true;
                    break;
                }
            }
            echo "    - Club ID " . $dbMembership['club_id'] . " (DB Status: " . $dbMembership['status'] . "): " . ($foundInApi ? "✅ Có trong API" : "❌ Không có trong API") . "\n";
        }
    }
    
    echo "\n";
    
    // 5. Kiểm tra logic render
    echo "🔍 5. Phân tích logic render:\n";
    
    $activeClubs = [];
    $pendingClubs = [];
    $rejectedClubs = [];
    
    foreach ($dbMemberships as $membership) {
        switch ($membership['status']) {
            case 'active':
                $activeClubs[] = $membership['club_id'];
                break;
            case 'pending':
                $pendingClubs[] = $membership['club_id'];
                break;
            case 'rejected':
                $rejectedClubs[] = $membership['club_id'];
                break;
        }
    }
    
    echo "  Theo database:\n";
    echo "    - Active clubs: " . implode(', ', $activeClubs) . " (Count: " . count($activeClubs) . ")\n";
    echo "    - Pending clubs: " . implode(', ', $pendingClubs) . " (Count: " . count($pendingClubs) . ")\n";
    echo "    - Rejected clubs: " . implode(', ', $rejectedClubs) . " (Count: " . count($rejectedClubs) . ")\n";
    
    echo "  Theo logic frontend:\n";
    echo "    - 'Câu lạc bộ đã tham gia' sẽ hiển thị: " . (count($activeClubs) > 0 ? count($activeClubs) . " clubs" : "Không có gì (vì tất cả đều pending)") . "\n";
    echo "    - 'Câu lạc bộ đang chờ duyệt' sẽ hiển thị: " . (count($pendingClubs) > 0 ? count($pendingClubs) . " clubs" : "Không có gì") . "\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n✅ Test hoàn tất\n";
?>
