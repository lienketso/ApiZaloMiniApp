<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::with(['attendances.member'])->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1'
        ]);

        $event = Event::create($validated);

        return response()->json([
            'success' => true,
            'data' => $event->load(['attendances.member']),
            'message' => 'Sự kiện đã được tạo thành công'
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load(['attendances.member']);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled'
        ]);

        $event->update($validated);

        return response()->json([
            'success' => true,
            'data' => $event->load(['attendances.member']),
            'message' => 'Sự kiện đã được cập nhật thành công'
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sự kiện đã được xóa thành công'
        ]);
    }
}
