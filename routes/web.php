<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Web routes (nếu cần)
// Route::get('/dashboard', function () {
//     return view('dashboard');
// });
