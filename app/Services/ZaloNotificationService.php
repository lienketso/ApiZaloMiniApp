<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ZaloNotificationService
{
    protected $accessToken;
    protected $apiUrl = 'https://business.openapi.zalo.me/notification/send';

    public function __construct()
    {
        $this->accessToken = Config::get('services.zalo.access_token');
    }

    /**
     * Gửi thông báo ZNS (Zalo Notification Service)
     * 
     * API Documentation: https://developers.zalo.me/docs/zalo-notification-service/bat-dau/gioi-thieu-zalo-notification-service
     * Endpoint: https://business.openapi.zalo.me/notification/send
     */
    public function sendZNS(string $phone, string $templateId, array $templateData, string $inviteLink = null): array
    {
        try {
            if (!$this->accessToken) {
                Log::error('Zalo access token not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo access token not configured'
                ];
            }

            // Chuẩn bị dữ liệu template theo format ZNS API
            $templateParams = [];
            foreach ($templateData as $key => $value) {
                $templateParams[] = [
                    'key' => $key,
                    'value' => $value
                ];
            }

            // Thêm link mời nếu có
            if ($inviteLink) {
                $templateParams[] = [
                    'key' => 'invite_link',
                    'value' => $inviteLink
                ];
            }

            // Payload theo ZNS API specification
            $payload = [
                'phone' => $phone,
                'template_id' => $templateId,
                'template_data' => $templateParams,
                'access_token' => $this->accessToken
            ];

            Log::info('Sending ZNS notification:', [
                'phone' => $phone,
                'template_id' => $templateId,
                'template_data' => $templateData,
                'api_url' => $this->apiUrl
            ]);

            // Gửi request đến ZNS API
            $response = Http::timeout(30)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('ZNS sent successfully:', $result);
                
                return [
                    'success' => true,
                    'message' => 'ZNS sent successfully',
                    'data' => $result
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('ZNS sending failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send ZNS: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending ZNS:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error sending ZNS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gửi thông báo mời thành viên
     * 
     * Template cần có các biến:
     * - club_name: Tên câu lạc bộ
     * - invite_message: Lời nhắn mời
     * - action_text: Hướng dẫn hành động
     * - invite_link: Link để tham gia (tự động thêm)
     */
    public function sendInvitationNotification(string $phone, string $clubName, string $inviteLink): array
    {
        // Template ID cho thông báo mời thành viên (cần tạo trong Zalo Business)
        $templateId = Config::get('services.zalo.invitation_template_id', '12345');
        
        $templateData = [
            'club_name' => $clubName,
            'invite_message' => 'Bạn được mời tham gia câu lạc bộ ' . $clubName,
            'action_text' => 'Nhấn vào đây để tham gia'
        ];

        return $this->sendZNS($phone, $templateId, $templateData, $inviteLink);
    }

    /**
     * Gửi thông báo chào mừng thành viên mới
     * 
     * Template cần có các biến:
     * - club_name: Tên câu lạc bộ
     * - welcome_message: Lời chào mừng
     * - next_steps: Hướng dẫn tiếp theo
     */
    public function sendWelcomeNotification(string $phone, string $clubName): array
    {
        $templateId = Config::get('services.zalo.welcome_template_id', '12346');
        
        $templateData = [
            'club_name' => $clubName,
            'welcome_message' => 'Chào mừng bạn đã tham gia câu lạc bộ ' . $clubName,
            'next_steps' => 'Hãy tham gia các hoạt động của câu lạc bộ'
        ];

        return $this->sendZNS($phone, $templateId, $templateData);
    }

    /**
     * Test kết nối ZNS API
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl);
            
            if ($response->status() === 404) {
                // 404 là expected response khi không có payload
                return [
                    'success' => true,
                    'message' => 'ZNS API endpoint is accessible',
                    'status' => 'API endpoint working'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'ZNS API connection test completed',
                'status_code' => $response->status(),
                'response' => $response->body()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'ZNS API connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin template từ ZNS
     */
    public function getTemplateInfo(string $templateId): array
    {
        try {
            // API để lấy thông tin template (cần implement theo ZNS docs)
            $apiUrl = 'https://business.openapi.zalo.me/notification/template/' . $templateId;
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken
                ])
                ->get($apiUrl);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get template info',
                    'error' => $response->json()
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting template info: ' . $e->getMessage()
            ];
        }
    }
}
