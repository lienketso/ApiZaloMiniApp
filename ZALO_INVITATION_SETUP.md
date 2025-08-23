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
FRONTEND_URL=https://your-app.com
```

## 2. Tạo Zalo Business Account

1. Đăng ký tài khoản Zalo Business tại: https://business.zalo.me/
2. Xác thực doanh nghiệp
3. Tạo ứng dụng và lấy access token

## 3. Tạo ZNS Templates

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

## 4. Chạy Migration

```bash
php artisan migrate
```

## 5. Test API

### Tạo lời mời:
```bash
POST /api/invitations
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "admin_zalo_gid"
}
```

### Chấp nhận lời mời:
```bash
POST /api/invitations/accept
{
    "invite_token": "generated_token_here",
    "zalo_gid": "user_zalo_gid"
}
```

## 6. Luồng hoạt động

1. **Admin tạo lời mời**: Nhập số điện thoại → hệ thống tạo invite_token và gửi ZNS
2. **Thành viên nhận ZNS**: Click vào link → mở Mini App
3. **Hệ thống xác thực**: Lấy zalo_gid từ ZMP SDK
4. **Xử lý lời mời**: Backend insert vào users + user_clubs
5. **Hoàn tất**: Thành viên đã tham gia club

## 7. Lưu ý bảo mật

- `invite_token` có thời hạn 7 ngày
- Chỉ admin mới có quyền tạo/hủy lời mời
- ZNS được gửi qua Zalo Business API đã được xác thực
- Tất cả thao tác đều được log để theo dõi

## 8. Troubleshooting

### Nếu ZNS không gửi được:
1. Kiểm tra `ZALO_ACCESS_TOKEN` có hợp lệ không
2. Kiểm tra template ID có đúng không
3. Kiểm tra logs trong `storage/logs/laravel.log`

### Nếu lời mời không hoạt động:
1. Kiểm tra `invite_token` có đúng không
2. Kiểm tra token có hết hạn không
3. Kiểm tra user có quyền admin không

## 9. Monitoring

- Log tất cả thao tác tạo/hủy/chấp nhận lời mời
- Theo dõi số lượng ZNS gửi thành công/thất bại
- Kiểm tra thời gian xử lý lời mời
