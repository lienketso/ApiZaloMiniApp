<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function index(): JsonResponse
    {
        $attendance = Attendance::with(['event', 'user'])->get();

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string',
            'guests' => 'nullable|string'
        ]);

        // Kiểm tra xem đã có attendance record chưa
        $existing = Attendance::where('event_id', $validated['event_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            // Cập nhật record hiện tại
            $existing->update([
                'status' => $validated['status'],
                'check_in_time' => $validated['status'] === 'present' ? now() : null,
                'notes' => $validated['notes'],
                'guests' => $validated['guests']
            ]);

            $attendance = $existing;
        } else {
            // Tạo record mới
            $attendance = Attendance::create([
                'event_id' => $validated['event_id'],
                'user_id' => $validated['user_id'],
                'status' => $validated['status'],
                'check_in_time' => $validated['status'] === 'present' ? now() : null,
                'notes' => $validated['notes'],
                'guests' => $validated['guests']
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $attendance->load(['event', 'user']),
            'message' => 'Điểm danh thành công'
        ], 201);
    }

    public function show($id): JsonResponse
    {
        // Find attendance by ID instead of using route model binding
        $attendance = Attendance::find($id);
        
        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found'
            ], 404);
        }

        // Load relationships explicitly
        $attendance->load(['event', 'user']);

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|in:present,absent,late',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',
            'notes' => 'nullable|string',
            'guests' => 'nullable|string'
        ]);

        $attendance->update($validated);

        return response()->json([
            'success' => true,
            'data' => $attendance->load(['event', 'user']),
            'message' => 'Điểm danh đã được cập nhật thành công'
        ]);
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Điểm danh đã được xóa thành công'
        ]);
    }

    public function getByEvent(Event $event): JsonResponse
    {
        $attendance = $event->attendances()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }
}
