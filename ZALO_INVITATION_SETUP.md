# Hướng dẫn setup chức năng mời thành viên (Không dùng ZNS)

## 1. Cấu hình môi trường

Thêm các biến sau vào file `.env`:

```env
# Frontend URL
APP_URL=https://your-app.com

# ZNS (tạm thời không dùng)
# ZALO_ACCESS_TOKEN=your_zalo_access_token_here
# ZALO_INVITATION_TEMPLATE_ID=12345
# ZALO_WELCOME_TEMPLATE_ID=12346
```

## 2. Luồng hoạt động mới (không dùng ZNS)

### **Admin tạo lời mời:**
1. Admin nhập số điện thoại thành viên
2. Hệ thống tạo record trong bảng `invitations`
3. **KHÔNG gửi ZNS** - chỉ lưu vào DB

### **Thành viên tham gia:**
1. Thành viên truy cập Mini App
2. Click vào câu lạc bộ muốn tham gia
3. Hệ thống tự động kiểm tra:
   - Nếu có invitation → Tự động tham gia
   - Nếu không có → Thông báo cần được mời

## 3. API Endpoints

### **Invitations (Admin):**
```bash
# Tạo lời mời (không gửi ZNS)
POST /api/invitations
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "admin_zalo_gid"
}

# Xem danh sách lời mời
GET /api/invitations?club_id=1&zalo_gid=admin_zalo_gid

# Hủy lời mời
DELETE /api/invitations/{id}
```

### **Club Membership (User):**
```bash
# Tham gia club (click vào club)
POST /api/club-membership/join
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "user_zalo_gid"
}

# Kiểm tra trạng thái membership
POST /api/club-membership/check
{
    "club_id": 1,
    "zalo_gid": "user_zalo_gid"
}

# Xem danh sách club có thể tham gia
GET /api/club-membership/available-clubs?phone=0123456789
```

## 4. Chạy Migration

```bash
php artisan migrate
```

## 5. Test hệ thống

### **Test toàn bộ hệ thống:**
```bash
php test_invitation_system.php
```

### **Test qua API:**
```bash
# Test tạo lời mời
POST /api/invitations
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "admin_zalo_gid"
}

# Test tham gia club
POST /api/club-membership/join
{
    "club_id": 1,
    "phone": "0123456789",
    "zalo_gid": "user_zalo_gid"
}
```

## 6. Luồng hoạt động chi tiết

1. **Admin tạo lời mời**: Nhập số điện thoại → hệ thống tạo record trong `invitations`
2. **Thành viên truy cập Mini App**: Đăng nhập bằng ZMP SDK → lấy `zalo_gid`
3. **Thành viên click vào club**: Frontend gọi API `/api/club-membership/join`
4. **Backend xử lý**: 
   - Kiểm tra có invitation cho số điện thoại không
   - Nếu có → Tự động tham gia club
   - Nếu không → Thông báo cần được mời
5. **Hoàn tất**: Thành viên đã tham gia club hoặc nhận thông báo

## 7. Lưu ý bảo mật

- `invite_token` có thời hạn 7 ngày
- Chỉ admin mới có quyền tạo/hủy lời mời
- **Không gửi ZNS** - chỉ lưu trong DB
- Tất cả thao tác đều được log để theo dõi

## 8. Troubleshooting

### **Nếu lời mời không hoạt động:**
1. **Kiểm tra invitation**: Đảm bảo record tồn tại trong bảng `invitations`
2. **Kiểm tra thời hạn**: Đảm bảo invitation chưa hết hạn
3. **Kiểm tra quyền**: Đảm bảo user có quyền admin
4. **Kiểm tra database**: Đảm bảo bảng invitations đã được tạo

### **Nếu user không thể tham gia:**
1. **Kiểm tra số điện thoại**: Đảm bảo số điện thoại khớp với invitation
2. **Kiểm tra zalo_gid**: Đảm bảo user đã đăng nhập
3. **Kiểm tra logs**: Xem `storage/logs/laravel.log` để debug

## 9. Monitoring & Logs

### **Log files:**
- **Laravel logs**: `storage/logs/laravel.log`
- **Membership logs**: Tự động log trong ClubMembershipService

### **Metrics cần theo dõi:**
- Số lượng invitation được tạo
- Số lượng user tham gia thành công
- Số lượng user bị từ chối (không có invitation)
- Thời gian xử lý yêu cầu tham gia

## 10. Production Checklist

- [ ] Database migration đã chạy thành công
- [ ] Bảng `invitations` đã được tạo
- [ ] ClubMembershipService đã được test
- [ ] API endpoints hoạt động bình thường
- [ ] Frontend integration hoàn tất
- [ ] Monitoring và alerting đã setup

## 11. Khi nào cần bật lại ZNS

Khi muốn bật lại ZNS:
1. Cấu hình lại các biến môi trường Zalo
2. Uncomment code gửi ZNS trong `InvitationController`
3. Test ZNS API connection
4. Cập nhật frontend để hiển thị thông báo ZNS

## 12. Support & Resources

- **Zalo Developer Portal**: https://developers.zalo.me/
- **Zalo Business**: https://business.zalo.me/
- **ZNS Documentation**: https://developers.zalo.me/docs/zalo-notification-service/
- **API Status**: Kiểm tra status.zalo.me nếu có vấn đề
