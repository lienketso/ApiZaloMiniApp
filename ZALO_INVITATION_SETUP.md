# Hướng dẫn setup chức năng mời thành viên

## 1. Cấu hình môi trường

Thêm các biến sau vào file `.env`:

```env
# Zalo Business API
ZALO_ACCESS_TOKEN=your_zalo_access_token_here
ZALO_BUSINESS_ID=your_business_id_here
ZALO_APP_ID=your_app_id_here
ZALO_APP_SECRET=your_app_secret_here

# Zalo Notification Service Templates
ZALO_INVITATION_TEMPLATE_ID=12345
ZALO_WELCOME_TEMPLATE_ID=12346

# Frontend URL
APP_URL=https://your-app.com
```

## 2. Tạo Zalo Business Account

1. **Đăng ký tài khoản Zalo Business** tại: https://business.zalo.me/
2. **Xác thực doanh nghiệp** theo hướng dẫn
3. **Tạo ứng dụng** và lấy access token
4. **Kích hoạt ZNS (Zalo Notification Service)**

## 3. ZNS API Endpoints

### API Documentation:
- **Chính thức**: https://developers.zalo.me/docs/zalo-notification-service/bat-dau/gioi-thieu-zalo-notification-service
- **Send Notification**: `POST https://business.openapi.zalo.me/notification/send`

### API Payload Format:
```json
{
    "phone": "0123456789",
    "template_id": "12345",
    "template_data": [
        {"key": "club_name", "value": "Tên Club"},
        {"key": "invite_message", "value": "Lời nhắn mời"},
        {"key": "action_text", "value": "Hướng dẫn hành động"}
    ],
    "access_token": "your_access_token"
}
```

## 4. Tạo ZNS Templates

### Template mời thành viên (ID: 12345):
```
Xin chào! Bạn được mời tham gia câu lạc bộ {{club_name}}.

{{invite_message}}

{{action_text}}: {{invite_link}}

Lời mời có hiệu lực trong 7 ngày.
```

### Template chào mừng (ID: 12346):
```
Chào mừng bạn đã tham gia câu lạc bộ {{club_name}}!

{{welcome_message}}

{{next_steps}}

Cảm ơn bạn!
```

## 5. Chạy Migration

```bash
php artisan migrate
```

## 6. Test ZNS API

### Test kết nối cơ bản:
```bash
# Test API connection
php artisan zns:test

# Test với template cụ thể
php artisan zns:test --template=12345

# Test gửi notification
php artisan zns:test --phone=0123456789 --template=12345
```

### Test qua API endpoint:
```bash
# Test connection
GET /api/test-zns

# Test tạo lời mời
POST /api/invitations
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "admin_zalo_gid"
}
```

## 7. Luồng hoạt động

1. **Admin tạo lời mời**: Nhập số điện thoại → hệ thống tạo invite_token và gửi ZNS
2. **Thành viên nhận ZNS**: Click vào link → mở Mini App
3. **Hệ thống xác thực**: Lấy zalo_gid từ ZMP SDK
4. **Xử lý lời mời**: Backend insert vào users + user_clubs
5. **Hoàn tất**: Thành viên đã tham gia club

## 8. Lưu ý bảo mật

- `invite_token` có thời hạn 7 ngày
- Chỉ admin mới có quyền tạo/hủy lời mời
- ZNS được gửi qua Zalo Business API đã được xác thực
- Tất cả thao tác đều được log để theo dõi

## 9. Troubleshooting

### Nếu ZNS không gửi được:
1. **Kiểm tra access token**: Đảm bảo `ZALO_ACCESS_TOKEN` hợp lệ
2. **Kiểm tra template ID**: Đảm bảo template đã được tạo trong Zalo Business
3. **Kiểm tra logs**: Xem `storage/logs/laravel.log` để debug
4. **Test connection**: Sử dụng `php artisan zns:test` để kiểm tra

### Nếu lời mời không hoạt động:
1. **Kiểm tra invite_token**: Đảm bảo token đúng và chưa hết hạn
2. **Kiểm tra quyền**: Đảm bảo user có quyền admin
3. **Kiểm tra database**: Đảm bảo bảng invitations đã được tạo

### Common ZNS Errors:
- **404 "empty api"**: API endpoint đúng nhưng cần payload hợp lệ
- **401 Unauthorized**: Access token không hợp lệ hoặc hết hạn
- **400 Bad Request**: Template data không đúng format

## 10. Monitoring & Logs

### Log files:
- **Laravel logs**: `storage/logs/laravel.log`
- **ZNS logs**: Tự động log trong InvitationController

### Metrics cần theo dõi:
- Số lượng ZNS gửi thành công/thất bại
- Thời gian xử lý lời mời
- Số lượng lời mời được chấp nhận/từ chối
- Error rate của ZNS API

## 11. Production Checklist

- [ ] Zalo Business account đã được xác thực
- [ ] ZNS templates đã được tạo và approved
- [ ] Access token có đủ quyền gửi ZNS
- [ ] Environment variables đã được cấu hình
- [ ] Database migration đã chạy thành công
- [ ] Test ZNS API thành công
- [ ] Frontend integration hoàn tất
- [ ] Monitoring và alerting đã setup

## 12. Support & Resources

- **Zalo Developer Portal**: https://developers.zalo.me/
- **Zalo Business**: https://business.zalo.me/
- **ZNS Documentation**: https://developers.zalo.me/docs/zalo-notification-service/
- **API Status**: Kiểm tra status.zalo.me nếu có vấn đề
