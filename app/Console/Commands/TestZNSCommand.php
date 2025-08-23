<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZaloNotificationService;

class TestZNSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zns:test {--phone= : Phone number to test} {--template= : Template ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Zalo Notification Service (ZNS) API connection and sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Zalo Notification Service (ZNS) API...');
        
        $znsService = new ZaloNotificationService();
        
        // Test 1: Connection test
        $this->info('1. Testing API connection...');
        $connectionResult = $znsService->testConnection();
        
        if ($connectionResult['success']) {
            $this->info('✅ API connection successful');
            $this->line('Status: ' . $connectionResult['message']);
        } else {
            $this->error('❌ API connection failed');
            $this->line('Error: ' . $connectionResult['message']);
            return 1;
        }
        
        // Test 2: Template info (if template ID provided)
        $templateId = $this->option('template');
        if ($templateId) {
            $this->info('2. Getting template info for ID: ' . $templateId);
            $templateResult = $znsService->getTemplateInfo($templateId);
            
            if ($templateResult['success']) {
                $this->info('✅ Template info retrieved successfully');
                $this->line('Template data: ' . json_encode($templateResult['data'], JSON_PRETTY_PRINT));
            } else {
                $this->warn('⚠️ Failed to get template info');
                $this->line('Error: ' . $templateResult['message']);
            }
        }
        
        // Test 3: Send test notification (if phone provided)
        $phone = $this->option('phone');
        if ($phone) {
            $this->info('3. Testing notification sending to: ' . $phone);
            
            // Sử dụng template mời thành viên để test
            $templateId = config('services.zalo.invitation_template_id', '12345');
            $testResult = $znsService->sendInvitationNotification(
                $phone, 
                'Test Club', 
                'https://example.com/invite/test123'
            );
            
            if ($testResult['success']) {
                $this->info('✅ Test notification sent successfully');
                $this->line('Response: ' . json_encode($testResult['data'], JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Test notification failed');
                $this->line('Error: ' . $testResult['message']);
                if (isset($testResult['error'])) {
                    $this->line('Details: ' . json_encode($testResult['error'], JSON_PRETTY_PRINT));
                }
            }
        }
        
        $this->info('ZNS testing completed!');
        return 0;
    }
}
