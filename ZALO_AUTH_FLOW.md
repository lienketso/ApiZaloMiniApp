# Quy trình Xác thực Zalo - My Club App

## Tổng quan

Quy trình xác thực Zalo mới được thiết kế để tự động đăng nhập user khi vào app, sử dụng ZMP SDK để lấy Zalo ID và tạo/cập nhật tài khoản tự động.

## Luồng hoạt động

### 1. Khởi động App
- Khi app khởi động, `AuthProvider` sẽ tự động gọi `initializeAuth()`
- Kiểm tra xem có đang chạy trong ZMP environment không

### 2. Auto-login với Zalo
- Nếu đang trong ZMP environment:
  - Gọi `zmpService.getZaloGid()` để lấy Zalo ID từ ZMP SDK
  - Gọi API `POST /auth/zalo/auto-login` với `zalo_gid`
  - Backend sẽ kiểm tra và tạo tài khoản mới nếu cần

### 3. Xử lý Backend
- `ZaloAuthController::autoLogin()` nhận `zalo_gid`
- Tìm user trong bảng `users` theo `zalo_gid`
- Nếu chưa có user:
  - Tạo user mới với thông tin mặc định
  - Tên: "Zalo User {6 ký tự cuối của zalo_gid}"
  - Email: "zalo_{zalo_gid}@temp.com"
  - Role: "Member"
  - Password: random string
- Nếu đã có user:
  - Cập nhật thông tin cơ bản nếu cần
- Tạo token mới và trả về

### 4. Lưu trữ Local
- Frontend lưu token và thông tin user vào localStorage
- Cập nhật AuthContext state
- User được đăng nhập tự động

### 5. Fallback
- Nếu không phải ZMP environment hoặc Zalo auto-login thất bại:
  - Kiểm tra localStorage để lấy thông tin đăng nhập cũ
  - Nếu không có, user cần đăng nhập thủ công

## Cấu trúc Files

### Frontend
- `src/contexts/auth-context.tsx` - AuthContext với logic auto-login
- `src/components/zalo-auth-status.tsx` - Component hiển thị trạng thái Zalo
- `src/pages/profile-new.tsx` - ProfilePage với tab Zalo Auth
- `src/services/api.ts` - API service với ZMP SDK integration

### Backend
- `app/Http/Controllers/ZaloAuthController.php` - Controller xử lý Zalo auth
- `app/Models/User.php` - User model với các trường Zalo
- `database/migrations/2025_08_03_041206_add_zalo_fields_to_users_table.php` - Migration cho Zalo fields

## API Endpoints

### Auto Login
```
POST /api/auth/zalo/auto-login
Content-Type: application/json

{
  "zalo_gid": "string"
}
```

### Update Zalo Info
```
POST /api/auth/zalo/update-info
Authorization: Bearer {token}
Content-Type: application/json

{
  "zalo_name": "string",
  "zalo_avatar": "string",
  "name": "string",
  "phone": "string"
}
```

## Database Schema

### Users Table
```sql
ALTER TABLE users ADD COLUMN zalo_gid VARCHAR(255) UNIQUE AFTER email;
ALTER TABLE users ADD COLUMN zalo_name VARCHAR(255) AFTER zalo_gid;
ALTER TABLE users ADD COLUMN zalo_avatar VARCHAR(500) AFTER zalo_name;
```

## Tính năng

### 1. Auto-login
- Tự động đăng nhập khi vào app
- Không cần nhập username/password
- Tạo tài khoản mới tự động nếu cần

### 2. Quản lý thông tin Zalo
- Hiển thị Zalo ID, tên, avatar
- Cho phép cập nhật thông tin cá nhân
- Lưu trữ thông tin Zalo trong database

### 3. Fallback Authentication
- Hỗ trợ đăng nhập thủ công nếu cần
- Lưu trữ session trong localStorage
- Tương thích với cả ZMP và non-ZMP environments

## Bảo mật

### 1. Token Management
- Sử dụng Laravel Sanctum cho API authentication
- Token tự động refresh khi cần
- Xóa token cũ khi tạo token mới

### 2. Validation
- Validate `zalo_gid` là required và unique
- Sanitize input data
- Log tất cả operations để audit

### 3. Error Handling
- Graceful fallback khi ZMP SDK không khả dụng
- Clear error messages cho user
- Log errors để debugging

## Development

### Testing
- Sử dụng mock Zalo ID trong development
- Test cả ZMP và non-ZMP environments
- Verify auto-login flow end-to-end

### Debugging
- Console logs cho mỗi step
- Network tab để monitor API calls
- Backend logs cho server-side operations

## Deployment

### Requirements
- Laravel 10+ với Sanctum
- Database với các trường Zalo
- Frontend build với ZMP SDK support

### Environment Variables
- `ZALO_APP_ID` (nếu cần)
- `ZALO_APP_SECRET` (nếu cần)
- Database configuration

## Troubleshooting

### Common Issues
1. **ZMP SDK không load**: Kiểm tra ZMP environment và SDK installation
2. **Auto-login thất bại**: Kiểm tra API endpoint và database connection
3. **Token không hợp lệ**: Clear localStorage và thử lại
4. **User không được tạo**: Kiểm tra database permissions và migration

### Debug Steps
1. Kiểm tra console logs
2. Verify API responses
3. Check database records
4. Test ZMP SDK functions

## Tương lai

### Planned Features
- Sync thông tin Zalo real-time
- Multiple Zalo account support
- Advanced user profile management
- Integration với Zalo social features

### Scalability
- Cache user data
- Optimize database queries
- Rate limiting cho API calls
- Monitoring và analytics
