<?php
/**
 * Test script cho hệ thống mời thành viên
 * Chạy: php test_invitation_system.php
 */

require_once 'vendor/autoload.php';

use App\Models\Invitation;
use App\Models\Club;
use App\Models\User;
use App\Models\UserClub;
use App\Services\ZaloNotificationService;

echo "=== TEST HỆ THỐNG MỜI THÀNH VIÊN ===\n\n";

try {
    // 1. Test tạo invitation
    echo "1. Testing tạo invitation...\n";
    
    // Tạo test data
    $club = Club::first();
    if (!$club) {
        echo "❌ Không có club nào trong database\n";
        exit(1);
    }
    
    $admin = User::first();
    if (!$admin) {
        echo "❌ Không có user nào trong database\n";
        exit(1);
    }
    
    echo "✅ Club: " . $club->name . "\n";
    echo "✅ Admin: " . $admin->name . "\n";
    
    // Tạo invitation
    $invitation = Invitation::create([
        'club_id' => $club->id,
        'phone' => '0123456789',
        'invited_by' => $admin->id,
    ]);
    
    echo "✅ Invitation created: " . $invitation->invite_token . "\n";
    echo "✅ Expires at: " . $invitation->expires_at . "\n";
    
    // 2. Test ZNS service
    echo "\n2. Testing ZNS service...\n";
    
    $znsService = new ZaloNotificationService();
    
    // Test connection
    $connectionResult = $znsService->testConnection();
    if ($connectionResult['success']) {
        echo "✅ ZNS API connection: " . $connectionResult['message'] . "\n";
    } else {
        echo "❌ ZNS API connection failed: " . $connectionResult['message'] . "\n";
    }
    
    // Test sending notification (chỉ test nếu có config)
    if (config('services.zalo.access_token')) {
        echo "\n3. Testing ZNS notification sending...\n";
        
        $znsResult = $znsService->sendInvitationNotification(
            '0123456789',
            $club->name,
            'https://example.com/invite/' . $invitation->invite_token
        );
        
        if ($znsResult['success']) {
            echo "✅ ZNS sent successfully\n";
        } else {
            echo "❌ ZNS failed: " . $znsResult['message'] . "\n";
        }
    } else {
        echo "⚠️ ZNS access token not configured, skipping notification test\n";
    }
    
    // 3. Test invitation validation
    echo "\n4. Testing invitation validation...\n";
    
    if ($invitation->canBeUsed()) {
        echo "✅ Invitation can be used\n";
    } else {
        echo "❌ Invitation cannot be used\n";
    }
    
    if (!$invitation->isExpired()) {
        echo "✅ Invitation not expired\n";
    } else {
        echo "❌ Invitation expired\n";
    }
    
    // 4. Test accept invitation
    echo "\n5. Testing invitation acceptance...\n";
    
    // Tạo test user
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'zalo_gid' => 'test_zalo_gid_' . time(),
        'password' => bcrypt('password'),
    ]);
    
    echo "✅ Test user created: " . $testUser->name . "\n";
    
    // Kiểm tra xem user đã là thành viên chưa
    $existingMembership = UserClub::where('user_id', $testUser->id)
        ->where('club_id', $club->id)
        ->first();
    
    if ($existingMembership) {
        echo "⚠️ User already a member of this club\n";
    } else {
        echo "✅ User not yet a member\n";
        
        // Thêm user vào club
        $userClub = UserClub::create([
            'user_id' => $testUser->id,
            'club_id' => $club->id,
            'role' => 'member',
            'joined_date' => now(),
            'is_active' => true,
        ]);
        
        echo "✅ User added to club successfully\n";
        
        // Đánh dấu invitation đã được chấp nhận
        $invitation->markAsAccepted();
        echo "✅ Invitation marked as accepted\n";
    }
    
    // 5. Cleanup
    echo "\n6. Cleaning up test data...\n";
    
    // Xóa test user
    if (isset($userClub)) {
        $userClub->delete();
        echo "✅ UserClub deleted\n";
    }
    
    $testUser->delete();
    echo "✅ Test user deleted\n";
    
    $invitation->delete();
    echo "✅ Test invitation deleted\n";
    
    echo "\n=== TEST HOÀN TẤT THÀNH CÔNG! ===\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
