<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Models\ClubMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClubController extends Controller
{
    /**
     * Get club information
     */
    public function index()
    {
        try {
            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $club = Club::where('created_by', $userId)->first();

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa có câu lạc bộ nào. Vui lòng tạo câu lạc bộ trước.',
                    'code' => 'NO_CLUB_FOUND',
                    'action_required' => 'create_club',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $club
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving club information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new club (alias for setup)
     */
    public function store(Request $request)
    {
        return $this->setup($request);
    }

    /**
     * Get user's clubs (both as member and admin)
     */
    public function getUserClubs()
    {
        try {
            \Log::info('ClubController::getUserClubs - Starting...');
            
            $userId = $this->getCurrentUserId();
            \Log::info('ClubController::getUserClubs - Current user ID:', ['user_id' => $userId]);

            if (!$userId) {
                \Log::warning('ClubController::getUserClubs - No user ID found');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = User::with(['clubs' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])->find($userId);

            if (!$user) {
                \Log::warning('ClubController::getUserClubs - User not found', ['user_id' => $userId]);
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            \Log::info('ClubController::getUserClubs - User found', [
                'user_id' => $userId,
                'user_name' => $user->name,
                'clubs_count' => $user->clubs->count()
            ]);

            // Luôn trả về danh sách clubs (có thể rỗng)
            return response()->json([
                'success' => true,
                'data' => $user->clubs
            ]);
            
        } catch (\Exception $e) {
            \Log::error('ClubController::getUserClubs - Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user clubs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get club information by ID
     */
    public function getClubInfo($clubId)
    {
        try {
            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $club = Club::with(['users' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->find($clubId);

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club not found'
                ], 404);
            }

            // Check if user is member of this club
            $userClub = $club->users->first();
            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a member of this club'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $club
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving club information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show club by ID - xử lý trường hợp user chưa có club
     */
    public function show($id)
    {
        try {
            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Kiểm tra xem user có club nào không
            $userClubs = Club::where('created_by', $userId)->count();
            
            if ($userClubs === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa có câu lạc bộ nào. Vui lòng tạo câu lạc bộ trước.',
                    'code' => 'NO_CLUB_FOUND',
                    'action_required' => 'create_club'
                ], 404);
            }

            // Nếu có club, gọi method getClubInfo
            return $this->getClubInfo($id);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi truy cập thông tin câu lạc bộ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup club for the first time
     */
    public function setup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sport' => 'required|string|max:255',
                'logo' => 'nullable|string',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'description' => 'nullable|string',
                'bank_name' => 'nullable|string|max:255',
                'account_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $this->getCurrentUserId();

            if (!$userId) {
                \Log::error('ClubController::setup - No user ID found');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated or error getting user ID'
                ], 401);
            }

            // Bắt đầu transaction để đảm bảo tính nhất quán
            DB::beginTransaction();
            
            try {
                // Create new club
                $club = Club::create([
                    'name' => $request->name,
                    'sport' => $request->sport,
                    'logo' => $request->logo,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'description' => $request->description,
                    'bank_name' => $request->bank_name,
                    'account_name' => $request->account_name,
                    'account_number' => $request->account_number,
                    'is_setup' => true,
                    'created_by' => $userId
                ]);

                // Lấy thông tin user để tạo member
                $user = User::find($userId);
                
                // Create member từ thông tin user
                $member = Member::create([
                    'club_id' => $club->id,
                    'name' => $user->name ?? 'Thành viên mới',
                    'phone' => $user->phone ?? $request->phone ?? '0123456789',
                    'email' => $user->email ?? $request->email ?? 'member@club.com',
                    'avatar' => $user->avatar ?? null,
                    'joined_date' => now()
                ]);

                // Liên kết member với club qua bảng club_member với role admin
                ClubMember::create([
                    'club_id' => $club->id,
                    'member_id' => $member->id,
                    'role' => 'admin',
                    'joined_date' => now(),
                    'notes' => 'Club creator - Admin',
                    'is_active' => true
                ]);

                // Create user_club relationship với admin role (để quản lý quyền sở hữu)
                $club->users()->attach($userId, [
                    'role' => 'admin',
                    'joined_date' => now(),
                    'notes' => 'Club owner',
                    'is_active' => true
                ]);

                // Commit transaction
                DB::commit();
                
            } catch (\Exception $e) {
                // Rollback nếu có lỗi
                DB::rollback();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Club setup successfully',
                'data' => $club
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error setting up club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update club information
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'sport' => 'sometimes|required|string|max:255',
                'logo' => 'nullable|string',
                'address' => 'sometimes|required|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'description' => 'nullable|string',
                'bank_name' => 'nullable|string|max:255',
                'account_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $club = Club::where('created_by', $userId)->first();

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club not found'
                ], 404);
            }

            $club->update($request->only([
                'name', 'sport', 'logo', 'address', 'phone', 'email', 'description', 'bank_name', 'account_name', 'account_number'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Club updated successfully',
                'data' => $club
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete club
     */
    public function destroy($id)
    {
        try {
            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $club = Club::where('created_by', $userId)->find($id);

            if (!$club) {
                return response()->json([
                    'success' => false,
                    'message' => 'Club not found or you are not authorized to delete it'
                ], 404);
            }

            // Delete club (this will also delete related records due to foreign key constraints)
            $club->delete();

            return response()->json([
                'success' => true,
                'message' => 'Club deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái club của user
     */
    public function checkClubStatus()
    {
        try {
            $userId = $this->getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $club = Club::where('created_by', $userId)->first();
            $userClubs = User::with('clubs')->find($userId);

            $response = [
                'success' => true,
                'has_own_club' => false,
                'is_member_of_clubs' => false,
                'total_clubs' => 0,
                'action_required' => null
            ];

            if ($club) {
                $response['has_own_club'] = true;
                $response['own_club'] = $club;
            }

            if ($userClubs && $userClubs->clubs->count() > 0) {
                $response['is_member_of_clubs'] = true;
                $response['total_clubs'] = $userClubs->clubs->count();
                $response['clubs'] = $userClubs->clubs;
            }

            // Xác định action cần thiết
            if (!$response['has_own_club'] && !$response['is_member_of_clubs']) {
                $response['action_required'] = 'create_or_join_club';
                $response['message'] = 'Bạn chưa có câu lạc bộ nào. Vui lòng tạo hoặc tham gia câu lạc bộ.';
            } elseif (!$response['has_own_club'] && $response['is_member_of_clubs']) {
                $response['action_required'] = 'create_own_club';
                $response['message'] = 'Bạn đã tham gia câu lạc bộ nhưng chưa có câu lạc bộ riêng.';
            } else {
                $response['message'] = 'Bạn đã có câu lạc bộ riêng.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi kiểm tra trạng thái club',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test method để debug
     */
    public function test()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'ClubController test method working',
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in test method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user ID - sử dụng mock user cho development
     */
    private function getCurrentUserId()
    {
        try {
            // Tìm hoặc tạo user mặc định cho development
            $mockUser = User::where('zalo_gid', 'test_zalo_456')->first();

            if (!$mockUser) {
                // Tạo user mặc định cho development
                $mockUser = User::create([
                    'name' => 'Dev User',
                    'email' => 'dev@example.com',
                    'password' => bcrypt('password'),
                    'zalo_gid' => 'dev_zalo_gid',
                    'role' => 'admin'
                ]);
            }

            return $mockUser->id;
            
        } catch (\Exception $e) {
            // Trả về null nếu có lỗi, để method gọi có thể xử lý
            return null;
        }
    }

    /**
     * Upload club logo
     */
    public function uploadLogo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('logo');
            $fileName = 'club_logo_' . time() . '.' . $file->getClientOriginalExtension();

            // Store in public/uploads/clubs directory
            $file->move(public_path('uploads/clubs'), $fileName);

            // Get the public URL
            $url = '/uploads/clubs/' . $fileName;

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'data' => [
                    'url' => $url,
                    'filename' => $fileName
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
