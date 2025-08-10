<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class ZaloAuthController extends Controller
{
    /**
     * Đăng nhập bằng Zalo GID
     */
    public function login(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string',
        ]);

        $zaloGid = $request->zalo_gid;

        // Tìm user theo Zalo GID
        $user = User::where('zalo_gid', $zaloGid)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được đăng ký',
            ], 404);
        }

        // Tạo token
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ]);
    }

    /**
     * Đăng ký user mới bằng Zalo GID
     */
    public function register(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string|unique:users,zalo_gid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'zalo_avatar' => 'nullable|url|max:500',
        ]);

        // Tạo user mới
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'zalo_gid' => $request->zalo_gid,
            'zalo_name' => $request->zalo_name,
            'zalo_avatar' => $request->zalo_avatar,
            'role' => 'Member',
            'join_date' => now(),
            'password' => Hash::make(Str::random(16)), // Random password
        ]);

        // Tạo token
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ], 201);
    }


    /**
     * Login hoặc Register tự động
     */
    public function loginOrRegister(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'zalo_avatar' => 'nullable|url|max:500',
        ]);

        $zaloGid = $request->zalo_gid;

        // Tìm user theo Zalo GID
        $user = User::where('zalo_gid', $zaloGid)->first();

        if (!$user) {
            // Tạo user mới nếu chưa tồn tại
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'zalo_gid' => $zaloGid,
                'zalo_name' => $request->zalo_name,
                'zalo_avatar' => $request->zalo_avatar,
                'role' => 'Member',
                'join_date' => now(),
                'password' => Hash::make(Str::random(16)),
            ]);
        } else {
            // Cập nhật thông tin nếu user đã tồn tại
            $user->update([
                'name' => $request->name,
                'zalo_name' => $request->zalo_name,
                'zalo_avatar' => $request->zalo_avatar,
            ]);
        }

        // Tạo token mới
        $user->tokens()->delete(); // Xóa token cũ
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ]);
    }

    /**
     * Tính toán stats cho user
     */
    private function calculateUserStats(User $user)
    {
        $totalEvents = \App\Models\Event::count();
        $totalAttendance = $user->attendances()->count();
        $attendanceRate = $totalEvents > 0 ? round(($totalAttendance / $totalEvents) * 100) : 0;

        return [
            'total_events' => $totalEvents,
            'total_attendance' => $totalAttendance,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
        ]);
    }

    /**
     * Kiểm tra trạng thái xác thực
     */
    public function checkAuth(Request $request)
    {
        try {
            // Lấy token từ header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No token provided',
                    'authenticated' => false
                ], 401);
            }

            // Kiểm tra token có hợp lệ không
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'authenticated' => false
                ], 401);
            }

            // Tính toán stats
            $stats = $this->calculateUserStats($user);

            return response()->json([
                'success' => true,
                'message' => 'User authenticated',
                'authenticated' => true,
                'data' => array_merge($user->toArray(), $stats)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking authentication',
                'authenticated' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto login - kiểm tra và tạo token mới nếu cần
     */
    public function autoLogin(Request $request)
    {
        try {
            $request->validate([
                'zalo_gid' => 'required|string',
            ]);

            $zaloGid = $request->zalo_gid;

            // Tìm user theo Zalo GID
            $user = User::where('zalo_gid', $zaloGid)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản chưa được đăng ký',
                    'authenticated' => false
                ], 404);
            }

            // Tạo token mới
            $user->tokens()->delete(); // Xóa token cũ
            $newToken = $user->createToken('zalo-mini-app')->plainTextToken;

            // Tính toán stats
            $stats = $this->calculateUserStats($user);

            return response()->json([
                'success' => true,
                'message' => 'Auto login successful',
                'authenticated' => true,
                'data' => [
                    'token' => $newToken,
                    'user' => array_merge($user->toArray(), $stats)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during auto login',
                'authenticated' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
