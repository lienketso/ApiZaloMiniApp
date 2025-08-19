<?php

namespace App\Http\Controllers;

use App\Models\UserClub;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserClubController extends Controller
{
    /**
     * Display a listing of club members
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy club_id từ request query hoặc body
            $clubId = $request->input('club_id') ?? $request->query('club_id');
            
            // Debug: Log thông tin
            \Log::info('UserClubController::index - Request data:', [
                'club_id_from_request' => $clubId,
                'user_authenticated' => $request->user() ? 'yes' : 'no',
                'user_id' => $request->user() ? $request->user()->id : null
            ]);
            
            // Nếu không có club_id, lấy club đầu tiên của user hiện tại
            if (!$clubId) {
                // Lấy zalo_gid từ request
                $zaloGid = $request->input('zalo_gid') ?? $request->header('X-Zalo-GID');
                
                if ($zaloGid) {
                    // Tìm user theo zalo_gid
                    $user = User::where('zalo_gid', $zaloGid)->first();
                    
                    if ($user) {
                        \Log::info('UserClubController::index - Looking for user clubs for user:', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'zalo_gid' => $zaloGid
                        ]);
                        
                        $userClub = UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($userClub) {
                            $clubId = $userClub->club_id;
                            \Log::info('UserClubController::index - Found user club:', [
                                'club_id' => $clubId,
                                'club_name' => $userClub->club->name ?? 'unknown'
                            ]);
                        } else {
                            \Log::warning('UserClubController::index - No user club found for user:', [
                                'user_id' => $user->id
                            ]);
                        }
                    } else {
                        \Log::warning('UserClubController::index - No user found with zalo_gid:', [
                            'zalo_gid' => $zaloGid
                        ]);
                    }
                } else {
                    \Log::warning('UserClubController::index - No zalo_gid provided');
                }
            }

            // Nếu vẫn không có club_id, lấy club đầu tiên có sẵn (fallback)
            if (!$clubId) {
                $firstClub = Club::first();
                if ($firstClub) {
                    $clubId = $firstClub->id;
                    \Log::info('UserClubController::index - Using fallback club:', [
                        'club_id' => $clubId,
                        'club_name' => $firstClub->name
                    ]);
                }
            }

            if (!$clubId) {
                \Log::error('UserClubController::index - No club found at all');
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy club'
                ], 404);
            }

            \Log::info('UserClubController::index - Loading members for club:', ['club_id' => $clubId]);

            // Lấy thành viên từ bảng user_clubs
            $userClubs = UserClub::where('club_id', $clubId)
                ->where('is_active', true)
                ->with(['user', 'club'])
                ->get();

            \Log::info('UserClubController::index - Found user clubs:', ['count' => $userClubs->count()]);

            // Transform data để frontend dễ sử dụng
            $members = $userClubs->map(function ($userClub) {
                return [
                    'id' => $userClub->id,
                    'name' => $userClub->user->name ?? 'Không xác định',
                    'phone' => $userClub->user->phone ?? null,
                    'email' => $userClub->user->email ?? null,
                    'role' => $userClub->role,
                    'club_role' => $userClub->role,
                    'joined_date' => $userClub->joined_date,
                    'created_at' => $userClub->created_at,
                    'updated_at' => $userClub->updated_at,
                    'notes' => $userClub->notes,
                    'avatar' => null, // Có thể thêm avatar sau
                    'is_active' => $userClub->is_active,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $members
            ]);

        } catch (\Exception $e) {
            \Log::error('UserClubController::index - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created club member
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
            'club_id' => 'required|exists:clubs,id',
            'club_role' => 'required|in:member,admin,guest',
            'joined_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Tìm user dựa trên phone hoặc email
            $user = null;
            if ($request->phone) {
                $user = User::where('phone', $request->phone)->first();
            } elseif ($request->email) {
                $user = User::where('email', $request->email)->first();
            }

            // Nếu không tìm thấy user, tạo user mới
            if (!$user) {
                $user = User::create([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'password' => bcrypt('default_password'), // Có thể thay đổi sau
                ]);
            }

            // Kiểm tra xem user đã là thành viên của club này chưa
            $existingMembership = UserClub::where('club_id', $request->club_id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thành viên đã tồn tại trong câu lạc bộ này'
                ], 400);
            }

            // Tạo user club mới (sử dụng bảng user_clubs)
            $userClub = UserClub::create([
                'club_id' => $request->club_id,
                'user_id' => $user->id,
                'role' => $request->club_role,
                'joined_date' => $request->joined_date ?? now(),
                'notes' => $request->notes,
                'is_active' => true,
            ]);

            // Load relationships
            $userClub->load(['user', 'club']);

            // Transform data để frontend dễ sử dụng
            $memberData = [
                'id' => $userClub->id,
                'name' => $userClub->user->name,
                'phone' => $userClub->user->phone,
                'email' => $userClub->user->email,
                'role' => $userClub->role,
                'club_role' => $userClub->role,
                'joined_date' => $userClub->joined_date,
                'created_at' => $userClub->created_at,
                'updated_at' => $userClub->updated_at,
                'notes' => $userClub->notes,
                'avatar' => null,
                'is_active' => $userClub->is_active,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Thêm thành viên vào câu lạc bộ thành công',
                'data' => $memberData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified club member
     */
    public function show($id): JsonResponse
    {
        try {
            $userClub = UserClub::with(['user', 'club'])->findOrFail($id);
            
            $memberData = [
                'id' => $userClub->id,
                'name' => $userClub->user->name,
                'phone' => $userClub->user->phone,
                'email' => $userClub->user->email,
                'role' => $userClub->role,
                'club_role' => $userClub->role,
                'joined_date' => $userClub->joined_date,
                'created_at' => $userClub->created_at,
                'updated_at' => $userClub->updated_at,
                'notes' => $userClub->notes,
                'avatar' => null,
                'is_active' => $userClub->is_active,
            ];

            return response()->json([
                'success' => true,
                'data' => $memberData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified club member
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
            'club_role' => 'nullable|in:member,admin,guest',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $userClub = UserClub::findOrFail($id);
            
            // Cập nhật thông tin user nếu có
            if ($request->has('name') || $request->has('phone') || $request->has('email')) {
                $user = $userClub->user;
                $user->update($request->only(['name', 'phone', 'email']));
            }

            // Cập nhật thông tin user club
            $userClub->update($request->only(['club_role', 'notes', 'is_active']));

            // Load relationships
            $userClub->load(['user', 'club']);

            // Transform data
            $memberData = [
                'id' => $userClub->id,
                'name' => $userClub->user->name,
                'phone' => $userClub->user->phone,
                'email' => $userClub->user->email,
                'role' => $userClub->role,
                'club_role' => $userClub->role,
                'joined_date' => $userClub->joined_date,
                'created_at' => $userClub->created_at,
                'updated_at' => $userClub->updated_at,
                'notes' => $userClub->notes,
                'avatar' => null,
                'is_active' => $userClub->is_active,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành viên thành công',
                'data' => $memberData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified club member
     */
    public function destroy($id): JsonResponse
    {
        try {
            $userClub = UserClub::findOrFail($id);
            $userClub->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa thành viên khỏi câu lạc bộ thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
