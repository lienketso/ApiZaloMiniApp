<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Club;
use App\Services\SubscriptionService;

class UpdateSubscriptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật trạng thái subscription của tất cả clubs';

    protected $subscriptionService;

    /**
     * Execute the console command.
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    public function handle()
    {
        $this->info('Bắt đầu cập nhật trạng thái subscription...');

        $clubs = Club::all();
        $updatedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($clubs->count());
        $progressBar->start();

        foreach ($clubs as $club) {
            try {
                $this->subscriptionService->checkAndUpdateStatus($club);
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Lỗi khi cập nhật club {$club->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Hoàn thành! Đã cập nhật {$updatedCount} clubs, {$errorCount} lỗi.");

        if ($errorCount > 0) {
            $this->warn("Có {$errorCount} clubs gặp lỗi khi cập nhật.");
        }

        return 0;
    }
}
