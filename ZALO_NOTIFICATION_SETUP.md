# Hướng dẫn cấu hình Zalo OA Notification (MIỄN PHÍ)

## Tổng quan
Chức năng gửi thông báo điểm danh qua **Tin Truyền thông OA** của Zalo - **HOÀN TOÀN MIỄN PHÍ** - cho phép gửi tin nhắn broadcast đến tất cả người dùng đã follow Official Account khi có sự kiện điểm danh.

## ⚠️ Lưu ý quan trọng
- **MIỄN PHÍ**: Sử dụng Tin Truyền thông OA, không mất phí
- **Broadcast**: Gửi đến tất cả người đã follow OA, không cần zalo_gid của từng user
- **Đơn giản**: Chỉ cần cấu hình OA access token

## Cấu hình cần thiết

### 1. Biến môi trường (.env)
Thêm các biến sau vào file `.env`:

```env
# Zalo OA Configuration (MIỄN PHÍ)
ZALO_OA_ACCESS_TOKEN=your_zalo_oa_access_token_here
ZALO_APP_ID=your_zalo_app_id_here
ZALO_OA_ID=your_zalo_oa_id_here

# Zalo OAuth v4 Configuration (Tùy chọn - cho xác thực nâng cao)
ZALO_APP_SECRET=your_zalo_app_secret_here
```

**Chỉ cần ZALO_OA_ACCESS_TOKEN là đủ để gửi broadcast miễn phí!**
**ZALO_APP_SECRET chỉ cần khi sử dụng OAuth v4 để xác thực người dùng.**

### 2. Lấy thông tin Zalo OA

#### ZALO_OA_ACCESS_TOKEN
1. Đăng nhập vào [Zalo Business](https://business.zalo.me/)
2. Vào **Quản lý ứng dụng** > **Ứng dụng của tôi**
3. Chọn ứng dụng của bạn
4. Vào tab **Cài đặt** > **Thông tin ứng dụng**
5. Copy **Access Token**

#### ZALO_APP_ID
1. Trong cùng trang **Thông tin ứng dụng**
2. Copy **App ID**

#### ZALO_OA_ID
1. Vào **Quản lý Official Account**
2. Chọn OA của bạn
3. Copy **OA ID** (số ID của Official Account)

## Cách sử dụng

### 1. Gửi thông báo từ Frontend
Trong trang điểm danh, có 3 tùy chọn gửi thông báo:

#### 🚀 Gửi thông báo (Tự động) - KHUYẾN NGHỊ
- Hệ thống tự động chọn phương pháp tối ưu:
  - **≤ 10 thành viên**: Gửi cá nhân hóa (có phí)
  - **> 10 thành viên**: Gửi broadcast (miễn phí)
- Chỉ gửi cho thành viên trong câu lạc bộ

#### 👤 Gửi cá nhân
- Gửi tin nhắn cá nhân hóa đến từng thành viên có zalo_gid
- **CÓ PHÍ** - chỉ gửi cho thành viên trong câu lạc bộ
- Phù hợp với câu lạc bộ nhỏ

#### 📢 Gửi broadcast
- Sử dụng **Tin Truyền thông OA** - **MIỄN PHÍ**
- Gửi đến tất cả người follow OA (có thể bao gồm người ngoài câu lạc bộ)
- Phù hợp với câu lạc bộ lớn

### 2. API Endpoints

#### Gửi thông báo (tự động/cá nhân/broadcast)
```bash
POST /api/notifications/send-attendance
Content-Type: application/json

{
  "club_id": 1,
  "zalo_gid": "user_zalo_gid",
  "method": "auto"  // "auto", "personal", "broadcast"
}
```

#### Gửi thông báo cá nhân hóa (có phí) - Legacy
```bash
POST /api/notifications/send-attendance-members
Content-Type: application/json

{
  "club_id": 1,
  "zalo_gid": "user_zalo_gid"
}
```

#### Test gửi thông báo
```bash
POST /api/notifications/test
Content-Type: application/json

{
  "zalo_gid": "user_zalo_gid"
}
```

## Test chức năng

### 1. Chạy test script
```bash
php test_zalo_notification.php
```

### 2. Test qua API
```bash
# Test gửi thông báo cho một user
curl -X POST http://localhost/club/public/api/notifications/test \
  -H 'Content-Type: application/json' \
  -d '{"zalo_gid": "YOUR_ZALO_GID"}'

# Test gửi thông báo cho tất cả thành viên
curl -X POST http://localhost/club/public/api/notifications/send-attendance \
  -H 'Content-Type: application/json' \
  -d '{"club_id": 1, "zalo_gid": "YOUR_ZALO_GID"}'
```

## Cấu trúc thông báo

### Tin Truyền thông OA (MIỄN PHÍ)
Thông báo được gửi dưới dạng text message với:
- **Text**: "📢 Thông báo điểm danh từ câu lạc bộ [Tên Club]!\n\nCó sự kiện điểm danh mới, hãy vào ứng dụng để tham gia!\n\n[Link Mini App]"
- **Đối tượng**: Tất cả người đã follow Official Account

### Tin nhắn cá nhân hóa (CÓ PHÍ)
Thông báo được gửi dưới dạng template button với:
- **Text**: "📢 Bạn có thông báo điểm danh từ câu lạc bộ"
- **Button**: "Vào điểm danh" - mở Mini App
- **Đối tượng**: Từng thành viên cụ thể có zalo_gid

## Xử lý lỗi

### Lỗi thường gặp:
1. **Zalo OA access token not configured**: Chưa cấu hình `ZALO_OA_ACCESS_TOKEN`
2. **Zalo App ID not configured**: Chưa cấu hình `ZALO_APP_ID` hoặc `ZALO_OA_ID`
3. **User không có zalo_gid**: Thành viên chưa liên kết với Zalo
4. **Zalo API error**: Lỗi từ phía Zalo (token hết hạn, quyền không đủ, etc.)

### Debug:
- Kiểm tra logs trong `storage/logs/laravel.log`
- Sử dụng test script để kiểm tra cấu hình
- Test với một user cụ thể trước khi gửi hàng loạt

## Lưu ý quan trọng

1. **Rate Limiting**: Zalo có giới hạn số lượng tin nhắn gửi trong một khoảng thời gian
2. **User Consent**: Chỉ gửi thông báo cho user đã đồng ý nhận
3. **Error Handling**: Luôn xử lý trường hợp gửi thất bại
4. **Testing**: Test kỹ trước khi triển khai production

## Troubleshooting

### Không gửi được thông báo
1. Kiểm tra cấu hình biến môi trường
2. Kiểm tra token có còn hạn không
3. Kiểm tra user có zalo_gid không
4. Kiểm tra logs để xem lỗi cụ thể

### Thông báo không hiển thị
1. Kiểm tra user có follow OA không
2. Kiểm tra template có đúng format không
3. Kiểm tra Mini App link có hoạt động không

## OAuth v4 - Xác thực nâng cao (Tùy chọn)

### Tính năng OAuth v4

Nếu bạn muốn xác thực người dùng thông qua Zalo OAuth v4, hệ thống đã hỗ trợ:

#### 1. Tạo URL xác thực
```bash
POST /api/zalo/oauth/auth-url
Content-Type: application/json

{
  "redirect_uri": "https://your-domain.com/callback",
  "state": "optional_state"
}
```

#### 2. Lấy Access Token
```bash
POST /api/zalo/oauth/access-token
Content-Type: application/json

{
  "code": "authorization_code_from_zalo",
  "redirect_uri": "https://your-domain.com/callback"
}
```

#### 3. Làm mới Access Token
```bash
POST /api/zalo/oauth/refresh-token
Content-Type: application/json

{
  "refresh_token": "refresh_token_from_previous_response"
}
```

#### 4. Lấy thông tin người dùng
```bash
POST /api/zalo/oauth/user-info
Content-Type: application/json

{
  "access_token": "access_token_from_previous_response"
}
```

### Test OAuth v4

Sử dụng file `test_zalo_oauth.php`:

```bash
php test_zalo_oauth.php
```

### Cấu hình OAuth v4

Thêm vào file `.env`:

```env
# Zalo OAuth v4 Configuration (Tùy chọn - cho xác thực nâng cao)
ZALO_APP_SECRET=your_zalo_app_secret_here
```

**Lưu ý**: OAuth v4 chỉ cần thiết khi bạn muốn xác thực người dùng thông qua Zalo. Để gửi thông báo broadcast miễn phí, bạn chỉ cần `ZALO_OA_ACCESS_TOKEN`.
