<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy club_id từ request hoặc từ user hiện tại
            $clubId = $request->input('club_id') ?? $request->query('club_id');
            
            if (!$clubId) {
                // Nếu không có club_id, trả về tất cả events (fallback)
                $events = Event::with(['attendances.user', 'club'])->get();
            } else {
                // Filter events theo club_id
                $events = Event::with(['attendances.user', 'club'])
                    ->where('club_id', $clubId)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            Log::error('EventController::index - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_club_id' => $clubId ?? 'not_provided'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'location' => 'nullable|string|max:255',
                'max_participants' => 'nullable|integer|min:1',
                'club_id' => 'required|integer|exists:clubs,id'
            ]);

            $event = Event::create($validated);

            return response()->json([
                'success' => true,
                'data' => $event->load(['attendances.user', 'club']),
                'message' => 'Sự kiện đã được tạo thành công'
            ], 201);
        } catch (\Exception $e) {
            Log::error('EventController::store - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Event $event): JsonResponse
    {
        try {
            $event->load(['attendances.user', 'club']);

            return response()->json([
                'success' => true,
                'data' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('EventController::show - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after:start_date',
                'location' => 'nullable|string|max:255',
                'max_participants' => 'nullable|integer|min:1',
                'status' => 'sometimes|in:upcoming,ongoing,completed,cancelled',
                'club_id' => 'sometimes|required|integer|exists:clubs,id'
            ]);

            $event->update($validated);

            return response()->json([
                'success' => true,
                'data' => $event->load(['attendances.user', 'club']),
                'message' => 'Sự kiện đã được cập nhật thành công'
            ]);
        } catch (\Exception $e) {
            Log::error('EventController::update - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Event $event): JsonResponse
    {
        try {
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sự kiện đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            Log::error('EventController::destroy - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
