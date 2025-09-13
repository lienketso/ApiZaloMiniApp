#!/bin/bash

# Zalo Token Auto Refresh - Crontab Setup Script
# This script sets up the crontab for Laravel Scheduler

echo "🕐 Setting up Zalo Token Auto Refresh Crontab"
echo "============================================="
echo ""

# Get the current directory
PROJECT_DIR="/home/apilks/domains/api.lienketso.vn/public_html"
PHP_PATH="/usr/local/bin/php"

echo "📋 Configuration:"
echo "   Project Directory: $PROJECT_DIR"
echo "   PHP Path: $PHP_PATH"
echo ""

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo "❌ Project directory not found: $PROJECT_DIR"
    exit 1
fi

# Check if PHP exists
if [ ! -f "$PHP_PATH" ]; then
    echo "❌ PHP not found: $PHP_PATH"
    exit 1
fi

# Test Laravel command
echo "🧪 Testing Laravel command..."
cd "$PROJECT_DIR"
$PHP_PATH artisan --version
if [ $? -eq 0 ]; then
    echo "✅ Laravel command working"
else
    echo "❌ Laravel command failed"
    exit 1
fi

echo ""

# Test Zalo command
echo "🧪 Testing Zalo refresh command..."
$PHP_PATH artisan zalo:refresh-token --check-only
if [ $? -eq 0 ]; then
    echo "✅ Zalo refresh command working"
else
    echo "❌ Zalo refresh command failed"
    exit 1
fi

echo ""

# Create crontab entry
CRON_ENTRY="* * * * * cd $PROJECT_DIR && $PHP_PATH artisan schedule:run >> /dev/null 2>&1"

echo "📝 Crontab entry to add:"
echo "   $CRON_ENTRY"
echo ""

# Check if crontab already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "⚠️  Crontab entry already exists!"
    echo "Current crontab:"
    crontab -l
    echo ""
    echo "Do you want to replace it? (y/N)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        # Remove existing schedule:run entries
        crontab -l 2>/dev/null | grep -v "schedule:run" | crontab -
        # Add new entry
        (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
        echo "✅ Crontab updated successfully"
    else
        echo "❌ Crontab setup cancelled"
        exit 1
    fi
else
    # Add new crontab entry
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    echo "✅ Crontab added successfully"
fi

echo ""

# Verify crontab
echo "🔍 Verifying crontab..."
crontab -l
echo ""

# Test scheduler
echo "🧪 Testing Laravel Scheduler..."
$PHP_PATH artisan schedule:list
echo ""

echo "🎉 Crontab setup completed!"
echo ""
echo "📊 Monitoring:"
echo "   - Laravel logs: $PROJECT_DIR/storage/logs/laravel.log"
echo "   - Refresh logs: $PROJECT_DIR/storage/logs/zalo-token-refresh.log"
echo "   - Check logs: $PROJECT_DIR/storage/logs/zalo-token-check.log"
echo ""
echo "🔧 Manual commands:"
echo "   - Check token: $PHP_PATH artisan zalo:refresh-token --check-only"
echo "   - Force refresh: $PHP_PATH artisan zalo:refresh-token --force"
echo "   - Run scheduler: $PHP_PATH artisan schedule:run"
echo "   - List schedules: $PHP_PATH artisan schedule:list"
echo ""
echo "✅ Setup complete! The cron job will run every minute and check/refresh Zalo tokens as needed."
