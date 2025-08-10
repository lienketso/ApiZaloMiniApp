<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;

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
Route::get('/dashboard', function () {
    if (!session('auth_token')) {
        return redirect('/login');
    }
    return view('dashboard');
})->name('dashboard');

// Web routes (nếu cần)
// Route::get('/dashboard', function () {
//     return view('dashboard');
// });
