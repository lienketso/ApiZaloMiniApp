<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
}
