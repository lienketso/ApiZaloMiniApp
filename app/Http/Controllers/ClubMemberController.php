<?php

namespace App\Http\Controllers;

use App\Models\ClubMember;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClubMemberController extends Controller
{
    /**
     * Add a member to a club
     */
    public function addMemberToClub(Request $request): JsonResponse
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'member_id' => 'required|exists:users,id',
            'role' => 'required|in:member,admin,guest',
            'joined_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Check if member is already in this club
            $existingMembership = ClubMember::where('club_id', $request->club_id)
                ->where('member_id', $request->member_id)
                ->first();

            if ($existingMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thành viên đã tồn tại trong câu lạc bộ này'
                ], 400);
            }

            $clubMember = ClubMember::create([
                'club_id' => $request->club_id,
                'member_id' => $request->member_id,
                'role' => $request->role,
                'joined_date' => $request->joined_date ?? now(),
                'notes' => $request->notes,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm thành viên vào câu lạc bộ thành công',
                'data' => $clubMember->load(['user', 'club'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update member role in club
     */
    public function updateMemberRole(Request $request, $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:member,admin,guest',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $clubMember = ClubMember::findOrFail($id);

            $clubMember->update([
                'role' => $request->role,
                'notes' => $request->notes,
                'is_active' => $request->has('is_active') ? $request->is_active : $clubMember->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành viên thành công',
                'data' => $clubMember->load(['user', 'club'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove member from club
     */
    public function removeMemberFromClub($id): JsonResponse
    {
        try {
            $clubMember = ClubMember::findOrFail($id);
            $clubMember->delete();

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
     * Get all members of a club
     */
    public function getClubMembers($clubId): JsonResponse
    {
        try {
            $clubMembers = ClubMember::where('club_id', $clubId)
                ->with(['user', 'club'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clubMembers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all clubs of a member
     */
    public function getMemberClubs($memberId): JsonResponse
    {
        try {
            $memberClubs = ClubMember::where('member_id', $memberId)
                ->with(['user', 'club'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $memberClubs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role options
     */
    public function getRoleOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ClubMember::getRoleOptions()
        ]);
    }
}
