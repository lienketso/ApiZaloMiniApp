# 🚀 Hướng dẫn cấu hình nhanh Zalo OA

## Bước 1: Tạo file .env

```bash
# Tạo file .env
touch .env

# Thêm nội dung sau vào file .env
cat >> .env << 'EOF'
# Zalo OA Configuration (MIỄN PHÍ)
ZALO_OA_ACCESS_TOKEN=your_zalo_oa_access_token_here
ZALO_APP_ID=your_zalo_app_id_here
ZALO_OA_ID=your_zalo_oa_id_here

# Zalo OAuth v4 Configuration (Tùy chọn)
ZALO_APP_SECRET=your_zalo_app_secret_here
EOF
```

## Bước 2: Lấy Zalo OA Access Token

### 2.1. Truy cập Zalo Business
- Mở: https://business.zalo.me/
- Đăng nhập bằng tài khoản Zalo

### 2.2. Tạo hoặc chọn Official Account
- Nếu chưa có OA: Tạo mới
- Nếu đã có OA: Chọn OA cần sử dụng

### 2.3. Lấy Access Token
- Vào **Quản lý OA** → **Cài đặt** → **Tích hợp**
- Tìm **"Access Token"** hoặc **"API Token"**
- Copy token (dạng: `abc123def456...`)

### 2.4. Cập nhật file .env
```bash
# Thay thế your_zalo_oa_access_token_here bằng token thật
sed -i 's/your_zalo_oa_access_token_here/ACTUAL_TOKEN_HERE/g' .env
```

## Bước 3: Test ngay

```bash
# Chạy test với Zalo ID cụ thể
php test_zalo_notification.php
```

## Bước 4: Test API endpoints

```bash
# Test gửi thông báo đến Zalo ID 5170627724267093288
curl -X POST http://localhost/club/public/api/notifications/test \
  -H 'Content-Type: application/json' \
  -d '{"zalo_gid": "5170627724267093288"}'

# Test gửi broadcast miễn phí
curl -X POST http://localhost/club/public/api/notifications/send-attendance \
  -H 'Content-Type: application/json' \
  -d '{"club_id": 1, "zalo_gid": "5170627724267093288", "method": "broadcast"}'
```

## Lưu ý quan trọng

1. **MIỄN PHÍ**: Sử dụng Tin Truyền thông OA không mất phí
2. **Zalo ID**: 5170627724267093288 đã được hardcode trong test
3. **Bảo mật**: Không commit file .env lên Git
4. **Debug**: Kiểm tra logs trong `storage/logs/laravel.log`

## Troubleshooting

### Lỗi "ZALO_OA_ACCESS_TOKEN chưa được cấu hình"
```bash
# Kiểm tra file .env
cat .env | grep ZALO_OA_ACCESS_TOKEN

# Nếu chưa có, thêm vào
echo "ZALO_OA_ACCESS_TOKEN=your_token_here" >> .env
```

### Lỗi "Invalid access token"
- Kiểm tra token có còn hạn không
- Lấy token mới từ Zalo Business
- Kiểm tra OA có quyền gửi tin nhắn không

### Lỗi "User not found"
- Kiểm tra Zalo ID có đúng không
- Kiểm tra user có follow OA không
- Kiểm tra OA có được xác thực chưa

## Tài liệu tham khảo

- [Zalo OA API Documentation](https://developers.zalo.me/docs/official-account/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new)
- [Zalo Business](https://business.zalo.me/)
- [Zalo Developers](https://developers.zalo.me/)
