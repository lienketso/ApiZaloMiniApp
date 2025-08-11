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
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'No user authenticated',
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
})->middleware('auth:sanctum');

// Public routes
Route::get('/auth/check', function () {
    return response()->json([
        'message' => 'Auth check endpoint',
        'timestamp' => now(),
        'status' => 'success'
    ]);
});

// Zalo Auth routes
Route::post('/auth/zalo/auto-login', [ZaloAuthController::class, 'autoLogin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Members
    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members', [MemberController::class, 'store']);
    Route::get('/members/{id}', [MemberController::class, 'show']);
    Route::put('/members/{id}', [MemberController::class, 'update']);
    Route::delete('/members/{id}', [MemberController::class, 'destroy']);

    // Clubs
    Route::get('/clubs', [ClubController::class, 'index']);
    Route::post('/clubs', [ClubController::class, 'store']);
    Route::get('/clubs/{id}', [ClubController::class, 'show']);
    Route::put('/clubs/{id}', [ClubController::class, 'update']);
    Route::delete('/clubs/{id}', [ClubController::class, 'destroy']);

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
});
