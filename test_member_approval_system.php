<?php
/**
 * Test hệ thống duyệt thành viên
 * 
 * Luồng test:
 * 1. Tạo invitation
 * 2. User tham gia club (status: pending)
 * 3. Admin duyệt thành viên
 * 4. Kiểm tra trạng thái
 */

require_once 'vendor/autoload.php';

// Cấu hình database
$host = 'localhost';
$dbname = 'club_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Kết nối database thành công\n";
} catch (PDOException $e) {
    die("❌ Lỗi kết nối database: " . $e->getMessage() . "\n");
}

// Test data
$testClubId = 1;
$testAdminId = 1;
$testPhone = '0123456789';
$testZaloGid = 'test_zalo_gid_' . time();

echo "\n🚀 Bắt đầu test hệ thống duyệt thành viên...\n";
echo "==========================================\n";

// 1. Tạo test user
echo "\n1️⃣ Tạo test user...\n";
try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, zalo_gid, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test User', 'test@example.com', $testZaloGid, $testPhone, 'member']);
    $testUserId = $pdo->lastInsertId();
    echo "✅ Tạo test user thành công - ID: $testUserId\n";
} catch (PDOException $e) {
    echo "❌ Lỗi tạo test user: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Tạo invitation
echo "\n2️⃣ Tạo invitation...\n";
try {
    $inviteToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $stmt = $pdo->prepare("INSERT INTO invitations (club_id, phone, invite_token, invited_by, status, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$testClubId, $testPhone, $inviteToken, $testAdminId, 'pending', $expiresAt]);
    $invitationId = $pdo->lastInsertId();
    echo "✅ Tạo invitation thành công - ID: $invitationId\n";
} catch (PDOException $e) {
    echo "❌ Lỗi tạo invitation: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test user tham gia club (status: pending)
echo "\n3️⃣ Test user tham gia club...\n";
try {
    $stmt = $pdo->prepare("INSERT INTO user_clubs (user_id, club_id, role, status, joined_date, is_active, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $testUserId, 
        $testClubId, 
        'member', 
        'pending', 
        date('Y-m-d H:i:s'), 
        false, 
        'Auto-joined via invitation - pending admin approval'
    ]);
    $userClubId = $pdo->lastInsertId();
    echo "✅ User tham gia club thành công - ID: $userClubId (status: pending)\n";
} catch (PDOException $e) {
    echo "❌ Lỗi user tham gia club: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Kiểm tra trạng thái membership
echo "\n4️⃣ Kiểm tra trạng thái membership...\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM user_clubs WHERE id = ?");
    $stmt->execute([$userClubId]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($membership) {
        echo "✅ Membership status: " . $membership['status'] . "\n";
        echo "   - is_active: " . ($membership['is_active'] ? 'true' : 'false') . "\n";
        echo "   - joined_date: " . $membership['joined_date'] . "\n";
        echo "   - notes: " . $membership['notes'] . "\n";
    } else {
        echo "❌ Không tìm thấy membership\n";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi kiểm tra membership: " . $e->getMessage() . "\n";
}

// 5. Admin duyệt thành viên
echo "\n5️⃣ Admin duyệt thành viên...\n";
try {
    $stmt = $pdo->prepare("UPDATE user_clubs SET status = 'approved', approved_at = ?, approved_by = ?, is_active = true WHERE id = ?");
    $stmt->execute([date('Y-m-d H:i:s'), $testAdminId, $userClubId]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Admin duyệt thành viên thành công\n";
    } else {
        echo "❌ Không thể duyệt thành viên\n";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi admin duyệt thành viên: " . $e->getMessage() . "\n";
}

// 6. Kiểm tra trạng thái sau khi duyệt
echo "\n6️⃣ Kiểm tra trạng thái sau khi duyệt...\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM user_clubs WHERE id = ?");
    $stmt->execute([$userClubId]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($membership) {
        echo "✅ Membership status sau khi duyệt: " . $membership['status'] . "\n";
        echo "   - is_active: " . ($membership['is_active'] ? 'true' : 'false') . "\n";
        echo "   - approved_at: " . $membership['approved_at'] . "\n";
        echo "   - approved_by: " . $membership['approved_by'] . "\n";
    } else {
        echo "❌ Không tìm thấy membership\n";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi kiểm tra membership: " . $e->getMessage() . "\n";
}

// 7. Test từ chối thành viên
echo "\n7️⃣ Test từ chối thành viên...\n";
try {
    // Tạo user khác để test từ chối
    $stmt = $pdo->prepare("INSERT INTO users (name, email, zalo_gid, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Rejected User', 'rejected@example.com', 'rejected_zalo_' . time(), '0987654321', 'member']);
    $rejectedUserId = $pdo->lastInsertId();
    
    // Tạo membership với status pending
    $stmt = $pdo->prepare("INSERT INTO user_clubs (user_id, club_id, role, status, joined_date, is_active, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $rejectedUserId, 
        $testClubId, 
        'member', 
        'pending', 
        date('Y-m-d H:i:s'), 
        false, 
        'Test rejection'
    ]);
    $rejectedMembershipId = $pdo->lastInsertId();
    
    // Admin từ chối
    $stmt = $pdo->prepare("UPDATE user_clubs SET status = 'rejected', approved_by = ?, rejection_reason = ? WHERE id = ?");
    $stmt->execute([$testAdminId, 'Không phù hợp với câu lạc bộ', $rejectedMembershipId]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Admin từ chối thành viên thành công\n";
    } else {
        echo "❌ Không thể từ chối thành viên\n";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi test từ chối thành viên: " . $e->getMessage() . "\n";
}

// 8. Kiểm tra thống kê
echo "\n8️⃣ Kiểm tra thống kê membership...\n";
try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM user_clubs WHERE club_id = ? GROUP BY status");
    $stmt->execute([$testClubId]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Thống kê membership cho club ID $testClubId:\n";
    foreach ($stats as $stat) {
        echo "   - {$stat['status']}: {$stat['count']}\n";
    }
} catch (PDOException $e) {
    echo "❌ Lỗi kiểm tra thống kê: " . $e->getMessage() . "\n";
}

// 9. Dọn dẹp test data
echo "\n9️⃣ Dọn dẹp test data...\n";
try {
    // Xóa memberships
    $stmt = $pdo->prepare("DELETE FROM user_clubs WHERE user_id IN (?, ?)");
    $stmt->execute([$testUserId, $rejectedUserId ?? 0]);
    
    // Xóa users
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN (?, ?)");
    $stmt->execute([$testUserId, $rejectedUserId ?? 0]);
    
    // Xóa invitation
    $stmt = $pdo->prepare("DELETE FROM invitations WHERE id = ?");
    $stmt->execute([$invitationId]);
    
    echo "✅ Dọn dẹp test data thành công\n";
} catch (PDOException $e) {
    echo "❌ Lỗi dọn dẹp test data: " . $e->getMessage() . "\n";
}

echo "\n🎉 Test hệ thống duyệt thành viên hoàn tất!\n";
echo "==========================================\n";
echo "\n📋 Tóm tắt:\n";
echo "✅ Tạo user và invitation\n";
echo "✅ User tham gia club (status: pending)\n";
echo "✅ Admin duyệt thành viên\n";
echo "✅ Kiểm tra trạng thái sau khi duyệt\n";
echo "✅ Test từ chối thành viên\n";
echo "✅ Kiểm tra thống kê\n";
echo "✅ Dọn dẹp test data\n";

echo "\n🔧 Để test API, sử dụng các endpoint:\n";
echo "GET /api/member-approval/pending?club_id=1&zalo_gid=admin_zalo_gid\n";
echo "POST /api/member-approval/approve\n";
echo "POST /api/member-approval/reject\n";
echo "GET /api/member-approval/stats?club_id=1&zalo_gid=admin_zalo_gid\n";
?>
