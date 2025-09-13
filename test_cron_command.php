<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ZaloToken;

echo "🕐 Test Zalo Token Refresh Cron Command\n";
echo "=======================================\n\n";

// Test 1: Show current token status
echo "1️⃣ Current token status in database...\n";
$token = ZaloToken::first();
if ($token) {
    echo "✅ Token found:\n";
    echo "   - Access token: " . (empty($token->access_token) ? 'Empty' : substr($token->access_token, 0, 20) . '...') . "\n";
    echo "   - Refresh token: " . (empty($token->refresh_token) ? 'Empty' : substr($token->refresh_token, 0, 20) . '...') . "\n";
    echo "   - Expires in: " . ($token->expires_in ?? 'Unknown') . " seconds\n";
    echo "   - Last refreshed: " . ($token->last_refreshed_at ?? 'Never') . "\n";
    
    if ($token->expires_in && $token->last_refreshed_at) {
        $secondsSinceRefresh = now()->diffInSeconds($token->last_refreshed_at);
        $remainingTime = $token->expires_in - $secondsSinceRefresh;
        echo "   - Seconds since refresh: " . $secondsSinceRefresh . "\n";
        echo "   - Remaining time: " . $remainingTime . " seconds\n";
        echo "   - Will expire in: " . ($remainingTime > 0 ? gmdate('H:i:s', $remainingTime) : 'Already expired') . "\n";
        
        // Kiểm tra nếu cần refresh (trước 5 phút)
        $bufferTime = 300; // 5 phút
        $needsRefresh = $secondsSinceRefresh > ($token->expires_in - $bufferTime);
        echo "   - Needs refresh (5min buffer): " . ($needsRefresh ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "❌ No token found in database\n";
}

echo "\n";

// Test 2: Test command via Artisan
echo "2️⃣ Testing commands via Artisan...\n";

echo "   Running: /Applications/XAMPP/xamppfiles/bin/php artisan zalo:refresh-token --check-only\n";
$output = shell_exec('cd /Applications/XAMPP/xamppfiles/htdocs/club && /Applications/XAMPP/xamppfiles/bin/php artisan zalo:refresh-token --check-only 2>&1');
echo "   Output:\n" . $output . "\n";

echo "   Running: /Applications/XAMPP/xamppfiles/bin/php artisan zalo:refresh-token\n";
$output2 = shell_exec('cd /Applications/XAMPP/xamppfiles/htdocs/club && /Applications/XAMPP/xamppfiles/bin/php artisan zalo:refresh-token 2>&1');
echo "   Output:\n" . $output2 . "\n";

// Test 3: Check scheduler
echo "3️⃣ Testing Laravel Scheduler...\n";
$output3 = shell_exec('cd /Applications/XAMPP/xamppfiles/htdocs/club && /Applications/XAMPP/xamppfiles/bin/php artisan schedule:list 2>&1');
echo "   Schedule list:\n" . $output3 . "\n";

// Test 4: Check log files
echo "4️⃣ Checking log files...\n";
$logFiles = [
    'storage/logs/zalo-token-refresh.log',
    'storage/logs/zalo-token-check.log',
    'storage/logs/laravel.log'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        $size = filesize($logFile);
        echo "   ✅ {$logFile}: {$size} bytes\n";
        
        // Show last few lines
        $lines = file($logFile);
        $lastLines = array_slice($lines, -3);
        echo "   Last 3 lines:\n";
        foreach ($lastLines as $line) {
            echo "     " . trim($line) . "\n";
        }
    } else {
        echo "   ❌ {$logFile}: Not found\n";
    }
    echo "\n";
}

echo "🎉 Cron command test completed!\n";
