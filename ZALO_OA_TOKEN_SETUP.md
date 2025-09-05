# 🔐 Hướng dẫn lấy Zalo OA Access Token

## Bước 1: Tạo file .env

Tạo file `.env` trong thư mục gốc của project:

```bash
cp .env.example .env
```

Hoặc tạo file `.env` mới với nội dung:

```env
# Zalo OA Configuration (MIỄN PHÍ)
ZALO_OA_ACCESS_TOKEN=your_zalo_oa_access_token_here
ZALO_APP_ID=your_zalo_app_id_here
ZALO_OA_ID=your_zalo_oa_id_here

# Zalo OAuth v4 Configuration (Tùy chọn)
ZALO_APP_SECRET=your_zalo_app_secret_here
```

## Bước 2: Lấy Zalo OA Access Token

### 2.1. Đăng nhập Zalo Business
1. Truy cập: https://business.zalo.me/
2. Đăng nhập bằng tài khoản Zalo của bạn

### 2.2. Tạo Official Account (OA)
1. Trong Zalo Business, chọn "Tạo Official Account"
2. Điền thông tin OA của bạn
3. Xác thực tài khoản (nếu cần)

### 2.3. Lấy Access Token
1. Vào **Quản lý OA** → **Cài đặt** → **Tích hợp**
2. Tìm phần **"Access Token"** hoặc **"API Token"**
3. Copy token và paste vào file `.env`

### 2.4. Lấy App ID và OA ID
1. **ZALO_APP_ID**: Tìm trong phần **"Ứng dụng"** hoặc **"App"**
2. **ZALO_OA_ID**: Tìm trong phần **"Thông tin OA"** hoặc **"OA Info"**

## Bước 3: Test cấu hình

Chạy file test để kiểm tra:

```bash
php test_zalo_notification.php
```

## Bước 4: Cấu hình Zalo Mini App (Tùy chọn)

Nếu bạn muốn sử dụng Zalo Mini App:

### 4.1. Tạo Mini App
1. Truy cập: https://developers.zalo.me/
2. Tạo ứng dụng mới
3. Chọn loại "Mini App"

### 4.2. Cấu hình Mini App
1. Thêm domain của bạn vào **"Whitelist Domain"**
2. Cấu hình **"Callback URL"**
3. Lấy **App ID** và **App Secret**

### 4.3. Cập nhật .env
```env
ZALO_APP_ID=your_mini_app_id_here
ZALO_APP_SECRET=your_mini_app_secret_here
```

## Troubleshooting

### Lỗi "ZALO_OA_ACCESS_TOKEN chưa được cấu hình"
1. Kiểm tra file `.env` có tồn tại không
2. Kiểm tra tên biến có đúng không: `ZALO_OA_ACCESS_TOKEN`
3. Kiểm tra có dấu cách thừa không
4. Restart server sau khi cập nhật .env

### Lỗi "Invalid access token"
1. Kiểm tra token có còn hạn không
2. Kiểm tra token có đúng không
3. Lấy token mới từ Zalo Business

### Lỗi "Permission denied"
1. Kiểm tra OA có quyền gửi tin nhắn không
2. Kiểm tra OA có được xác thực chưa
3. Liên hệ Zalo support nếu cần

## Test API

Sau khi cấu hình xong, test API:

```bash
# Test gửi broadcast miễn phí
curl -X POST http://localhost/club/public/api/notifications/send-attendance \
  -H 'Content-Type: application/json' \
  -d '{"club_id": 1, "method": "broadcast"}'

# Test gửi thông báo test
curl -X POST http://localhost/club/public/api/notifications/test \
  -H 'Content-Type: application/json' \
  -d '{"zalo_gid": "YOUR_ZALO_GID"}'
```

## Lưu ý quan trọng

1. **MIỄN PHÍ**: Sử dụng Tin Truyền thông OA không mất phí
2. **BẢO MẬT**: Không commit file `.env` lên Git
3. **BACKUP**: Lưu trữ token an toàn
4. **RENEW**: Token có thể hết hạn, cần gia hạn định kỳ
