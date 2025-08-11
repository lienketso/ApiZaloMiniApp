<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ZaloAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FundTransactionController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubMemberController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\AuthController;

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

// Test route để kiểm tra ClubMember model
Route::get('/test-club-member-model', function () {
    try {
        $clubMembers = \App\Models\ClubMember::take(3)->get();
        return response()->json([
            'message' => 'ClubMember model test successful',
            'timestamp' => now(),
            'status' => 'success',
            'count' => $clubMembers->count(),
            'club_members' => $clubMembers
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'ClubMember model test failed: ' . $e->getMessage(),
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
Route::get('/clubs/test', [ClubController::class, 'test']);
Route::get('/clubs/{id}', [ClubController::class, 'show']);
Route::put('/clubs/{id}', [ClubController::class, 'update']);
Route::delete('/clubs/{id}', [ClubController::class, 'destroy']);

// Members
Route::get('/members', [MemberController::class, 'index']);
Route::post('/members', [MemberController::class, 'store']);
Route::get('/members/{id}', [MemberController::class, 'show']);
Route::put('/members/{id}', [MemberController::class, 'update']);
Route::delete('/members/{id}', [MemberController::class, 'destroy']);

// Club Members
Route::get('/club-members', [ClubMemberController::class, 'index']);
Route::post('/club-members', [ClubMemberController::class, 'store']);
Route::get('/club-members/{id}', [ClubMemberController::class, 'show']);
Route::put('/club-members/{id}', [ClubMemberController::class, 'update']);
Route::delete('/club-members/{id}', [ClubMemberController::class, 'destroy']);

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

// Matches
Route::get('/matches', [MatchController::class, 'index']);
Route::post('/matches', [MatchController::class, 'store']);
Route::get('/matches/{id}', [MatchController::class, 'show']);
Route::put('/matches/{id}', [MatchController::class, 'update']);
Route::delete('/matches/{id}', [MatchController::class, 'destroy']);

// User profile
Route::get('/user/profile', [UserController::class, 'profile']);
Route::put('/user/profile', [UserController::class, 'updateProfile']);
