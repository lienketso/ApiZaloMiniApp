<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            
            // Kiểm tra xem user có member record không
            $member = $user->member;
            if (!$member) {
                return [
                    'total_events' => $totalEvents,
                    'total_attendance' => 0,
                    'attendance_rate' => 0,
                ];
            }
            
            $totalAttendance = $user->attendances()->count();
            $attendanceRate = $totalEvents > 0 ? round(($totalAttendance / $totalEvents) * 100) : 0;

            return [
                'total_events' => $totalEvents,
                'total_attendance' => $totalAttendance,
                'attendance_rate' => $attendanceRate,
            ];
        } catch (\Exception $e) {
            \Log::warning('Error calculating user stats:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
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
     * Auto login - kiểm tra và tạo tài khoản mới nếu chưa có
     */
    public function autoLogin(Request $request)
    {
        try {
            $request->validate([
                'zalo_gid' => 'required|string',
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
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
                    'phone' => $request->phone ?? null,
                    'zalo_gid' => $zaloGid,
                    'zalo_name' => $request->zalo_name ?? $request->name, // Sử dụng zalo_name nếu có, không thì dùng name
                    'zalo_avatar' => $request->zalo_avatar ?? null,
                    'role' => 'Member',
                    'join_date' => now(),
                    'password' => Hash::make(Str::random(16)),
                    'email' => 'zalo_' . $zaloGid . '@temp.com', // Email tạm thời bắt buộc
                ];
                
                \Log::info('Attempting to create user with data:', $userData);
                
                try {
                    // Tạo user mới
                    $user = User::create($userData);
                    
                    \Log::info('User created successfully', [
                        'user_id' => $user->id,
                        'zalo_gid' => $zaloGid
                    ]);
                    
                } catch (\Exception $createError) {
                    \Log::error('Error creating user:', [
                        'error' => $createError->getMessage(),
                        'data' => $userData,
                        'trace' => $createError->getTraceAsString()
                    ]);
                    throw $createError;
                }

                \Log::info('Auto-created new user during auto login', [
                    'zalo_gid' => $zaloGid,
                    'name' => $request->name,
                    'created_at' => now()
                ]);
            } else {
                // Cập nhật thông tin nếu user đã tồn tại
                $updateData = [
                    'name' => $request->name,
                ];
                
                // Chỉ cập nhật các trường có giá trị
                if ($request->has('zalo_name') && !empty($request->zalo_name)) {
                    $updateData['zalo_name'] = $request->zalo_name;
                }
                
                if ($request->has('zalo_avatar') && !empty($request->zalo_avatar)) {
                    $updateData['zalo_avatar'] = $request->zalo_avatar;
                }
                
                if ($request->has('phone') && !empty($request->phone)) {
                    $updateData['phone'] = $request->phone;
                }
                
                $user->update($updateData);

                \Log::info('Updated existing user during auto login', [
                    'zalo_gid' => $zaloGid,
                    'name' => $request->name,
                    'updated_at' => now()
                ]);
            }

            // Tạo token mới
            $user->tokens()->delete(); // Xóa token cũ
            $newToken = $user->createToken('zalo-mini-app')->plainTextToken;

            // Tính toán stats
            $stats = $this->calculateUserStats($user);

            return response()->json([
                'success' => true,
                'message' => $user->wasRecentlyCreated ? 'Tài khoản mới được tạo và đăng nhập thành công' : 'Đăng nhập thành công',
                'authenticated' => true,
                'is_new_user' => $user->wasRecentlyCreated,
                'data' => [
                    'token' => $newToken,
                    'user' => array_merge($user->toArray(), $stats)
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error during auto login', [
                'zalo_gid' => $request->zalo_gid ?? 'unknown',
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'authenticated' => false,
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error during auto login', [
                'zalo_gid' => $request->zalo_gid ?? 'unknown',
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

}
