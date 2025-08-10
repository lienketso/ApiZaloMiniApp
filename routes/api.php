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

// Public routes
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()
    ]);
});

// Zalo Auth routes (public)
Route::post('/auth/zalo/login', [ZaloAuthController::class, 'login']);
Route::post('/auth/zalo/auto-login', [ZaloAuthController::class, 'autoLogin']);
Route::post('/auth/zalo/register', [ZaloAuthController::class, 'register']);
Route::post('/auth/zalo/login-or-register', [ZaloAuthController::class, 'loginOrRegister']);

// Check auth route (public)
Route::get('/auth/check', [ZaloAuthController::class, 'checkAuth']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [ZaloAuthController::class, 'logout']);
    Route::get('/auth/profile', [UserController::class, 'profile']);
    Route::put('/auth/profile', [UserController::class, 'updateProfile']);
    
    // Member
    Route::apiResource('members', MemberController::class);

    // Events
    Route::apiResource('events', EventController::class);

    // Attendance
    Route::apiResource('attendance', AttendanceController::class);
    Route::get('attendance/event/{event}', [AttendanceController::class, 'getByEvent']);
    
    // Fund
    Route::apiResource('fund-transactions', FundTransactionController::class);
    Route::get('fund-stats', [FundTransactionController::class, 'getFundStats']);
    
    // Club
    Route::get('/club', [ClubController::class, 'index']);
    Route::get('/club/user-clubs', [ClubController::class, 'getUserClubs']);
    Route::post('/club/setup', [ClubController::class, 'setup']);
    Route::put('/club', [ClubController::class, 'update']);

    // Club Member
    Route::post('/club-members', [ClubMemberController::class, 'addMemberToClub']);
    Route::put('/club-members/{id}', [ClubMemberController::class, 'updateMemberRole']);
    Route::delete('/club-members/{id}', [ClubMemberController::class, 'removeMemberFromClub']);
    Route::get('/club-members/club/{clubId}', [ClubMemberController::class, 'getClubMembers']);
    Route::get('/club-members/member/{memberId}', [ClubMemberController::class, 'getMemberClubs']);
    Route::get('/club-members/roles', [ClubMemberController::class, 'getRoleOptions']);

    // Matches
    Route::get('/matches', [MatchController::class, 'index']);
    Route::post('/matches', [MatchController::class, 'store']);
    Route::put('/matches/{id}', [MatchController::class, 'update']);
    Route::delete('/matches/{id}', [MatchController::class, 'destroy']);
    Route::put('/matches/{id}/teams', [MatchController::class, 'updateTeams']);
    Route::post('/matches/{id}/start', [MatchController::class, 'startMatch']);
    Route::put('/matches/{id}/result', [MatchController::class, 'updateResult']);
    Route::get('/matches/members', [MatchController::class, 'getClubMembers']);
});
