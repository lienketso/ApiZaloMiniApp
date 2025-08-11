<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Added Log facade

class ZaloAuthController extends Controller
{
    /**
     * Đăng nhập bằng Zalo GID
     */
    public function login(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string',
        ]);

        $zaloGid = $request->zalo_gid;

        // Tìm user theo Zalo GID
        $user = User::where('zalo_gid', $zaloGid)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được đăng ký',
            ], 404);
        }

        // Tạo token
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ]);
    }

    /**
     * Đăng ký user mới bằng Zalo GID
     */
    public function register(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string|unique:users,zalo_gid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'zalo_avatar' => 'nullable|url|max:500',
        ]);

        // Tạo user mới
        $userData = [
            'name' => $request->name,
            'phone' => $request->phone,
            'zalo_gid' => $request->zalo_gid,
            'zalo_name' => $request->zalo_name,
            'zalo_avatar' => $request->zalo_avatar,
            'role' => 'Member',
            'join_date' => now(),
            'password' => Hash::make(Str::random(16)), // Random password
        ];
        
        // Chỉ thêm email nếu có giá trị
        if (!empty($request->email)) {
            $userData['email'] = $request->email;
        } else {
            // Tạo email tạm thời từ zalo_gid nếu không có email
            $userData['email'] = 'zalo_' . $request->zalo_gid . '@temp.com';
        }
        
        $user = User::create($userData);

        // Tạo token
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ], 201);
    }


    /**
     * Login hoặc Register tự động
     */
    public function loginOrRegister(Request $request)
    {
        $request->validate([
            'zalo_gid' => 'required|string',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'zalo_name' => 'nullable|string|max:255',
            'zalo_avatar' => 'nullable|url|max:500',
        ]);

        $zaloGid = $request->zalo_gid;

        // Tìm user theo Zalo GID
        $user = User::where('zalo_gid', $zaloGid)->first();

        if (!$user) {
            // Tạo user mới nếu chưa tồn tại
            $userData = [
                'name' => $request->name,
                'phone' => $request->phone,
                'zalo_gid' => $zaloGid,
                'zalo_name' => $request->zalo_name,
                'zalo_avatar' => $request->zalo_avatar,
                'role' => 'Member',
                'join_date' => now(),
                'password' => Hash::make(Str::random(16)),
            ];
            
            // Chỉ thêm email nếu có giá trị
            if (!empty($request->email)) {
                $userData['email'] = $request->email;
            } else {
                // Tạo email tạm thời từ zalo_gid nếu không có email
                $userData['email'] = 'zalo_' . $zaloGid . '@temp.com';
            }
            
            $user = User::create($userData);
        } else {
            // Cập nhật thông tin nếu user đã tồn tại
            $user->update([
                'name' => $request->name,
                'zalo_name' => $request->zalo_name,
                'zalo_avatar' => $request->zalo_avatar,
            ]);
        }

        // Tạo token mới
        $user->tokens()->delete(); // Xóa token cũ
        $token = $user->createToken('zalo-mini-app')->plainTextToken;

        // Tính toán stats
        $stats = $this->calculateUserStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => array_merge($user->toArray(), $stats),
            ],
        ]);
    }

    /**
     * Tính toán stats cho user
     */
    private function calculateUserStats(User $user)
    {
        try {
            $totalEvents = \App\Models\Event::count();
            
            // Trả về stats mặc định cho user mới
            return [
                'total_events' => $totalEvents,
                'total_attendance' => 0,
                'attendance_rate' => 0,
            ];
        } catch (\Exception $e) {
            // Trả về stats mặc định nếu có lỗi
            return [
                'total_events' => 0,
                'total_attendance' => 0,
                'attendance_rate' => 0,
            ];
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
        ]);
    }

    /**
     * Kiểm tra trạng thái xác thực
     */
    public function checkAuth(Request $request)
    {
        try {
            // Lấy token từ header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No token provided',
                    'authenticated' => false
                ], 401);
            }

            // Kiểm tra token có hợp lệ không
            $user = Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'authenticated' => false
                ], 401);
            }

            // Tính toán stats
            $stats = $this->calculateUserStats($user);

            return response()->json([
                'success' => true,
                'message' => 'User authenticated',
                'authenticated' => true,
                'data' => array_merge($user->toArray(), $stats)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking authentication',
                'authenticated' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto login - ZMP SDK đã xác thực user, chỉ cần lấy thông tin và tạo/cập nhật tài khoản
     */
    public function autoLogin(Request $request)
    {
        try {
            Log::info('ZaloAuthController::autoLogin - Starting with data:', $request->all());
            
            $request->validate([
                'zalo_gid' => 'required|string',
            ]);

            $zaloGid = $request->zalo_gid;
            Log::info('ZaloAuthController::autoLogin - Zalo GID:', ['zalo_gid' => $zaloGid]);

            // Tìm user theo Zalo GID
            $user = User::where('zalo_gid', $zaloGid)->first();

            if (!$user) {
                Log::info('ZaloAuthController::autoLogin - User not found, creating new user');
                
                // Tạo user mới với thông tin từ ZMP SDK
                $userData = [
                    'zalo_gid' => $zaloGid,
                    'name' => 'Zalo User ' . substr($zaloGid, -6), // Tên mặc định với 6 ký tự cuối của zalo_gid
                    'role' => 'Member',
                    'join_date' => now(),
                    'password' => Hash::make(Str::random(16)),
                    'email' => 'zalo_' . $zaloGid . '@temp.com', // Email tạm thời
                ];
                
                Log::info('ZaloAuthController::autoLogin - Creating user with data:', $userData);
                
                try {
                    $user = User::create($userData);
                    Log::info('ZaloAuthController::autoLogin - User created successfully:', ['user_id' => $user->id]);
                } catch (\Exception $createError) {
                    Log::error('ZaloAuthController::autoLogin - Error creating user:', [
                        'error' => $createError->getMessage(),
                        'trace' => $createError->getTraceAsString()
                    ]);
                    throw $createError;
                }
            } else {
                Log::info('ZaloAuthController::autoLogin - User found:', ['user_id' => $user->id]);
                
                // Cập nhật thông tin cơ bản nếu cần
                $updateData = [];
                if (empty($user->name) || $user->name === 'Zalo User') {
                    $updateData['name'] = 'Zalo User ' . substr($zaloGid, -6);
                }
                
                if (!empty($updateData)) {
                    $user->update($updateData);
                    Log::info('ZaloAuthController::autoLogin - User updated:', $updateData);
                }
            }

            // Tạo token mới
            $user->tokens()->delete(); // Xóa token cũ
            $newToken = $user->createToken('zmp-sdk')->plainTextToken;
            Log::info('ZaloAuthController::autoLogin - Token created:', ['token_prefix' => substr($newToken, 0, 10) . '...']);

            // Tính toán stats
            $stats = $this->calculateUserStats($user);

            $response = [
                'success' => true,
                'message' => $user->wasRecentlyCreated ? 'Tài khoản mới được tạo và đăng nhập thành công' : 'Đăng nhập thành công',
                'authenticated' => true,
                'is_new_user' => $user->wasRecentlyCreated,
                'data' => [
                    'token' => $newToken,
                    'user' => array_merge($user->toArray(), $stats)
                ]
            ];

            Log::info('ZaloAuthController::autoLogin - Success response:', [
                'is_new_user' => $user->wasRecentlyCreated,
                'user_id' => $user->id
            ]);

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ZaloAuthController::autoLogin - Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Zalo GID không hợp lệ',
                'authenticated' => false,
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('ZaloAuthController::autoLogin - Unexpected error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi trong quá trình đăng nhập tự động',
                'authenticated' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin user từ Zalo (sau khi đã đăng nhập)
     */
    public function updateZaloInfo(Request $request)
    {
        try {
            $request->validate([
                'zalo_name' => 'nullable|string|max:255',
                'zalo_avatar' => 'nullable|url|max:500',
                'name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
            ]);

            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User chưa đăng nhập',
                ], 401);
            }

            $updateData = [];
            
            // Chỉ cập nhật các trường có giá trị
            if ($request->has('zalo_name') && !empty($request->zalo_name)) {
                $updateData['zalo_name'] = $request->zalo_name;
            }
            
            if ($request->has('zalo_avatar') && !empty($request->zalo_avatar)) {
                $updateData['zalo_avatar'] = $request->zalo_avatar;
            }
            
            if ($request->has('name') && !empty($request->name)) {
                $updateData['name'] = $request->name;
            }
            
            if ($request->has('phone') && !empty($request->phone)) {
                $updateData['phone'] = $request->phone;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công',
                'data' => $user->fresh()->toArray()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi trong quá trình cập nhật thông tin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
