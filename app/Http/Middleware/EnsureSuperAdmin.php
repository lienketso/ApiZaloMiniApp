<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $token = session('auth_token');
        $user = session('user');

        if (!$token || !$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để truy cập bảng điều khiển.');
        }

        $role = Str::of($user['role'] ?? '')->lower();
        $allowedRoles = collect(['super_admin', 'super-admin', 'superadmin']);

        if (!$allowedRoles->contains($role)) {
            return redirect()->route('login')->with('error', 'Bạn không có quyền truy cập khu vực này.');
        }

        return $next($request);
    }
}

