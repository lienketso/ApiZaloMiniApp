<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserClub;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy club_id từ request hoặc từ user hiện tại
            $clubId = $request->input('club_id') ?? $request->query('club_id');
            
            // Pagination parameters
            $limit = (int)($request->input('limit') ?? $request->query('limit') ?? 10);
            $offset = (int)($request->input('offset') ?? $request->query('offset') ?? 0);
            
            $eventsQuery = Event::with(['attendances.user', 'club']);
            
            if (!$clubId) {
                // Nếu không có club_id, trả về tất cả events (fallback)
                $totalCount = $eventsQuery->count();
                $events = $eventsQuery
                    ->orderBy('start_date', 'desc')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();
            } else {
                // Filter events theo club_id
                $totalCount = $eventsQuery->where('club_id', $clubId)->count();
                $events = $eventsQuery
                    ->where('club_id', $clubId)
                    ->orderBy('start_date', 'desc')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $events,
                'total' => $totalCount,
                'per_page' => $limit,
                'current_page' => ($offset / $limit) + 1,
                'has_more' => ($offset + $limit) < $totalCount
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
            $user = $this->getCurrentUser();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (strtolower($user->role ?? '') !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa sự kiện'
                ], 403);
            }

            DB::beginTransaction();

            // Xóa các bản ghi liên quan để tránh dữ liệu mồ côi
            $eventRole = $this->getUserClubRole($user->id, $event->club_id);

            if (!in_array(strtolower($eventRole ?? ''), ['admin', 'owner'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa sự kiện của câu lạc bộ này'
                ], 403);
            }

            DB::beginTransaction();

            $event->attendances()->delete();

            $event->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sự kiện đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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

    private function getCurrentUser(): ?User
    {
        try {
            $zaloGid = request()->input('zalo_gid') ?? request()->header('X-Zalo-GID');

            if (!$zaloGid) {
                Log::warning('EventController::getCurrentUser - Missing zalo_gid');
                return null;
            }

            $user = User::where('zalo_gid', $zaloGid)->first();

            if (!$user) {
                Log::warning('EventController::getCurrentUser - User not found', ['zalo_gid' => $zaloGid]);
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('EventController::getCurrentUser - Error:', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function getUserClubRole(int $userId, ?int $clubId): ?string
    {
        if (!$clubId) {
            return null;
        }

        $membership = UserClub::where('user_id', $userId)
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->first();

        return $membership?->role;
    }
}
