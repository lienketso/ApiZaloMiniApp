# Hướng dẫn Debug vấn đề Loading trong My Club App

## Vấn đề đã được khắc phục

### 1. Vòng lặp vô hạn trong ProtectedRoute
- **Nguyên nhân**: Component `ProtectedRoute` đang gọi `checkUserClubMembership()` mỗi lần render
- **Giải pháp**: Sử dụng `useCallback` và state `hasCheckedClub` để đảm bảo chỉ kiểm tra một lần

### 2. State management không đúng trong AuthContext
- **Nguyên nhân**: Functions được tạo mới mỗi lần render
- **Giải pháp**: Sử dụng `useCallback` để tránh re-render không cần thiết

### 3. Authentication logic trong ClubController
- **Nguyên nhân**: Method `getCurrentUserId()` không sử dụng đúng Sanctum guard
- **Giải pháp**: Sửa để sử dụng `Auth::guard('sanctum')->check()` và `Auth::guard('sanctum')->id()`

## Các thay đổi đã thực hiện

### Frontend (React/TypeScript)

#### 1. `src/components/protected-route.tsx`
- Thêm state `hasCheckedClub` và `clubCheckResult`
- Sử dụng `useCallback` cho `checkUserClubMembership`
- Cải thiện logic để tránh vòng lặp vô hạn
- Thêm debug logging chi tiết

#### 2. `src/contexts/auth-context.tsx`
- Sử dụng `useCallback` cho tất cả functions
- Cải thiện state management
- Thêm debug logging

#### 3. `src/components/loading-screen.tsx`
- Cải thiện thông tin hiển thị
- Thêm hướng dẫn debug

#### 4. `src/services/api.ts`
- Thêm debug logging cho API calls
- Cải thiện error handling

### Backend (Laravel)

#### 1. `app/Http/Controllers/ClubController.php`
- Sửa `getCurrentUserId()` để sử dụng Sanctum guard đúng cách
- Thêm logging chi tiết cho debugging
- Cải thiện error handling

## Cách Debug

### 1. Kiểm tra Console Browser
Mở Developer Tools (F12) và xem Console tab để theo dõi:
- AuthProvider logs
- ProtectedRoute logs
- API request/response logs

### 2. Kiểm tra Network Tab
Xem Network tab trong Developer Tools để kiểm tra:
- API calls đến `/clubs/user-clubs`
- Response status và data
- Headers (đặc biệt là Authorization)

### 3. Kiểm tra Laravel Logs
Xem logs trong `storage/logs/laravel.log` để kiểm tra:
- ClubController logs
- Authentication logs
- Error logs

### 4. Test API trực tiếp
Sử dụng file `test_user_clubs_api.php` để test API endpoint:
```bash
php test_user_clubs_api.php
```

## Các bước kiểm tra

### Bước 1: Kiểm tra Authentication
1. Mở app và đăng nhập
2. Kiểm tra localStorage có `auth_token` không
3. Kiểm tra console logs của AuthProvider

### Bước 2: Kiểm tra Club Membership
1. Sau khi đăng nhập, kiểm tra console logs của ProtectedRoute
2. Xem có gọi API `/clubs/user-clubs` không
3. Kiểm tra response của API

### Bước 3: Kiểm tra Redirect
1. Nếu user không có club, app sẽ redirect đến `/club-list`
2. Nếu user có club, app sẽ hiển thị trang chính

## Troubleshooting

### Vấn đề: Vẫn bị loading vô hạn
**Nguyên nhân có thể:**
- API endpoint không hoạt động
- Authentication token không hợp lệ
- Database connection issues

**Giải pháp:**
1. Kiểm tra Laravel logs
2. Test API endpoint trực tiếp
3. Kiểm tra database connection
4. Verify Sanctum configuration

### Vấn đề: API trả về 401 Unauthorized
**Nguyên nhân có thể:**
- Token không được gửi đúng cách
- Token đã hết hạn
- Sanctum middleware không hoạt động

**Giải pháp:**
1. Kiểm tra Authorization header
2. Verify token trong database
3. Check Sanctum configuration

### Vấn đề: API trả về 500 Internal Server Error
**Nguyên nhân có thể:**
- Database query errors
- Model relationship issues
- Missing database tables

**Giải pháp:**
1. Kiểm tra Laravel logs
2. Verify database schema
3. Check model relationships

## Cấu hình cần thiết

### 1. Sanctum Configuration
Đảm bảo `config/sanctum.php` được cấu hình đúng:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

### 2. CORS Configuration
Đảm bảo `config/cors.php` cho phép frontend domain:
```php
'allowed_origins' => ['*'], // Hoặc domain cụ thể
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'allowed_credentials' => true,
```

### 3. Database Migration
Đảm bảo tất cả migrations đã chạy:
```bash
php artisan migrate:status
php artisan migrate
```

## Kết luận

Sau khi áp dụng các thay đổi trên, vấn đề loading vô hạn sẽ được khắc phục. App sẽ:

1. Kiểm tra authentication một cách hiệu quả
2. Kiểm tra club membership chỉ một lần
3. Redirect đúng cách dựa trên trạng thái user
4. Hiển thị thông tin debug hữu ích

Nếu vẫn gặp vấn đề, hãy kiểm tra logs và sử dụng các công cụ debug đã cung cấp.
