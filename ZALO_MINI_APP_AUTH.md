# ZMP SDK Authentication Guide

## Tổng quan
Trong Zalo Mini Program (ZMP), khi user vào app bằng điện thoại, ZMP SDK đã xác thực và cung cấp thông tin user thông qua `getUserInfo()`. Chúng ta chỉ cần lấy `zalo_gid` (từ `data.userInfo.id`) để xác định user.

## Luồng xác thực

### 1. Auto Login (Không cần xác thực)
```
POST /api/auth/zalo/auto-login
Content-Type: application/json

{
    "zalo_gid": "zalo_user_id_from_zmp_sdk"
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

### 2. Cập nhật thông tin từ ZMP (Sau khi đăng nhập)
```
POST /api/auth/zalo/update-info
Authorization: Bearer {token}
Content-Type: application/json

{
    "zalo_name": "Tên hiển thị trên Zalo",
    "zalo_avatar": "https://avatar_url_from_zmp",
    "name": "Tên thật",
    "phone": "0123456789"
}
```

## Cách hoạt động trong ZMP SDK

### Frontend (ZMP SDK)
```javascript
import { getUserInfo } from "zmp-sdk";

// Lấy thông tin user từ ZMP SDK
getUserInfo({
    success: (data) => {
        console.log("Thông tin người dùng:", data);
        console.log("Zalo Open ID:", data.userInfo.id); // Đây chính là zalo_gid
        
        // Gọi API auto login
        fetch('/api/auth/zalo/auto-login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                zalo_gid: data.userInfo.id
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Lưu token
                localStorage.setItem('token', result.data.token);
                
                // Cập nhật thông tin chi tiết nếu cần
                if (data.userInfo.name || data.userInfo.avatar) {
                    fetch('/api/auth/zalo/update-info', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${result.data.token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            zalo_name: data.userInfo.name,
                            zalo_avatar: data.userInfo.avatar
                        })
                    });
                }
            }
        })
        .catch(error => {
            console.error('Lỗi đăng nhập:', error);
        });
    },
    fail: (error) => {
        console.error("Lỗi khi lấy thông tin user:", error);
    }
});
```

### Backend (Laravel)
- **autoLogin**: Chỉ cần `zalo_gid`, tự động tạo user mới nếu chưa có
- **updateZaloInfo**: Cập nhật thông tin chi tiết sau khi đã đăng nhập
- **Logging**: Thêm logging để debug và theo dõi quá trình xác thực
- Không cần validate phức tạp vì ZMP SDK đã xác thực rồi

## Lợi ích của cách tiếp cận này

1. **Đơn giản**: Chỉ cần `zalo_gid` để đăng nhập
2. **Bảo mật**: ZMP SDK đã xác thực user, không cần lo về bảo mật
3. **Hiệu quả**: User không cần nhập thông tin, trải nghiệm mượt mà
4. **Linh hoạt**: Có thể cập nhật thông tin sau khi đăng nhập
5. **Debug**: Có logging chi tiết để theo dõi và xử lý lỗi

## Lưu ý

- `zalo_gid` phải là unique trong database
- User mới sẽ có tên mặc định "Zalo User"
- Có thể cập nhật thông tin chi tiết sau khi đăng nhập
- Token được tạo bằng Laravel Sanctum với name "zmp-sdk"
- Không cần password vì đã xác thực qua ZMP SDK
- Có logging chi tiết để debug và monitor

## Troubleshooting

### Nếu user không được tạo:
1. Kiểm tra logs trong `storage/logs/laravel.log`
2. Đảm bảo `zalo_gid` được gửi đúng format
3. Kiểm tra database connection và permissions
4. Xem có lỗi validation nào không

### Nếu token không được tạo:
1. Kiểm tra Laravel Sanctum configuration
2. Đảm bảo user được tạo thành công trước
3. Kiểm tra database permissions
