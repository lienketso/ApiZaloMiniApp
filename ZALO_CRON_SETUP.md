# Zalo Token Auto Refresh Cron Job Setup

## 📋 Tổng quan

Hệ thống tự động refresh Zalo OA access token sử dụng Laravel Scheduler để:
- Kiểm tra token trong database mỗi 5 phút
- Tự động refresh token khi gần hết hạn (trước 5 phút)
- Cập nhật lại database với token mới
- Log tất cả hoạt động

## 🔧 Cấu hình đã hoàn thành

### 1. Artisan Command
- **File**: `app/Console/Commands/RefreshZaloTokenCommand.php`
- **Command**: `php artisan zalo:refresh-token`
- **Options**:
  - `--force`: Force refresh ngay cả khi token chưa hết hạn
  - `--check-only`: Chỉ kiểm tra status, không refresh

### 2. Laravel Scheduler
- **File**: `app/Providers/SchedulerServiceProvider.php`
- **Tần suất**: Mỗi 5 phút
- **Log**: `storage/logs/zalo-token-refresh.log`

### 3. Service Provider
- **File**: `bootstrap/app.php` (đã đăng ký)
- **Provider**: `App\Providers\SchedulerServiceProvider::class`

## 🚀 Cách sử dụng

### Chạy thủ công
```bash
# Kiểm tra status token
php artisan zalo:refresh-token --check-only

# Force refresh token
php artisan zalo:refresh-token --force

# Kiểm tra bình thường (tự động refresh nếu cần)
php artisan zalo:refresh-token
```

### Chạy Laravel Scheduler
```bash
# Chạy scheduler (cần thêm vào crontab)
* * * * * cd /Applications/XAMPP/xamppfiles/htdocs/club && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Monitoring

### Log files
- **Refresh logs**: `storage/logs/zalo-token-refresh.log`
- **Check logs**: `storage/logs/zalo-token-check.log`
- **Laravel logs**: `storage/logs/laravel.log`

### Kiểm tra status
```bash
# Xem log refresh
tail -f storage/logs/zalo-token-refresh.log

# Xem log check
tail -f storage/logs/zalo-token-check.log

# Xem Laravel log
tail -f storage/logs/laravel.log
```

## ⚙️ Cấu hình Cron Job

### 1. Thêm vào crontab
```bash
# Mở crontab
crontab -e

# Thêm dòng sau (thay đổi đường dẫn cho phù hợp)
* * * * * cd /Applications/XAMPP/xamppfiles/htdocs/club && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Kiểm tra crontab
```bash
# Xem crontab hiện tại
crontab -l

# Kiểm tra cron service
sudo systemctl status cron
```

## 🔍 Troubleshooting

### 1. Command không chạy
```bash
# Kiểm tra command có tồn tại
php artisan list | grep zalo

# Test command
php artisan zalo:refresh-token --check-only
```

### 2. Scheduler không chạy
```bash
# Kiểm tra schedule list
php artisan schedule:list

# Chạy scheduler thủ công
php artisan schedule:run
```

### 3. Token refresh thất bại
- Kiểm tra `ZALO_APP_ID` và `ZALO_APP_SECRET` trong `.env`
- Kiểm tra refresh token trong database
- Xem log chi tiết trong `storage/logs/`

## 📈 Tần suất chạy

| Task | Tần suất | Mục đích |
|------|----------|----------|
| Token refresh check | Mỗi 5 phút | Kiểm tra và refresh token nếu cần |
| Token status check | Mỗi giờ | Monitor token status |

## 🛡️ Bảo mật

- Token được lưu trong database với encryption
- Log không chứa token đầy đủ (chỉ hiển thị 20 ký tự đầu)
- Refresh token được bảo vệ và chỉ sử dụng khi cần

## 📝 Log Format

### Refresh Log
```
[2025-09-13 15:33:12] local.INFO: RefreshZaloTokenCommand: Token refreshed successfully {"old_token":"_9GR8xZ0McNub1efiiXK...","new_token":"uxs83crFXWddlOa4PqU8...","expires_in":90000}
```

### Check Log
```
[2025-09-13 15:30:00] local.INFO: RefreshZaloTokenCommand: Token status check completed
```

## ✅ Test Commands

```bash
# Test toàn bộ hệ thống
php test_cron_command.php

# Test command riêng lẻ
php artisan zalo:refresh-token --check-only
php artisan zalo:refresh-token --force
php artisan zalo:refresh-token
```
