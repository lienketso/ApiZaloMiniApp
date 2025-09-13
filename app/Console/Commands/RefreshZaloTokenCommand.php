<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZaloToken;
use App\Services\ZaloNotificationService;
use Illuminate\Support\Facades\Log;

class RefreshZaloTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zalo:refresh-token 
                            {--force : Force refresh even if token is not expired}
                            {--check-only : Only check token status without refreshing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and refresh Zalo OA access token if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Zalo token refresh check...');
        
        try {
            $token = ZaloToken::first();
            
            if (!$token || empty($token->access_token)) {
                $this->error('❌ No Zalo token found in database');
                Log::warning('RefreshZaloTokenCommand: No Zalo token found in database');
                return Command::FAILURE;
            }

            $this->info("📋 Current token info:");
            $this->line("   - Access token: " . substr($token->access_token, 0, 20) . "...");
            $this->line("   - Refresh token: " . (empty($token->refresh_token) ? 'Empty' : substr($token->refresh_token, 0, 20) . "..."));
            $this->line("   - Expires in: " . ($token->expires_in ?? 'Unknown') . " seconds");
            $this->line("   - Last refreshed: " . ($token->last_refreshed_at ?? 'Never'));

            // Kiểm tra nếu chỉ muốn check status
            if ($this->option('check-only')) {
                $this->info('✅ Token status check completed');
                return Command::SUCCESS;
            }

            // Kiểm tra nếu token hết hạn hoặc force refresh
            $shouldRefresh = false;
            $reason = '';

            if ($this->option('force')) {
                $shouldRefresh = true;
                $reason = 'Force refresh requested';
            } elseif ($token->expires_in && $token->last_refreshed_at) {
                $secondsSinceRefresh = now()->diffInSeconds($token->last_refreshed_at);
                $bufferTime = 300; // 5 phút buffer
                $isExpired = $secondsSinceRefresh > ($token->expires_in - $bufferTime);
                
                if ($isExpired) {
                    $shouldRefresh = true;
                    $reason = "Token expired (used {$secondsSinceRefresh}s, expires in {$token->expires_in}s)";
                } else {
                    $remainingTime = $token->expires_in - $secondsSinceRefresh;
                    $this->info("✅ Token is still valid for {$remainingTime} seconds");
                }
            } else {
                $this->warn('⚠️  Cannot determine token expiry (missing expires_in or last_refreshed_at)');
                $shouldRefresh = true;
                $reason = 'Missing expiry information';
            }

            if (!$shouldRefresh) {
                $this->info('✅ No refresh needed');
                return Command::SUCCESS;
            }

            $this->warn("🔄 Token needs refresh: {$reason}");
            
            // Kiểm tra refresh token
            if (empty($token->refresh_token)) {
                $this->error('❌ No refresh token available. Please re-authenticate.');
                Log::error('RefreshZaloTokenCommand: No refresh token available');
                return Command::FAILURE;
            }

            // Thực hiện refresh token
            $this->info('🔄 Refreshing token...');
            
            $zaloService = new ZaloNotificationService();
            $refreshResult = $zaloService->refreshAccessToken();

            if ($refreshResult['success']) {
                $this->info('✅ Token refreshed successfully!');
                
                // Log thông tin token mới
                $newToken = ZaloToken::first();
                if ($newToken) {
                    $this->line("   - New access token: " . substr($newToken->access_token, 0, 20) . "...");
                    $this->line("   - New expires in: " . ($newToken->expires_in ?? 'Unknown') . " seconds");
                    $this->line("   - Refreshed at: " . ($newToken->last_refreshed_at ?? 'Unknown'));
                }

                Log::info('RefreshZaloTokenCommand: Token refreshed successfully', [
                    'old_token' => substr($token->access_token, 0, 20) . '...',
                    'new_token' => substr($newToken->access_token ?? '', 0, 20) . '...',
                    'expires_in' => $newToken->expires_in ?? null
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to refresh token: ' . $refreshResult['message']);
                Log::error('RefreshZaloTokenCommand: Failed to refresh token', [
                    'error' => $refreshResult['message'],
                    'data' => $refreshResult['data'] ?? null
                ]);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error during token refresh: ' . $e->getMessage());
            Log::error('RefreshZaloTokenCommand: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}