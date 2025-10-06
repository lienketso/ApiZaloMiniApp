<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\ZaloToken;

class ZaloNotificationService
{
    protected $accessToken;
    protected $appId;
    protected $appSecret;
    protected $oaId;

    public function __construct()
    {
        $this->accessToken = Config::get('services.zalo.oa_access_token');
        $this->appId = Config::get('services.zalo.app_id');
        $this->appSecret = Config::get('services.zalo.app_secret');
        $this->oaId = Config::get('services.zalo.oa_id');
        
        // Tự động kiểm tra và refresh token nếu cần
        $this->ensureValidToken();
    }

    /**
     * Đảm bảo access token còn hiệu lực, tự động refresh nếu cần
     * 
     * @return bool
     */
    protected function ensureValidToken(): bool
    {
        try {
            $token = ZaloToken::first();
            
            if (!$token || empty($token->access_token)) {
                Log::warning('No Zalo token found in database');
                return false;
            }

            // Kiểm tra nếu token hết hạn (trừ 60 giây buffer)
            $isExpired = false;
            if ($token->expires_in && $token->last_refreshed_at) {
                $secondsSinceRefresh = now()->diffInSeconds($token->last_refreshed_at);
                $isExpired = $secondsSinceRefresh > ($token->expires_in - 60);
            }

            if ($isExpired) {
                Log::info('Zalo access token expired, attempting to refresh...', [
                    'last_refreshed' => $token->last_refreshed_at,
                    'expires_in' => $token->expires_in,
                    'seconds_since_refresh' => $secondsSinceRefresh
                ]);

                $refreshResult = $this->refreshAccessToken();
                
                if ($refreshResult['success']) {
                    Log::info('Zalo access token refreshed successfully');
                    // Cập nhật access token trong service
                    $this->accessToken = $refreshResult['data']['access_token'] ?? $this->accessToken;
                    return true;
                } else {
                    Log::error('Failed to refresh Zalo access token', [
                        'error' => $refreshResult['message']
                    ]);
                    return false;
                }
            }

            // Cập nhật access token từ database nếu cần
            if ($this->accessToken !== $token->access_token) {
                $this->accessToken = $token->access_token;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error ensuring valid Zalo token:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Gửi tin nhắn cá nhân đến Zalo user (sử dụng OA API)
     * 
     * @param string $zaloId Zalo user ID
     * @param string $message Tin nhắn cần gửi
     * @param string $appId Zalo App ID (optional, sẽ dùng từ config nếu không truyền)
     * @param string $oaId Zalo OA ID (optional, sẽ dùng từ config nếu không truyền)
     * @return array
     */
    public function sendPersonalMessage(string $zaloId, string $message, string $appId = null, string $oaId = null): array
    {
        try {
            $appId = $appId ?: $this->appId;
            $oaId = $oaId ?: $this->oaId;
            
            // Đảm bảo token còn hiệu lực trước khi gửi
            if (!$this->ensureValidToken()) {
                Log::error('Zalo OA access token not available or expired');
                return [
                    'success' => false,
                    'message' => 'Zalo OA access token not available or expired',
                    'error' => 1
                ];
            }

            $url = "https://openapi.zalo.me/v3.0/oa/message/cs";
            
            $payload = [
                "recipient" => [
                    "user_id" => $zaloId
                ],
                "message" => [
                    "text" => $message
                ]
            ];

            Log::info('Sending Zalo personal message:', [
                'zalo_id' => $zaloId,
                'app_id' => $appId,
                'oa_id' => $oaId,
                'message' => $message
            ]);

            // Theo tài liệu: access_token để ở header
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => $this->accessToken,
            ])->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo personal message sent successfully:', $result);
                
                return [
                    'success' => true,
                    'message' => 'Zalo personal message sent successfully',
                    'data' => $result,
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                
                // Kiểm tra nếu lỗi là "Access token is invalid"
                if (isset($errorResponse['error']) && $errorResponse['error'] == -216) {
                    Log::warning('Access token is invalid, attempting to refresh...');
                    
                    // Thử refresh token và gửi lại
                    $refreshResult = $this->refreshAccessToken();
                    if ($refreshResult['success']) {
                        Log::info('Token refreshed, retrying message send...');
                        
                        // Cập nhật access token
                        $this->accessToken = $refreshResult['data']['access_token'];
                        
                        // Gửi lại tin nhắn
                        $retryResponse = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'access_token' => $this->accessToken,
                        ])->post($url, $payload);
                        
                        if ($retryResponse->successful()) {
                            $retryResult = $retryResponse->json();
                            Log::info('Zalo personal message sent successfully after token refresh:', $retryResult);
                            
                            return [
                                'success' => true,
                                'message' => 'Zalo personal message sent successfully after token refresh',
                                'data' => $retryResult,
                                'error' => 0
                            ];
                        }
                    }
                }
                
                Log::error('Zalo personal message sending failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send Zalo personal message: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending Zalo personal message:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error sending Zalo personal message: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }

    /**
     * Gửi thông báo điểm danh đến Zalo user
     * 
     * @param string $zaloId Zalo user ID
     * @param string $appId Zalo App ID (optional)
     * @param string $oaId Zalo OA ID (optional)
     * @return array
     */
    public function sendCheckinNotification(string $zaloId, string $appId = null, string $oaId = null): array
    {
        $appId = $appId ?: $this->appId;
        $oaId = $oaId ?: $this->oaId;
        
        $miniAppLink = "https://zalo.me/s/{$oaId}?openMiniApp={$appId}";
        $message = "📢 Bạn có thông báo điểm danh từ câu lạc bộ!\n\nNhấn vào link bên dưới để vào điểm danh:\n" . $miniAppLink;

        return $this->sendPersonalMessage($zaloId, $message, $appId, $oaId);
    }

    /**
     * Gửi tin nhắn broadcast đến tất cả người dùng đã follow OA (miễn phí)
     * 
     * @param string $message Tin nhắn cần gửi
     * @param string $appId Zalo App ID (optional)
     * @param string $oaId Zalo OA ID (optional)
     * @return array
     */
    public function sendBroadcastMessage(string $message, string $appId = null, string $oaId = null): array
    {
        try {
            $appId = $appId ?: $this->appId;
            $oaId = $oaId ?: $this->oaId;
            
            // Đảm bảo token còn hiệu lực trước khi gửi
            if (!$this->ensureValidToken()) {
                Log::error('Zalo OA access token not available or expired');
                return [
                    'success' => false,
                    'message' => 'Zalo OA access token not available or expired',
                    'error' => 1
                ];
            }

            $url = "https://openapi.zalo.me/v2.0/oa/message/broadcast";
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
                    'access_token' => $this->accessToken
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
                
                // Kiểm tra nếu lỗi là "Access token is invalid"
                if (isset($errorResponse['error']) && $errorResponse['error'] == -216) {
                    Log::warning('Access token is invalid for broadcast, attempting to refresh...');
                    
                    // Thử refresh token và gửi lại
                    $refreshResult = $this->refreshAccessToken();
                    if ($refreshResult['success']) {
                        Log::info('Token refreshed, retrying broadcast send...');
                        
                        // Cập nhật access token
                        $this->accessToken = $refreshResult['data']['access_token'];
                        
                        // Gửi lại broadcast
                        $retryResponse = Http::timeout(30)
                            ->withHeaders([
                                'Content-Type' => 'application/json',
                                'access_token' => $this->accessToken
                            ])
                            ->post($url, $payload);
                        
                        if ($retryResponse->successful()) {
                            $retryResult = $retryResponse->json();
                            Log::info('Zalo OA broadcast sent successfully after token refresh:', $retryResult);
                            
                            return [
                                'success' => true,
                                'message' => 'Zalo OA broadcast sent successfully after token refresh',
                                'data' => $retryResult,
                                'error' => 0
                            ];
                        }
                    }
                }
                
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
     * Làm mới Access Token bằng Refresh Token (OAuth v4)
     * 
     * @return array
     */
    public function refreshAccessToken(): array
    {
        try {
            $token = ZaloToken::first();
            if (!$token || empty($token->refresh_token)) {
                return [
                    'success' => false,
                    'message' => 'Chưa có refresh_token trong DB. Hãy insert refresh_token ban đầu.',
                    'error' => 1
                ];
            }

            $url = "https://oauth.zaloapp.com/v4/oa/access_token";

            // GỌI API theo đúng doc: header 'secret_key' + body form-encoded
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'secret_key'   => $this->appSecret,
            ])->asForm()->post($url, [
                'app_id'        => $this->appId,
                'refresh_token' => $token->refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            $data = $response->json();

            // Debug: nếu muốn log chi tiết khi lỗi
            if (!$response->ok()) {
                Log::error('Zalo refresh-token http error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            // Nếu API trả access_token thì cập nhật DB
            if (isset($data['access_token'])) {
                $token->update([
                    'access_token'      => $data['access_token'],
                    'refresh_token'     => $data['refresh_token'] ?? $token->refresh_token,
                    'expires_in'        => $data['expires_in'] ?? null,
                    'last_refreshed_at' => now(),
                ]);

                // Cập nhật access token trong service
                $this->accessToken = $data['access_token'];

                return [
                    'success' => true,
                    'message' => 'Access token refreshed successfully',
                    'data'    => $data,
                    'error' => 0
                ];
            }

            // Trả về lỗi rõ ràng từ Zalo để bạn debug (ví dụ Invalid secret key)
            return [
                'success' => false,
                'message' => 'Failed to refresh access token: ' . ($data['message'] ?? 'Unknown error'),
                'http_status' => $response->status(),
                'zalo_response' => $data,
                'error' => 1
            ];

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
     * Lấy Access Token từ Authorization Code (OAuth v4)
     * 
     * @param string $code Authorization code từ Zalo
     * @param string $redirectUri Redirect URI đã đăng ký
     * @return array
     */
    public function getAccessToken(string $code, string $redirectUri): array
    {
        try {
            $url = 'https://oauth.zaloapp.com/v4/access_token';
            
            $payload = [
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code
            ];

            Log::info('Requesting Zalo OAuth v4 access token:', [
                'app_id' => $this->appId,
                'redirect_uri' => $redirectUri,
                'code' => substr($code, 0, 10) . '...' // Log một phần code để debug
            ]);

            $response = Http::timeout(30)
                ->asForm()
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OAuth v4 access token received successfully');
                
                // Lưu token vào database
                if (isset($result['access_token'])) {
                    $token = ZaloToken::first();
                    if (!$token) {
                        $token = new ZaloToken();
                    }
                    
                    $token->updateOrCreate(
                        ['id' => 1], // Giả sử chỉ có 1 record
                        [
                            'access_token' => $result['access_token'],
                            'refresh_token' => $result['refresh_token'] ?? null,
                            'expires_in' => $result['expires_in'] ?? null,
                            'last_refreshed_at' => now(),
                        ]
                    );
                    
                    // Cập nhật access token trong service
                    $this->accessToken = $result['access_token'];
                }
                
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
     * Lấy thông tin người dùng từ Access Token (OAuth v4)
     * 
     * @param string $accessToken Access token (optional, sẽ dùng từ service nếu không truyền)
     * @return array
     */
    public function getUserInfo(string $accessToken = null): array
    {
        try {
            $accessToken = $accessToken ?: $this->accessToken;
            
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Access token not available',
                    'error' => 1
                ];
            }

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
     * @param string $redirectUri Redirect URI
     * @param string $state State parameter (optional)
     * @return string
     */
    public function getAuthUrl(string $redirectUri, string $state = null): string
    {
        $params = [
            'app_id' => $this->appId,
            'redirect_uri' => urlencode($redirectUri),
            'state' => $state ?: uniqid()
        ];

        $queryString = http_build_query($params);
        return 'https://oauth.zaloapp.com/v4/oa/permission?' . $queryString;
    }

    /**
     * Test gửi tin nhắn đến Zalo user cụ thể
     * 
     * @param string $zaloId Zalo user ID để test
     * @return array
     */
    public function testMessage(string $zaloId = "5170627724267093288"): array
    {
        // Đảm bảo token còn hiệu lực trước khi test
        if (!$this->ensureValidToken()) {
            Log::error('Zalo OA access token not available or expired for test message');
            return [
                'success' => false,
                'message' => 'Zalo OA access token not available or expired',
                'error' => 1
            ];
        }

        $message = "Xin chào, đây là tin nhắn test từ Laravel truy cập app zalo https://zalo.me/s/{$this->oaId}";
        
        return $this->sendPersonalMessage($zaloId, $message);
    }

    /**
     * Kiểm tra trạng thái token hiện tại
     * 
     * @return array
     */
    public function checkTokenStatus(): array
    {
        $token = ZaloToken::first();
        if (!$token || empty($token->access_token)) {
            return [
                'success' => false,
                'message' => 'Chưa có token, hãy chạy refresh token trước',
                'has_token' => false
            ];
        }

        return [
            'success' => true,
            'message' => 'Token available',
            'has_token' => true,
            'token_info' => [
                'has_access_token' => !empty($token->access_token),
                'has_refresh_token' => !empty($token->refresh_token),
                'expires_in' => $token->expires_in,
                'last_refreshed_at' => $token->last_refreshed_at
            ]
        ];
    }

    /**
     * Kiểm tra user đã follow OA chưa
     * 
     * @param string $zaloGid Zalo GID của user
     * @return array
     */
    public function checkUserFollowOA(string $zaloGid): array
    {
        try {
            // Đảm bảo token còn hiệu lực trước khi kiểm tra
            if (!$this->ensureValidToken()) {
                Log::error('Zalo OA access token not available or expired for follow check');
                return [
                    'success' => false,
                    'message' => 'Zalo OA access token not available or expired',
                    'error' => 1
                ];
            }

            $url = "https://openapi.zalo.me/v2.0/oa/followers/info";
            
            $payload = [
                "user_id" => $zaloGid
            ];

            Log::info('Checking if user follows OA:', [
                'zalo_gid' => $zaloGid,
                'oa_id' => $this->oaId
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'access_token' => $this->accessToken
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Zalo OA follow check result:', $result);
                
                // Kiểm tra kết quả từ Zalo API
                $isFollowing = isset($result['data']) && isset($result['data']['is_follow']) && $result['data']['is_follow'] === true;
                
                return [
                    'success' => true,
                    'message' => $isFollowing ? 'User đã follow OA' : 'User chưa follow OA',
                    'data' => [
                        'is_following' => $isFollowing,
                        'zalo_gid' => $zaloGid,
                        'oa_id' => $this->oaId
                    ],
                    'error' => 0
                ];
            } else {
                $errorResponse = $response->json();
                Log::error('Zalo OA follow check failed:', [
                    'status' => $response->status(),
                    'response' => $errorResponse,
                    'raw_response' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to check follow status: ' . ($errorResponse['message'] ?? 'Unknown error'),
                    'error' => $errorResponse['error'] ?? 1,
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error checking Zalo OA follow status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'zalo_gid' => $zaloGid
            ]);
            
            return [
                'success' => false,
                'message' => 'Error checking follow status: ' . $e->getMessage(),
                'error' => 1
            ];
        }
    }
}