# Zalo Mini App Authentication Guide

## Tổng quan
Trong Zalo Mini App, khi user vào app bằng điện thoại, Zalo đã xác thực và cung cấp thông tin user thông qua Zalo SDK. Chúng ta chỉ cần lấy `zalo_gid` để xác định user.

## Luồng xác thực

### 1. Auto Login (Không cần xác thực)
```
POST /api/auth/zalo/auto-login
Content-Type: application/json

{
    "zalo_gid": "zalo_user_id_from_sdk"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Tài khoản mới được tạo và đăng nhập thành công",
    "authenticated": true,
    "is_new_user": true,
    "data": {
        "token": "sanctum_token_here",
        "user": {
            "id": 1,
            "zalo_gid": "zalo_user_id",
            "name": "Zalo User",
            "role": "Member",
            "join_date": "2025-01-XX",
            // ... other fields
        }
    }
}
```

### 2. Cập nhật thông tin từ Zalo (Sau khi đăng nhập)
```
POST /api/auth/zalo/update-info
Authorization: Bearer {token}
Content-Type: application/json

{
    "zalo_name": "Tên hiển thị trên Zalo",
    "zalo_avatar": "https://avatar_url_from_zalo",
    "name": "Tên thật",
    "phone": "0123456789"
}
```

## Cách hoạt động trong Zalo Mini App

### Frontend (Zalo Mini App)
```javascript
// Lấy thông tin user từ Zalo SDK
ZaloMiniApp.getUserInfo().then(userInfo => {
    // userInfo chứa zalo_gid và các thông tin khác
    
    // Gọi API auto login
    fetch('/api/auth/zalo/auto-login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            zalo_gid: userInfo.id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Lưu token
            localStorage.setItem('token', data.data.token);
            
            // Cập nhật thông tin chi tiết nếu cần
            if (userInfo.name || userInfo.avatar) {
                fetch('/api/auth/zalo/update-info', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${data.data.token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        zalo_name: userInfo.name,
                        zalo_avatar: userInfo.avatar
                    })
                });
            }
        }
    });
});
```

### Backend (Laravel)
- **autoLogin**: Chỉ cần `zalo_gid`, tự động tạo user mới nếu chưa có
- **updateZaloInfo**: Cập nhật thông tin chi tiết sau khi đã đăng nhập
- Không cần validate phức tạp vì Zalo đã xác thực rồi

## Lợi ích của cách tiếp cận này

1. **Đơn giản**: Chỉ cần `zalo_gid` để đăng nhập
2. **Bảo mật**: Zalo đã xác thực user, không cần lo về bảo mật
3. **Hiệu quả**: User không cần nhập thông tin, trải nghiệm mượt mà
4. **Linh hoạt**: Có thể cập nhật thông tin sau khi đăng nhập

## Lưu ý

- `zalo_gid` phải là unique trong database
- User mới sẽ có tên mặc định "Zalo User"
- Có thể cập nhật thông tin chi tiết sau khi đăng nhập
- Token được tạo bằng Laravel Sanctum
- Không cần password vì đã xác thực qua Zalo
