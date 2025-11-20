<?php

namespace App\Http\Controllers;

use App\Models\UserClub;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
            
            // Debug: Log tất cả thông tin request
            \Log::info('UserClubController::index - Full Request debug:', [
                'club_id_from_input' => $request->input('club_id'),
                'club_id_from_query' => $request->query('club_id'),
                'club_id_final' => $clubId,
                'all_input' => $request->all(),
                'query_string' => $request->getQueryString(),
                'headers' => $request->headers->all(),
                'user_authenticated' => $request->user() ? 'yes' : 'no',
                'user_id' => $request->user() ? $request->user()->id : null
            ]);
            
            // Nếu không có club_id, lấy club đầu tiên của user hiện tại
            if (!$clubId) {
                // Lấy zalo_gid từ request
                $zaloGid = $request->input('zalo_gid') ?? $request->header('X-Zalo-GID');
                
                if ($zaloGid) {
                    \Log::info('UserClubController::index - Searching for user with zalo_gid:', [
                        'zalo_gid' => $zaloGid
                    ]);
                    
                    // Tìm user theo zalo_gid
                    $user = User::where('zalo_gid', $zaloGid)->first();
                    
                    if ($user) {
                        \Log::info('UserClubController::index - Found user, looking for user clubs:', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'user_email' => $user->email,
                            'zalo_gid' => $zaloGid
                        ]);
                        
                        $userClub = UserClub::where('user_id', $user->id)
                            ->where('is_active', true)
                            ->with('club')
                            ->first();
                        
                        \Log::info('UserClubController::index - UserClub query result:', [
                            'user_club_found' => $userClub ? 'yes' : 'no',
                            'user_club_id' => $userClub ? $userClub->id : null,
                            'user_club_club_id' => $userClub ? $userClub->club_id : null,
                            'club_loaded' => $userClub && $userClub->club ? 'yes' : 'no',
                            'club_name' => $userClub && $userClub->club ? $userClub->club->name : null
                        ]);
                        
                        if ($userClub && isset($userClub->club_id)) {
                            $clubId = $userClub->club_id;
                            \Log::info('UserClubController::index - Found user club:', [
                                'club_id' => $clubId,
                                'user_club_id' => $userClub->id
                            ]);
                        } else {
                            \Log::warning('UserClubController::index - No user club found for user:', [
                                'user_id' => $user->id
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => 'Người dùng chưa tham gia club nào',
                                'data' => [],
                                'total' => 0
                            ], 404);
                        }
                    } else {
                        \Log::warning('UserClubController::index - No user found with zalo_gid:', [
                            'zalo_gid' => $zaloGid
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Không tìm thấy người dùng với zalo_gid này',
                            'data' => [],
                            'total' => 0
                        ], 404);
                    }
                } else {
                    \Log::warning('UserClubController::index - No zalo_gid provided');
                    return response()->json([
                        'success' => false,
                        'message' => 'Thiếu thông tin xác thực (zalo_gid)',
                        'data' => []
                    ], 400);
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
                } else {
                    \Log::error('UserClubController::index - No clubs exist in system');
                    return response()->json([
                        'success' => false,
                        'message' => 'Hệ thống chưa có club nào',
                        'data' => []
                    ], 404);
                }
            }

            if (!$clubId) {
                \Log::error('UserClubController::index - No club found at all');
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy club',
                    'data' => [],
                    'total' => 0
                ], 404);
            }

            \Log::info('UserClubController::index - Loading members for club:', ['club_id' => $clubId]);

            // Lấy thành viên từ bảng user_clubs
            $statusFilter = $request->input('status') ?? $request->query('status');
            
            // Pagination parameters
            $limit = (int)($request->input('limit') ?? $request->query('limit') ?? 10);
            $offset = (int)($request->input('offset') ?? $request->query('offset') ?? 0);
            
            $userClubsQuery = UserClub::where('club_id', $clubId)
                ->with(['user', 'club']);
            
            // Nếu có filter status, áp dụng filter
            if ($statusFilter === 'rejected') {
                // Lấy rejected members (không filter is_active vì rejected thường có is_active = false)
                $userClubsQuery->where('status', 'rejected');
            } else if ($statusFilter === 'pending') {
                // Lấy pending members (không filter is_active)
                $userClubsQuery->where('status', 'pending');
            } else if ($statusFilter === 'active') {
                // Lấy active members (đã được duyệt và active)
                $userClubsQuery->where('status', 'active')
                    ->where('is_active', true);
            } else {
                // Mặc định chỉ lấy active members (đã được duyệt)
                $userClubsQuery->where('status', 'active')
                    ->where('is_active', true);
            }
            
            // Get total count trước khi paginate
            $totalCount = $userClubsQuery->count();
            
            // Apply pagination
            $userClubs = $userClubsQuery
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            \Log::info('UserClubController::index - Found user clubs:', ['count' => $userClubs->count()]);

            // Nếu không có thành viên nào, trả về array rỗng
            if ($userClubs->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Không có thành viên nào trong club',
                    'total' => 0
                ]);
            }

            // Transform data để frontend dễ sử dụng
            $members = $userClubs->map(function ($userClub) {
                try {
                    // Kiểm tra xem user có tồn tại không
                    if (!$userClub->user) {
                        \Log::warning('UserClubController::index - UserClub has no user:', [
                            'user_club_id' => $userClub->id,
                            'user_id' => $userClub->user_id
                        ]);
                        return null; // Bỏ qua user club không có user
                    }
                    
                    return [
                        'id' => $userClub->id,
                        'user_id' => $userClub->user_id, // Thêm user_id để frontend có thể map
                        'name' => $userClub->user->name ?? 'Không xác định',
                        'phone' => $userClub->user->phone ?? null,
                        'email' => $userClub->user->email ?? null,
                        'gender' => $userClub->user->gender ?? null,
                        'birthday' => $userClub->user->birthday ?? null,
                        'role' => $userClub->role,
                        'club_role' => $userClub->role,
                        'joined_date' => $userClub->joined_date,
                        'created_at' => $userClub->created_at,
                        'updated_at' => $userClub->updated_at,
                        'notes' => $userClub->notes,
                        'avatar' => $userClub->user->avatar ?? null, // Avatar từ user
                        'zalo_avatar' => $userClub->user->zalo_avatar ?? null, // Zalo avatar từ user (trực tiếp ở level member)
                        'is_active' => $userClub->is_active,
                        'status' => $userClub->status ?? 'active', // Thêm status để frontend có thể filter
                        // Thêm thông tin user để frontend dễ sử dụng
                        'user' => [
                            'id' => $userClub->user->id,
                            'name' => $userClub->user->name,
                            'email' => $userClub->user->email,
                            'avatar' => $userClub->user->avatar ?? null,
                            'zalo_avatar' => $userClub->user->zalo_avatar ?? null,
                        ]
                    ];
                } catch (\Exception $e) {
                    \Log::error('UserClubController::index - Error in transform:', [
                        'user_club_id' => $userClub->id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'line' => $e->getLine()
                    ]);
                    return null;
                }
            })->filter(function ($member) {
                return $member !== null; // Lọc bỏ các member null
            })->values(); // Reset array keys

            return response()->json([
                'success' => true,
                'data' => $members,
                'message' => 'Tải danh sách thành viên thành công',
                'total' => $totalCount,
                'per_page' => $limit,
                'current_page' => ($offset / $limit) + 1,
                'has_more' => ($offset + $limit) < $totalCount
            ]);

        } catch (\Exception $e) {
            \Log::error('UserClubController::index - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải danh sách thành viên: ' . $e->getMessage(),
                'data' => []
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
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'club_id' => 'required|exists:clubs,id',
            'club_role' => 'required|in:member,admin,guest',
            'joined_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,active,rejected',
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
                    'gender' => $request->gender,
                    'birthday' => $request->birthday,
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
            $status = $request->status ?? 'active'; // Mặc định là active nếu không có
            $isActive = ($status === 'active'); // is_active = true nếu status là active
            
            $userClub = UserClub::create([
                'club_id' => $request->club_id,
                'user_id' => $user->id,
                'role' => $request->club_role,
                'joined_date' => $request->joined_date ?? now(),
                'notes' => $request->notes,
                'status' => $status,
                'is_active' => $isActive,
            ]);

            // Load relationships
            $userClub->load(['user', 'club']);

            // Transform data để frontend dễ sử dụng
            $memberData = [
                'id' => $userClub->id,
                'name' => $userClub->user->name,
                'phone' => $userClub->user->phone,
                'email' => $userClub->user->email,
                'gender' => $userClub->user->gender,
                'birthday' => $userClub->user->birthday,
                'role' => $userClub->role,
                'club_role' => $userClub->role,
                'joined_date' => $userClub->joined_date,
                'created_at' => $userClub->created_at,
                'updated_at' => $userClub->updated_at,
                'notes' => $userClub->user->notes,
                'avatar' => null,
                'is_active' => $userClub->is_active,
                'status' => $userClub->status ?? 'active',
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
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'club_role' => 'nullable|in:member,admin,guest',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'status' => 'nullable|in:pending,active,rejected',
        ]);

        try {
            $userClub = UserClub::findOrFail($id);
            
            // Cập nhật thông tin user nếu có
            if ($request->has('name') || $request->has('phone') || $request->has('email') || $request->has('gender') || $request->has('birthday')) {
                $user = $userClub->user;
                $user->update($request->only(['name', 'phone', 'email', 'gender', 'birthday']));
            }

            // Cập nhật thông tin user club
            $updateData = $request->only(['notes', 'is_active', 'status']);
            
            // Map club_role thành role (vì frontend gửi club_role nhưng database dùng role)
            if ($request->has('club_role')) {
                $updateData['role'] = $request->club_role;
            }
            
            // Nếu có status, cập nhật is_active tương ứng
            if ($request->has('status')) {
                $updateData['is_active'] = ($request->status === 'active');
            }
            
            $userClub->update($updateData);

            // Load relationships
            $userClub->load(['user', 'club']);

            // Transform data
            $memberData = [
                'id' => $userClub->id,
                'name' => $userClub->user->name,
                'phone' => $userClub->user->phone,
                'email' => $userClub->user->email,
                'gender' => $userClub->user->gender,
                'birthday' => $userClub->user->birthday,
                'role' => $userClub->role,
                'club_role' => $userClub->role,
                'joined_date' => $userClub->joined_date,
                'created_at' => $userClub->created_at,
                'updated_at' => $userClub->updated_at,
                'notes' => $userClub->notes,
                'avatar' => null,
                'is_active' => $userClub->is_active,
                'status' => $userClub->status ?? 'active',
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

    /**
     * Kiểm tra status membership của user với club
     */
    public function checkStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'club_id' => 'required|integer|exists:clubs,id',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $clubId = $request->input('club_id');
            $userId = $request->input('user_id');

            // Tìm membership trong bảng user_clubs
            $membership = UserClub::where('club_id', $clubId)
                ->where('user_id', $userId)
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'User chưa có membership với club này',
                    'data' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin membership thành công',
                'data' => [
                    'id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'club_id' => $membership->club_id,
                    'role' => $membership->role,
                    'status' => $membership->status,
                    'joined_date' => $membership->joined_date,
                    'is_active' => $membership->is_active,
                    'notes' => $membership->notes,
                    'approved_at' => $membership->approved_at,
                    'approved_by' => $membership->approved_by,
                    'rejection_reason' => $membership->rejection_reason
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error checking membership status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra status membership',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
