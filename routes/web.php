<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ClubController as AdminClubController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Models\ZaloToken;

Route::get('/', function () {
    return view('welcome');
});

// Auth routes for web interface
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::get('/register', [WebAuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [WebAuthController::class, 'register']);
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('super-admin')
    ->name('dashboard');

Route::middleware('super-admin')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('clubs', AdminClubController::class)->except(['show']);
    Route::resource('members', AdminMemberController::class)->except(['show']);
    Route::post('members/{member}/reset-password', [AdminMemberController::class, 'resetPassword'])
        ->name('members.reset-password');
});

// Web routes (nếu cần)
// Route::get('/dashboard', function () {
//     return view('dashboard');
// });


Route::get('/zalo-test-message', function () {
    $token = ZaloToken::first();
    if (!$token || empty($token->access_token)) {
        return response()->json(["error" => "Chưa có token, hãy chạy /zalo-refresh-token trước"]);
    }

    $accessToken = $token->access_token;

    $userId  = "5170627724267093288"; // thay bằng user_id thực tế (đã quan tâm OA)
    $message = "Xin chào, đây là tin nhắn test từ Laravel truy cap app zalo https://zalo.me/s/530119453891460352";

    $url = "https://openapi.zalo.me/v3.0/oa/message/cs";

    $payload = [
        "recipient" => [
            "user_id" => $userId
        ],
        "message" => [
            "text" => $message
        ]
    ];

    // Theo tài liệu: access_token để ở header
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'access_token' => $accessToken,
    ])->post($url, $payload);

    return $response->json();
});

Route::get('/zalo-refresh-token', function () {
    $appId     = config('services.zalo.app_id');       // từ config/services.php hoặc .env
    $appSecret = config('services.zalo.app_secret');

    $token = ZaloToken::first();
    if (!$token || empty($token->refresh_token)) {
        return response()->json(["error" => "Chưa có refresh_token trong DB. Hãy insert refresh_token ban đầu."], 400);
    }

    $url = "https://oauth.zaloapp.com/v4/oa/access_token";

    // GỌI API theo đúng doc: header 'secret_key' + body form-encoded
    $response = Http::withHeaders([
        'Content-Type' => 'application/x-www-form-urlencoded',
        'secret_key'   => $appSecret,
    ])->asForm()->post($url, [
        'app_id'        => $appId,
        'refresh_token' => $token->refresh_token,
        'grant_type'    => 'refresh_token',
    ]);

    $data = $response->json();

    // Debug: nếu muốn log chi tiết khi lỗi
    if (!$response->ok()) {
        \Log::error('Zalo refresh-token http error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
    }

    // Nếu API trả access_token thì cập nhật DB
    if (isset($data['access_token'])) {
        $token->update([
            'access_token'      => $data['access_token'],
            'refresh_token'     => $data['refresh_token'] ?? $token->refresh_token,
            'expires_in'        => $data['expires_in'] ?? null,
            'last_refreshed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // Trả về lỗi rõ ràng từ Zalo để bạn debug (ví dụ Invalid secret key)
    return response()->json([
        'success' => false,
        'http_status' => $response->status(),
        'zalo_response' => $data,
    ], 400);
});
