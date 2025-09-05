<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ZaloNotificationService;

class ZaloOAuthController extends Controller
{
    protected $zaloNotificationService;

    public function __construct(ZaloNotificationService $zaloNotificationService)
    {
        $this->zaloNotificationService = $zaloNotificationService;
    }

    /**
     * Tạo URL xác thực OAuth v4
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'redirect_uri' => 'required|url',
                'state' => 'nullable|string'
            ]);

            $appId = env('ZALO_APP_ID');
            if (!$appId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo App ID chưa được cấu hình'
                ], 500);
            }

            $authUrl = $this->zaloNotificationService->getAuthUrl(
                $appId,
                $validated['redirect_uri'],
                $validated['state'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Auth URL created successfully',
                'data' => [
                    'auth_url' => $authUrl,
                    'app_id' => $appId,
                    'redirect_uri' => $validated['redirect_uri'],
                    'state' => $validated['state'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy Access Token từ Authorization Code
     */
    public function getAccessToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
                'redirect_uri' => 'required|url'
            ]);

            $appId = env('ZALO_APP_ID');
            $appSecret = env('ZALO_APP_SECRET');

            if (!$appId || !$appSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo App ID hoặc App Secret chưa được cấu hình'
                ], 500);
            }

            $result = $this->zaloNotificationService->getAccessToken(
                $appId,
                $appSecret,
                $validated['redirect_uri'],
                $validated['code']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Access token retrieved successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 1
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Làm mới Access Token
     */
    public function refreshAccessToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'refresh_token' => 'required|string'
            ]);

            $appId = env('ZALO_APP_ID');
            $appSecret = env('ZALO_APP_SECRET');

            if (!$appId || !$appSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zalo App ID hoặc App Secret chưa được cấu hình'
                ], 500);
            }

            $result = $this->zaloNotificationService->refreshAccessToken(
                $appId,
                $appSecret,
                $validated['refresh_token']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Access token refreshed successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 1
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin người dùng
     */
    public function getUserInfo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'access_token' => 'required|string'
            ]);

            $result = $this->zaloNotificationService->getUserInfo(
                $validated['access_token']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'User info retrieved successfully',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 1
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}

