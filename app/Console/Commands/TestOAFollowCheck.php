<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZaloNotificationService;

class TestOAFollowCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:oa-follow-check {zalo_gid?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Zalo OA follow check functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== TEST ZALO OA FOLLOW CHECK ===');
        
        $zaloGid = $this->argument('zalo_gid') ?: '5170627724267093288';
        
        try {
            $zaloService = new ZaloNotificationService();
            
            $this->info("Testing with zalo_gid: $zaloGid");
            $this->line('----------------------------------------');
            
            $result = $zaloService->checkUserFollowOA($zaloGid);
            
            $this->line('Result:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('');
            
            if ($result['success']) {
                $this->info('✅ Service hoạt động bình thường');
                if (isset($result['data']['is_following'])) {
                    $status = $result['data']['is_following'] ? 'Đã follow' : 'Chưa follow';
                    $this->line("Trạng thái follow OA: $status");
                }
            } else {
                $this->error('❌ Service có lỗi: ' . $result['message']);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
        
        $this->info('=== TEST COMPLETED ===');
    }
}
