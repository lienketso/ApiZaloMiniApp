<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
