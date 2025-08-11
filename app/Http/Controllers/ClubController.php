<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
                    'message' => 'Club not found'
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
     * Show club by ID (alias for getClubInfo)
     */
    public function show($id)
    {
        return $this->getClubInfo($id);
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
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

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

            // Create user_club relationship with admin role
            $club->users()->attach($userId, [
                'role' => 'admin',
                'joined_date' => now(),
                'notes' => 'Club creator',
                'is_active' => true
            ]);

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
     * Get current user ID - handles both authenticated and mock data scenarios
     */
    private function getCurrentUserId()
    {
        \Log::info('ClubController::getCurrentUserId - Starting...');
        
        // Nếu user đã đăng nhập thực sự với Sanctum
        if (Auth::guard('sanctum')->check()) {
            $userId = Auth::guard('sanctum')->id();
            \Log::info('ClubController::getCurrentUserId - User authenticated with Sanctum', ['user_id' => $userId]);
            return $userId;
        }

        \Log::info('ClubController::getCurrentUserId - No Sanctum authentication, checking for mock user');
        
        // Nếu đang sử dụng mock data, tìm hoặc tạo user mặc định
        $mockUser = User::where('email', 'dev@example.com')->first();

        if (!$mockUser) {
            \Log::info('ClubController::getCurrentUserId - Creating mock user for development');
            // Tạo user mặc định cho development
            $mockUser = User::create([
                'name' => 'Dev User',
                'email' => 'dev@example.com',
                'password' => bcrypt('password'),
                'zalo_gid' => 'dev_zalo_gid',
                'role' => 'admin'
            ]);
            \Log::info('ClubController::getCurrentUserId - Mock user created', ['user_id' => $mockUser->id]);
        } else {
            \Log::info('ClubController::getCurrentUserId - Mock user found', ['user_id' => $mockUser->id]);
        }

        return $mockUser->id;
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
