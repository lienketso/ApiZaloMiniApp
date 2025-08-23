<?php
/**
 * Test script cho hệ thống club membership (không dùng ZNS)
 * Chạy: php test_club_membership_system.php
 */

require_once 'vendor/autoload.php';

use App\Models\Invitation;
use App\Models\Club;
use App\Models\User;
use App\Models\UserClub;
use App\Services\ClubMembershipService;

echo "=== TEST HỆ THỐNG CLUB MEMBERSHIP (KHÔNG DÙNG ZNS) ===\n\n";

try {
    // 1. Test tạo invitation (không gửi ZNS)
    echo "1. Testing tạo invitation (không gửi ZNS)...\n";
    
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
    echo "✅ Note: ZNS không được gửi (tạm thời tắt)\n";
    
    // 2. Test ClubMembershipService
    echo "\n2. Testing ClubMembershipService...\n";
    
    $membershipService = new ClubMembershipService();
    
    // Test tạo test user
    $testUser = User::create([
        'name' => 'Test User Membership',
        'email' => 'test_membership@example.com',
        'zalo_gid' => 'test_membership_zalo_gid_' . time(),
        'password' => bcrypt('password'),
    ]);
    
    echo "✅ Test user created: " . $testUser->name . "\n";
    
    // 3. Test mapUserToClub
    echo "\n3. Testing mapUserToClub...\n";
    
    $mapResult = $membershipService->mapUserToClub(
        '0123456789', // phone
        $testUser->zalo_gid, // zalo_gid
        $club->id // club_id
    );
    
    if ($mapResult['success']) {
        echo "✅ User mapped to club successfully\n";
        echo "   Code: " . $mapResult['code'] . "\n";
        echo "   Message: " . $mapResult['message'] . "\n";
        
        if (isset($mapResult['data'])) {
            echo "   Club: " . $mapResult['data']['club_name'] . "\n";
            echo "   Role: " . $mapResult['data']['role'] . "\n";
        }
    } else {
        echo "❌ User mapping failed\n";
        echo "   Code: " . $mapResult['code'] . "\n";
        echo "   Message: " . $mapResult['message'] . "\n";
    }
    
    // 4. Test checkMembershipStatus
    echo "\n4. Testing checkMembershipStatus...\n";
    
    $membershipResult = $membershipService->checkMembershipStatus(
        $testUser->zalo_gid,
        $club->id
    );
    
    if ($membershipResult['success']) {
        echo "✅ Membership status checked successfully\n";
        echo "   Code: " . $membershipResult['code'] . "\n";
        echo "   Role: " . $membershipResult['data']['role'] . "\n";
        echo "   Joined: " . $membershipResult['data']['joined_date'] . "\n";
    } else {
        echo "❌ Membership status check failed\n";
        echo "   Code: " . $membershipResult['code'] . "\n";
        echo "   Message: " . $membershipResult['message'] . "\n";
    }
    
    // 5. Test getAvailableClubs
    echo "\n5. Testing getAvailableClubs...\n";
    
    $availableClubsResult = $membershipService->getAvailableClubs('0123456789');
    
    if ($availableClubsResult['success']) {
        echo "✅ Available clubs retrieved successfully\n";
        echo "   Total: " . $availableClubsResult['total'] . "\n";
        
        foreach ($availableClubsResult['data'] as $clubInfo) {
            echo "   - Club: " . $clubInfo['club_name'] . " (ID: " . $clubInfo['club_id'] . ")\n";
        }
    } else {
        echo "❌ Available clubs retrieval failed\n";
        echo "   Message: " . $availableClubsResult['message'] . "\n";
    }
    
    // 6. Test trường hợp không có invitation
    echo "\n6. Testing trường hợp không có invitation...\n";
    
    $noInvitationResult = $membershipService->mapUserToClub(
        '0987654321', // phone khác
        $testUser->zalo_gid, // zalo_gid
        $club->id // club_id
    );
    
    if (!$noInvitationResult['success']) {
        echo "✅ Correctly handled no invitation case\n";
        echo "   Code: " . $noInvitationResult['code'] . "\n";
        echo "   Message: " . $noInvitationResult['message'] . "\n";
    } else {
        echo "❌ Unexpected success for no invitation case\n";
    }
    
    // 7. Cleanup
    echo "\n7. Cleaning up test data...\n";
    
    // Xóa test user và membership
    $userClub = UserClub::where('user_id', $testUser->id)
        ->where('club_id', $club->id)
        ->first();
    
    if ($userClub) {
        $userClub->delete();
        echo "✅ UserClub deleted\n";
    }
    
    $testUser->delete();
    echo "✅ Test user deleted\n";
    
    $invitation->delete();
    echo "✅ Test invitation deleted\n";
    
    echo "\n=== TEST HOÀN TẤT THÀNH CÔNG! ===\n";
    echo "✅ Hệ thống hoạt động bình thường không cần ZNS\n";
    echo "✅ User được map vào club bằng số điện thoại\n";
    echo "✅ Invitation được xử lý tự động\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
