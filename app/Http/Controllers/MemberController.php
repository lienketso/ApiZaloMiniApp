<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserClub;
use App\Models\Club;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        $users = User::with(['clubs'])->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'role' => 'sometimes|in:admin,member,guest',
            'joined_date' => 'required|date',
            'club_id' => 'required|exists:clubs,id',
            'notes' => 'nullable|string'
        ]);

        try {
            // Create the user
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'avatar' => $validated['avatar'] ?? null,
                'password' => bcrypt('password123'), // Default password, user can change later
            ];

            $user = User::create($userData);

            // Add user to club
            $userClub = UserClub::create([
                'club_id' => $validated['club_id'],
                'user_id' => $user->id,
                'role' => $validated['role'] ?? 'member',
                'joined_date' => $validated['joined_date'],
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => $user->load(['clubs']),
                'message' => 'Thành viên đã được tạo thành công và thêm vào câu lạc bộ'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(User $user)
    {
        $user->load(['attendances.event', 'clubs']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Thành viên đã được cập nhật thành công'
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Thành viên đã được xóa thành công'
        ]);
    }

    // Method để cập nhật role của user trong club
    public function updateClubRole(Request $request, User $user, Club $club)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,member,guest',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $userClub = UserClub::where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->first();

        if (!$userClub) {
            return response()->json([
                'success' => false,
                'message' => 'User không phải là thành viên của club này'
            ], 404);
        }

        $userClub->update($validated);

        return response()->json([
            'success' => true,
            'data' => $userClub,
            'message' => 'Role đã được cập nhật thành công'
        ]);
    }
}
