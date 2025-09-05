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
     * Gửi thông báo điểm danh qua Tin Truyền thông OA (miễn phí)
     * 
     * Sử dụng Zalo OA API để gửi tin nhắn broadcast đến người dùng đã follow OA
     */
    public function sendCheckinNotification(string $zaloId, string $appId, string $oaId): array
    {
        try {
            // Sử dụng Tin Truyền thông OA - miễn phí
            $url = "https://openapi.zalo.me/v2.0/oa/message";
            $accessToken = env('ZALO_OA_ACCESS_TOKEN');

            if (!$accessToken) {
                Log::error('Zalo OA access token not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo OA access token not configured',
                    'error' => 1
                ];
            }

            $miniAppLink = "https://zalo.me/s/{$oaId}?openMiniApp={$appId}";

            // Tin Truyền thông OA sử dụng format khác
            $payload = [
                "recipient" => [
                    "user_id" => $zaloId
                ],
                "message" => [
                    "text" => "📢 Bạn có thông báo điểm danh từ câu lạc bộ!\n\nNhấn vào link bên dưới để vào điểm danh:\n" . $miniAppLink
                ]
            ];

            Log::info('Sending Zalo OA broadcast message:', [
                'zalo_id' => $zaloId,
                'app_id' => $appId,
                'oa_id' => $oaId,
                'mini_app_link' => $miniAppLink
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'access_token' => $accessToken
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OA broadcast message sent successfully:', $result);
                
                return [
                    'success' => true,
                    'message' => 'Zalo OA broadcast message sent successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo OA broadcast message sending failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send Zalo OA broadcast message: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending Zalo OA broadcast message:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error sending Zalo OA broadcast message: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }

    /**
     * Gửi tin nhắn broadcast đến tất cả người dùng đã follow OA (miễn phí)
     * 
     * Đây là cách gửi miễn phí thay vì gửi từng user một
     */
    public function sendBroadcastMessage(string $message, string $appId, string $oaId): array
    {
        try {
            $url = "https://openapi.zalo.me/v2.0/oa/message/broadcast";
            $accessToken = env('ZALO_OA_ACCESS_TOKEN');

            if (!$accessToken) {
                Log::error('Zalo OA access token not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo OA access token not configured',
                    'error' => 1
                ];
            }

            $miniAppLink = "https://zalo.me/s/{$oaId}?openMiniApp={$appId}";
            $fullMessage = $message . "\n\n" . $miniAppLink;

            $payload = [
                "message" => [
                    "text" => $fullMessage
                ]
            ];

            Log::info('Sending Zalo OA broadcast to all followers:', [
                'message' => $fullMessage,
                'app_id' => $appId,
                'oa_id' => $oaId
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'access_token' => $accessToken
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OA broadcast sent successfully:', $result);
                
                return [
                    'success' => true,
                    'message' => 'Zalo OA broadcast sent successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo OA broadcast sending failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send Zalo OA broadcast: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending Zalo OA broadcast:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error sending Zalo OA broadcast: ' . $e->getMessage(),
                'error' => 1
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

    /**
     * Lấy Access Token từ Authorization Code (OAuth v4)
     * 
     * @param string $appId Zalo App ID
     * @param string $appSecret Zalo App Secret
     * @param string $redirectUri Redirect URI đã đăng ký
     * @param string $code Authorization code từ Zalo
     * @return array
     */
    public function getAccessToken(string $appId, string $appSecret, string $redirectUri, string $code): array
    {
        try {
            $url = 'https://oauth.zaloapp.com/v4/access_token';
            
            $payload = [
                'app_id' => $appId,
                'app_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code
            ];

            Log::info('Requesting Zalo OAuth v4 access token:', [
                'app_id' => $appId,
                'redirect_uri' => $redirectUri,
                'code' => substr($code, 0, 10) . '...' // Log một phần code để debug
            ]);

            $response = Http::timeout(30)
                ->asForm()
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OAuth v4 access token received successfully');
                
                return [
                    'success' => true,
                    'message' => 'Access token retrieved successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo OAuth v4 access token request failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get access token: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error getting Zalo OAuth v4 access token:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error getting access token: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }

    /**
     * Làm mới Access Token bằng Refresh Token (OAuth v4)
     * 
     * @param string $appId Zalo App ID
     * @param string $appSecret Zalo App Secret
     * @param string $refreshToken Refresh token
     * @return array
     */
    public function refreshAccessToken(string $appId, string $appSecret, string $refreshToken): array
    {
        try {
            $url = 'https://oauth.zaloapp.com/v4/refresh_token';
            
            $payload = [
                'app_id' => $appId,
                'app_secret' => $appSecret,
                'refresh_token' => $refreshToken
            ];

            Log::info('Refreshing Zalo OAuth v4 access token:', [
                'app_id' => $appId,
                'refresh_token' => substr($refreshToken, 0, 10) . '...' // Log một phần refresh token
            ]);

            $response = Http::timeout(30)
                ->asForm()
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OAuth v4 access token refreshed successfully');
                
                return [
                    'success' => true,
                    'message' => 'Access token refreshed successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo OAuth v4 refresh token request failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to refresh access token: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error refreshing Zalo OAuth v4 access token:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error refreshing access token: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }

    /**
     * Lấy thông tin người dùng từ Access Token (OAuth v4)
     * 
     * @param string $accessToken Access token
     * @return array
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $url = 'https://graph.zalo.me/v2.0/me';
            
            Log::info('Getting Zalo user info with OAuth v4 access token');

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken
                ])
                ->get($url);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo user info retrieved successfully');
                
                return [
                    'success' => true,
                    'message' => 'User info retrieved successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo user info request failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get user info: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error getting Zalo user info:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error getting user info: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }

    /**
     * Tạo URL xác thực OAuth v4
     * 
     * @param string $appId Zalo App ID
     * @param string $redirectUri Redirect URI
     * @param string $state State parameter (optional)
     * @return string
     */
    public function getAuthUrl(string $appId, string $redirectUri, string $state = null): string
    {
        $params = [
            'app_id' => $appId,
            'redirect_uri' => urlencode($redirectUri),
            'state' => $state ?: uniqid()
        ];

        $queryString = http_build_query($params);
        return 'https://oauth.zaloapp.com/v4/oa/permission?' . $queryString;
    }
}
