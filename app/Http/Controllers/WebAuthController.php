<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
class WebAuthController extends Controller
{
    /**
     * Hiển thị form đăng nhập
     */
    public function showLoginForm()
    {
        // dd(Hash::make('123456'));   
        return view('auth.login');
    }

    /**
     * Hiển thị form đăng ký
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('phone', $request->phone)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors(['error' => 'Thông tin đăng nhập không chính xác']);
            }

            $role = Str::of($user->role ?? '')->lower();
            $allowedRoles = collect(['super_admin', 'super-admin', 'superadmin']);

            if (!$allowedRoles->contains($role)) {
                return back()->withErrors(['error' => 'Bạn không có quyền truy cập khu vực quản trị Super Admin.']);
            }

            $sessionToken = Str::random(60);

            $user->forceFill([
                'last_login_at' => now(),
                'last_seen_at' => now(),
            ])->save();

            Session::put('auth_token', $sessionToken);
            Session::put('user', [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'email' => $user->email,
            ]);

            return redirect()->route('dashboard')->with('success', 'Đăng nhập thành công!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Có lỗi xảy ra, vui lòng thử lại']);
        }
    }

    /**
     * Xử lý đăng ký
     */
    public function register(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|unique:users,phone',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        try {
            // Gọi API đăng ký
            $response = Http::post(url('/api/auth/zalo/register'), [
                'phone' => $request->phone,
                'name' => $request->name,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                return redirect('/login')->with('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            } else {
                $errors = $response->json();
                return back()->withErrors($errors);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Có lỗi xảy ra, vui lòng thử lại']);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout()
    {
        Session::forget(['auth_token', 'user']);
        return redirect('/')->with('success', 'Đã đăng xuất thành công!');
    }
}
