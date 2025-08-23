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
     * Gửi thông báo ZNS
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

            // Chuẩn bị dữ liệu template
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

            $payload = [
                'phone' => $phone,
                'template_id' => $templateId,
                'template_data' => $templateParams,
                'access_token' => $this->accessToken
            ];

            Log::info('Sending ZNS notification:', [
                'phone' => $phone,
                'template_id' => $templateId,
                'template_data' => $templateData
            ]);

            $response = Http::post($this->apiUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('ZNS sent successfully:', $result);
                
                return [
                    'success' => true,
                    'message' => 'ZNS sent successfully',
                    'data' => $result
                ];
            } else {
                Log::error('ZNS sending failed:', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send ZNS',
                    'error' => $response->body()
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
}
