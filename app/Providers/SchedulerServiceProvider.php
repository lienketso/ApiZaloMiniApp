<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\RefreshZaloTokenCommand;

class SchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Đăng ký scheduled tasks
        $this->app->booted(function () {
            Schedule::command('zalo:refresh-token')
                ->everyFiveMinutes() // Chạy mỗi 5 phút
                ->withoutOverlapping() // Không chạy đồng thời
                ->runInBackground() // Chạy background
                ->appendOutputTo(storage_path('logs/zalo-token-refresh.log')); // Log output

            // Chạy command check-only mỗi giờ để monitor
            Schedule::command('zalo:refresh-token --check-only')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/zalo-token-check.log'));
        });
    }
}