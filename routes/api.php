<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FundTransactionController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ClubMembershipController;
use App\Http\Controllers\MemberApprovalController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\UserClubController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\ZaloAuthController;
use App\Http\Controllers\LeaderboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route để kiểm tra API
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
        'status' => 'success'
    ]);
});



// Test route để kiểm tra middleware auth:sanctum
Route::get('/test-auth', function (Request $request) {
    try {
        // Kiểm tra xem có token không
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'message' => 'No token provided',
                'timestamp' => now(),
                'status' => 'error'
            ], 401);
        }
        
        // Kiểm tra xem token có hợp lệ không
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Invalid token',
                'timestamp' => now(),
                'status' => 'error'
            ], 401);
        }
        
        return response()->json([
            'message' => 'Protected route accessed successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'timestamp' => now(),
            'status' => 'success'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error in protected route: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
})->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);

// Test route đơn giản với auth:sanctum để debug
Route::get('/test-simple-auth', function () {
    return response()->json([
        'message' => 'Simple protected route works!',
        'timestamp' => now(),
        'status' => 'success'
    ]);
})->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);

// Test route với middleware đơn giản hơn
Route::get('/test-basic-auth', function () {
    return response()->json([
        'message' => 'Basic auth route works!',
        'timestamp' => now(),
        'status' => 'success'
    ]);
})->middleware('auth');

// Test route với middleware auth:sanctum sử dụng cách khác
Route::get('/test-sanctum-auth', function () {
    return response()->json([
        'message' => 'Sanctum auth route works!',
        'timestamp' => now(),
        'status' => 'success'
    ]);
})->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);

// Test route với middleware auth:sanctum sử dụng cách khác
Route::get('/test-sanctum-auth-2', function () {
    return response()->json([
        'message' => 'Sanctum auth route 2 works!',
        'timestamp' => now(),
        'status' => 'success'
    ]);
})->middleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);

// Test routes không dùng Sanctum để debug
Route::get('/test-no-auth', function () {
    return response()->json([
        'message' => 'No auth required - API is working!',
        'timestamp' => now(),
        'status' => 'success',
        'test' => 'This route works without authentication'
    ]);
});

// Test route để kiểm tra database connection
Route::get('/test-db', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'message' => 'Database connection successful',
            'timestamp' => now(),
            'status' => 'success',
            'database' => \DB::connection()->getDatabaseName()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
});

// Test route để kiểm tra User model
Route::get('/test-users', function () {
    try {
        $users = \App\Models\User::take(5)->get();
        return response()->json([
            'message' => 'Users query successful',
            'timestamp' => now(),
            'status' => 'success',
            'count' => $users->count(),
            'users' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Users query failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
});

// Test route để kiểm tra Club model
Route::get('/test-clubs', function () {
    try {
        $clubs = \App\Models\Club::take(5)->get();
        return response()->json([
            'message' => 'Clubs query successful',
            'timestamp' => now(),
            'status' => 'success',
            'count' => $clubs->count(),
            'clubs' => $clubs
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Clubs query failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
});

// Test route để kiểm tra ClubController
Route::get('/test-club-controller', function () {
    try {
        return response()->json([
            'message' => 'ClubController test route working',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'ClubController test route failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
});

// Test route để kiểm tra ClubController trực tiếp (không qua middleware)
Route::get('/test-club-controller-direct', function () {
    try {
        $controller = new \App\Http\Controllers\ClubController();
        $result = $controller->test();
        return $result;
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'ClubController direct test failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error',
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test route để kiểm tra getCurrentUserId method
Route::get('/test-get-current-user-id', function () {
    try {
        $controller = new \App\Http\Controllers\ClubController();
        
        // Sử dụng reflection để gọi private method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getCurrentUserId');
        $method->setAccessible(true);
        
        $userId = $method->invoke($controller);
        
        return response()->json([
            'message' => 'getCurrentUserId test successful',
            'timestamp' => now(),
            'status' => 'success',
            'user_id' => $userId
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'getCurrentUserId test failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error',
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test route để kiểm tra việc tạo ClubController
Route::get('/test-create-controller', function () {
    try {
        return response()->json([
            'message' => 'About to create ClubController',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Test failed: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error'
        ], 500);
    }
});

// Test route để kiểm tra việc tạo ClubController instance
Route::get('/test-controller-instance', function () {
    try {
        $controller = new \App\Http\Controllers\ClubController();
        return response()->json([
            'message' => 'ClubController created successfully',
            'timestamp' => now(),
            'status' => 'success'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to create ClubController: ' . $e->getMessage(),
            'timestamp' => now(),
            'status' => 'error',
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Public routes
Route::get('/auth/check', function () {
    return response()->json([
        'message' => 'Auth check endpoint',
        'timestamp' => now(),
        'status' => 'success'
    ]);
});

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Zalo Auth routes
Route::post('/auth/zalo/auto-login', [ZaloAuthController::class, 'autoLogin']);

// Zalo Auth protected routes (không cần Sanctum)
Route::post('/auth/zalo/update-info', [ZaloAuthController::class, 'updateZaloInfo']);

// Club routes (không cần Sanctum - đã xác thực qua Zalo)
Route::get('/clubs', [ClubController::class, 'index']);
Route::post('/clubs', [ClubController::class, 'store']);
Route::post('/club/setup', [ClubController::class, 'setup']);
Route::post('/club/upload-logo', [ClubController::class, 'uploadLogo']);
Route::get('/clubs/user-clubs', [ClubController::class, 'getUserClubs']);
Route::get('/clubs/available', [ClubController::class, 'getAvailableClubs']);
Route::post('/clubs/join', [ClubController::class, 'joinClub']);
Route::post('/clubs/leave', [ClubController::class, 'leaveClub']);
Route::get('/clubs/status', [ClubController::class, 'checkClubStatus']);
Route::get('/clubs/user-count', [ClubController::class, 'getUserClubCount']);
Route::get('/clubs/test', [ClubController::class, 'test']);
Route::get('/clubs/{id}', [ClubController::class, 'show']);
Route::put('/clubs/{id}', [ClubController::class, 'update']);
Route::delete('/clubs/{id}', [ClubController::class, 'destroy']);

// Members - Đã thay thế bằng UserClub
// Route::get('/members', [MemberController::class, 'index']);
// Route::post('/members', [MemberController::class, 'store']);
// Route::get('/members/{id}', [MemberController::class, 'show']);
// Route::put('/members/{id}', [MemberController::class, 'update']);
// Route::delete('/members/{id}', [MemberController::class, 'destroy']);

// Club Members
Route::get('/user-clubs/check-status', [UserClubController::class, 'checkStatus']);
Route::get('/user-clubs', [UserClubController::class, 'index']);
Route::post('/user-clubs', [UserClubController::class, 'store']);
Route::get('/user-clubs/{id}', [UserClubController::class, 'show']);
Route::put('/user-clubs/{id}', [UserClubController::class, 'update']);
Route::delete('/user-clubs/{id}', [UserClubController::class, 'destroy']);

// Invitations
Route::post('/invitations', [InvitationController::class, 'store']);
Route::post('/invitations/accept', [InvitationController::class, 'accept']);
Route::get('/invitations', [InvitationController::class, 'index']);
Route::delete('/invitations/{id}', [InvitationController::class, 'destroy']);

// Club Membership (không cần ZNS)
Route::post('/club-membership/join', [ClubMembershipController::class, 'joinClub']);
Route::post('/club-membership/check', [ClubMembershipController::class, 'checkMembership']);
Route::get('/club-membership/available-clubs', [ClubMembershipController::class, 'getAvailableClubs']);
Route::post('/club-membership/test', [ClubMembershipController::class, 'test']);

// Member Approval (Admin only)
Route::get('/member-approval/pending', [MemberApprovalController::class, 'getPendingMembers']);
Route::post('/member-approval/approve', [MemberApprovalController::class, 'approveMember']);
Route::post('/member-approval/reject', [MemberApprovalController::class, 'rejectMember']);
Route::get('/member-approval/stats', [MemberApprovalController::class, 'getMembershipStats']);

// Test ZNS API
Route::get('/test-zns', function () {
    try {
        $znsService = new \App\Services\ZaloNotificationService();
        $result = $znsService->testConnection();
        
        return response()->json([
            'success' => true,
            'message' => 'ZNS API test completed',
            'result' => $result
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'ZNS API test failed: ' . $e->getMessage()
        ], 500);
    }
});

// Events
Route::get('/events', [EventController::class, 'index']);
Route::post('/events', [EventController::class, 'store']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);
Route::get('/events/{id}/attendance', [AttendanceController::class, 'getByEvent']);

// Attendance
Route::get('/attendance', [AttendanceController::class, 'index']);
Route::post('/attendance', [AttendanceController::class, 'store']);
Route::get('/attendance/{id}', [AttendanceController::class, 'show']);
Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy']);

// Fund Transactions
Route::get('/fund-transactions', [FundTransactionController::class, 'index']);
Route::post('/fund-transactions', [FundTransactionController::class, 'store']);
Route::get('/fund-transactions/{id}', [FundTransactionController::class, 'show']);
Route::put('/fund-transactions/{id}', [FundTransactionController::class, 'update']);
Route::delete('/fund-transactions/{id}', [FundTransactionController::class, 'destroy']);
Route::post('/fund-transactions/{id}', [FundTransactionController::class, 'store']); // Method override support
Route::put('/fund-transactions/{id}/status', [FundTransactionController::class, 'updateTransactionStatus']);
Route::get('/fund-transactions/status/{status}', [FundTransactionController::class, 'getTransactionsByStatus']);
Route::get('/fund-transactions/stats', [FundTransactionController::class, 'getFundStats']);

// Matches
Route::get('/matches', [MatchController::class, 'index']);
Route::post('/matches', [MatchController::class, 'store']);
Route::get('/matches/{id}', [MatchController::class, 'show']);
Route::put('/matches/{id}', [MatchController::class, 'update']);
Route::delete('/matches/{id}', [MatchController::class, 'destroy']);
Route::put('/matches/{id}/teams', [MatchController::class, 'updateTeams']);
Route::get('/matches/club-members', [MatchController::class, 'getClubMembers']);
Route::post('/matches/{id}/start', [MatchController::class, 'startMatch']);
Route::put('/matches/{id}/result', [MatchController::class, 'updateResult']);
Route::put('/matches/{id}/cancel', [MatchController::class, 'cancelMatch']);

// User profile - Sử dụng zalo_gid để xác thực
Route::get('/user/profile', [UserController::class, 'profile']);
Route::put('/user/profile', [UserController::class, 'updateProfile']);

// API để lấy club members
Route::get('/club-members', function (Request $request) {
    try {
        $clubId = $request->query('club_id');
        if (!$clubId) {
            return response()->json([
                'success' => false,
                'message' => 'club_id is required'
            ], 400);
        }
        
        // Lấy users thuộc club
        $users = \App\Models\User::whereHas('clubs', function($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->select('id', 'name', 'avatar', 'phone')
            ->get()
            ->map(function ($user) use ($clubId) {
                $userClub = $user->clubs->where('id', $clubId)->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'role' => $userClub->pivot->role ?? 'member',
                    'joined_date' => $userClub->pivot->created_at ?? now()
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Leaderboard
Route::get('/leaderboard/wins', [LeaderboardController::class, 'getWinsLeaderboard']);
Route::get('/leaderboard/fund-contributions', [LeaderboardController::class, 'getFundContributionsLeaderboard']);
Route::get('/leaderboard/attendance', [LeaderboardController::class, 'getAttendanceLeaderboard']);

// Debug route để kiểm tra teams
Route::get('/debug/teams/{matchId}', function ($matchId) {
    try {
        $teams = DB::table('teams')
            ->where('match_id', $matchId)
            ->get();
            
        $teamPlayers = DB::table('team_players')
            ->join('users', 'team_players.user_id', '=', 'users.id')
            ->whereIn('team_players.team_id', $teams->pluck('id'))
            ->select('team_players.team_id', 'users.id as user_id', 'users.name', 'users.avatar')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'teams' => $teams,
                'team_players' => $teamPlayers
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Subscription routes
Route::get('/subscription/plans', [App\Http\Controllers\SubscriptionController::class, 'getPlans']);
Route::get('/subscription/club/{clubId}', [App\Http\Controllers\SubscriptionController::class, 'getClubSubscriptionInfo']);
Route::post('/subscription/club/{clubId}/trial', [App\Http\Controllers\SubscriptionController::class, 'startTrial']);
Route::post('/subscription/club/{clubId}/activate', [App\Http\Controllers\SubscriptionController::class, 'activateSubscription']);
Route::post('/subscription/club/{clubId}/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancelSubscription']);
Route::post('/subscription/club/{clubId}/check-permission', [App\Http\Controllers\SubscriptionController::class, 'checkActionPermission']);
