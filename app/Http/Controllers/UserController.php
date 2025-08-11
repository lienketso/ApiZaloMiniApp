<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User; // Added this import for the new method

class UserController extends Controller
{
    /**
     * Lấy thông tin profile
     */
    public function profile()
    {
        $user = Auth::user();

        // Tính toán stats
        $totalEvents = \App\Models\Event::count();
        $totalAttendance = $user->attendances()->count();
        $attendanceRate = $totalEvents > 0 ? round(($totalAttendance / $totalEvents) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'total_events' => $totalEvents,
                'total_attendance' => $totalAttendance,
                'attendance_rate' => $attendanceRate,
            ]),
        ]);
    }

    /**
     * Cập nhật profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $user->update($validated);

        // Tính toán stats
        $totalEvents = \App\Models\Event::count();
        $totalAttendance = $user->attendances()->count();
        $attendanceRate = $totalEvents > 0 ? round(($totalAttendance / $totalEvents) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => array_merge($user->toArray(), [
                'total_events' => $totalEvents,
                'total_attendance' => $totalAttendance,
                'attendance_rate' => $attendanceRate,
            ]),
        ]);
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string|min:8',
        ]);

        $user = Auth::user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        $user->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mật khẩu đã được thay đổi thành công'
        ]);
    }

    /**
     * Tạo hoặc cập nhật user từ ZMP SDK
     */
    public function createOrUpdateFromZMP(Request $request)
    {
        try {
            $validated = $request->validate([
                'zmp_id' => 'required|string',
                'name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'avatar' => 'nullable|url|max:500',
                'email' => 'nullable|email|max:255',
            ]);

            // Tìm user theo zmp_id (zalo_gid)
            $user = User::where('zalo_gid', $validated['zmp_id'])->first();

            if ($user) {
                // Cập nhật user hiện có
                $updateData = [];
                
                if (isset($validated['name']) && $validated['name']) {
                    $updateData['name'] = $validated['name'];
                }
                if (isset($validated['phone']) && $validated['phone']) {
                    $updateData['phone'] = $validated['phone'];
                }
                if (isset($validated['avatar']) && $validated['avatar']) {
                    $updateData['zalo_avatar'] = $validated['avatar'];
                }
                if (isset($validated['email']) && $validated['email']) {
                    $updateData['email'] = $validated['email'];
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                }

                $message = 'User đã được cập nhật thành công';
            } else {
                // Tạo user mới
                $userData = [
                    'zalo_gid' => $validated['zmp_id'],
                    'name' => $validated['name'] ?? 'User ' . substr($validated['zmp_id'], -4),
                    'phone' => $validated['phone'] ?? null,
                    'zalo_avatar' => $validated['avatar'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'role' => 'user',
                    'join_date' => now(),
                    'password' => Hash::make(Str::random(16)), // Tạo password ngẫu nhiên
                ];

                $user = User::create($userData);
                $message = 'User mới đã được tạo thành công';
            }

            // Tạo token cho user
            $token = $user->createToken('zmp-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'avatar' => $user->zalo_avatar,
                        'role' => $user->role,
                        'join_date' => $user->join_date,
                    ],
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo/cập nhật user: ' . $e->getMessage()
            ], 500);
        }
    }
}
