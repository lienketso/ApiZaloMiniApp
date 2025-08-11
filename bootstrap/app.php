<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApiAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        Laravel\Sanctum\SanctumServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Tắt CSRF cho API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Thêm CORS middleware
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        
        // Đảm bảo API routes không bị redirect
        $middleware->preventRequestsDuringMaintenance();
        
        // Đăng ký middleware aliases
        $middleware->alias([
            'api.auth' => ApiAuth::class,
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
