<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Nếu là API request, không redirect
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        // Nếu là web request, redirect đến login
        return route('login');
    }
}
