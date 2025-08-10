# Hướng dẫn cài đặt và cấu hình

## 1. Cấu hình Database

Tạo file `.env` trong thư mục gốc với nội dung sau:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=club
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## 2. Tạo APP_KEY

Chạy lệnh sau để tạo APP_KEY:

```bash
php artisan key:generate
```

## 3. Chạy Migrations

```bash
php artisan migrate
```

## 4. Chạy Seeders (nếu cần)

```bash
php artisan db:seed
```

## 5. Kiểm tra API

Sử dụng file `test_zalo_auth.php` để test API:

```bash
php test_zalo_auth.php
```

## 6. Các API endpoints

- `POST /api/auth/zalo/auto-login` - Auto login/register
- `POST /api/auth/zalo/login` - Login với Zalo GID
- `POST /api/auth/zalo/register` - Register user mới
- `POST /api/auth/zalo/login-or-register` - Login hoặc register
- `GET /api/auth/check` - Kiểm tra trạng thái xác thực
- `POST /api/auth/logout` - Đăng xuất (cần token)

## 7. Cấu hình XAMPP

Đảm bảo XAMPP đang chạy:
- Apache
- MySQL

## 8. Kiểm tra lỗi

Nếu gặp lỗi 500, kiểm tra:
1. File `.env` có đúng cấu hình database không
2. Database có tồn tại không
3. APP_KEY đã được tạo chưa
4. Migrations đã chạy thành công chưa
5. Log files trong `storage/logs/`

## 9. Test với Postman hoặc cURL

```bash
curl -X POST http://localhost/club/public/api/auth/zalo/auto-login \
  -H "Content-Type: application/json" \
  -d '{
    "zalo_gid": "test_user_123",
    "name": "Test User",
    "phone": "0123456789"
  }'
```
