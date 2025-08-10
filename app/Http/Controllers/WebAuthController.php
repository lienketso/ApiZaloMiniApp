<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class WebAuthController extends Controller
{
    /**
     * Hiển thị form đăng nhập
     */
    public function showLoginForm()
    {
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
            // Gọi API đăng nhập
            $response = Http::post(url('/api/auth/login'), [
                'phone' => $request->phone,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Lưu token vào session
                Session::put('auth_token', $data['token'] ?? null);
                Session::put('user', $data['user'] ?? null);
                
                return redirect('/dashboard')->with('success', 'Đăng nhập thành công!');
            } else {
                return back()->withErrors(['error' => 'Thông tin đăng nhập không chính xác']);
            }
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
