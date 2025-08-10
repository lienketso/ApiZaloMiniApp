<?php

namespace App\Http\Controllers;
use App\Models\Member;
use App\Models\ClubMember;
use App\Models\Club;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::all();
        return response()->json($members);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'role' => 'sometimes|in:admin,member,guest',
            'status' => 'sometimes|in:active,inactive',
            'joined_date' => 'required|date',
            'club_id' => 'required|exists:clubs,id',
            'notes' => 'nullable|string'
        ]);

        try {
            // Create the member
            $memberData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'avatar' => $validated['avatar'] ?? null,
                'role' => $validated['role'] ?? 'member',
                'status' => $validated['status'] ?? 'active',
                'joined_date' => $validated['joined_date']
            ];

            $member = Member::create($memberData);

            // Add member to club
            $clubMember = ClubMember::create([
//                'club_id' => $validated['club_id'],
                'club_id' => 1,
                'member_id' => $member->id,
                'role' => $validated['role'] ?? 'member',
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => $member->load(['clubs']),
                'message' => 'Thành viên đã được tạo thành công và thêm vào câu lạc bộ'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Member $member)
    {
        $member->load(['attendances.event']);

        return response()->json([
            'success' => true,
            'data' => $member
        ]);
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:members,email,' . $member->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'role' => 'sometimes|in:admin,member,guest',
            'status' => 'sometimes|in:active,inactive',
            'joined_date' => 'sometimes|required|date'
        ]);

        $member->update($validated);

        return response()->json([
            'success' => true,
            'data' => $member,
            'message' => 'Thành viên đã được cập nhật thành công'
        ]);
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json([
            'success' => true,
            'message' => 'Thành viên đã được xóa thành công'
        ]);
    }
}
