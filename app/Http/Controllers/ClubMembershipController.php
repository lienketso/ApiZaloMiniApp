<?php

namespace App\Http\Controllers;

use App\Services\ClubMembershipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClubMembershipController extends Controller
{
    protected $membershipService;

    public function __construct(ClubMembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }

    /**
     * User click vào club để tham gia
     * Frontend sẽ gọi API này khi user click vào club
     */
    public function joinClub(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'club_id' => 'required|integer|exists:clubs,id',
                'phone' => 'required|string|max:20',
                'zalo_gid' => 'required|string',
            ]);

            $clubId = $request->club_id;
            $phone = $request->phone;
            $zaloGid = $request->zalo_gid;

            Log::info('User attempting to join club:', [
                'club_id' => $clubId,
                'phone' => $phone,
                'zalo_gid' => $zaloGid
            ]);

            // Gọi service để xử lý việc tham gia club
            $result = $this->membershipService->mapUserToClub($phone, $zaloGid, $clubId);

            if ($result['success']) {
                Log::info('User successfully joined club:', [
                    'club_id' => $clubId,
                    'phone' => $phone,
                    'zalo_gid' => $zaloGid,
                    'code' => $result['code']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null,
                    'code' => $result['code']
                ]);
            } else {
                Log::warning('User failed to join club:', [
                    'club_id' => $clubId,
                    'phone' => $phone,
                    'zalo_gid' => $zaloGid,
                    'code' => $result['code'],
                    'message' => $result['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                    'data' => $result['data'] ?? null
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in joinClub:', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in joinClub:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý yêu cầu tham gia câu lạc bộ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái membership của user trong club
     */
    public function checkMembership(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'club_id' => 'required|integer|exists:clubs,id',
                'zalo_gid' => 'required|string',
            ]);

            $clubId = $request->club_id;
            $zaloGid = $request->zalo_gid;

            $result = $this->membershipService->checkMembershipStatus($zaloGid, $clubId);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'code' => $result['code'],
                'data' => $result['data'] ?? null
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error in checkMembership:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra trạng thái thành viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách club mà user có thể tham gia (có invitation)
     */
    public function getAvailableClubs(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $phone = $request->phone;

            $result = $this->membershipService->getAvailableClubs($phone);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error in getAvailableClubs:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách câu lạc bộ có thể tham gia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint để kiểm tra service
     */
    public function test(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => 'required|string|max:20',
                'zalo_gid' => 'required|string',
                'club_id' => 'required|integer|exists:clubs,id',
            ]);

            $phone = $request->phone;
            $zaloGid = $request->zalo_gid;
            $clubId = $request->club_id;

            // Test các chức năng
            $joinResult = $this->membershipService->mapUserToClub($phone, $zaloGid, $clubId);
            $membershipResult = $this->membershipService->checkMembershipStatus($zaloGid, $clubId);
            $availableClubsResult = $this->membershipService->getAvailableClubs($phone);

            return response()->json([
                'success' => true,
                'message' => 'Test completed successfully',
                'results' => [
                    'join_club' => $joinResult,
                    'check_membership' => $membershipResult,
                    'available_clubs' => $availableClubsResult
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
