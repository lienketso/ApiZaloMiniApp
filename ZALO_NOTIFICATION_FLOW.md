# Luồng hoạt động Zalo OA Notification (MIỄN PHÍ)

## Tổng quan
Chức năng gửi thông báo điểm danh qua **Tin Truyền thông OA** của Zalo - **HOÀN TOÀN MIỄN PHÍ** - hoạt động theo luồng sau:

## Luồng hoạt động

### 1. Frontend (attendance.tsx)
```
User click "Gửi thông báo cho thành viên"
    ↓
Lấy club_id từ localStorage hoặc API
    ↓
Gọi notificationService.sendAttendanceNotification(clubId)
    ↓
Hiển thị kết quả gửi thông báo
```

### 2. API Service (api.ts)
```
notificationService.sendAttendanceNotification()
    ↓
POST /api/notifications/send-attendance
    ↓
Payload: { club_id: number }
```

### 3. Backend Controller (NotificationController.php)
```
POST /api/notifications/send-attendance
    ↓
Validate request (club_id, zalo_gid)
    ↓
Lấy thông tin club từ database
    ↓
Sử dụng Tin Truyền thông OA - gửi broadcast miễn phí
    ↓
Gửi đến TẤT CẢ người đã follow Official Account
    ↓
Trả về kết quả thành công
```

### 4. Zalo Notification Service (ZaloNotificationService.php)
```
sendBroadcastMessage(message, appId, oaId)
    ↓
Tạo payload theo Tin Truyền thông OA format
    ↓
POST https://openapi.zalo.me/v2.0/oa/message/broadcast
    ↓
Trả về kết quả gửi broadcast miễn phí
```

### 5. Zalo OA API
```
Nhận request broadcast từ service
    ↓
Gửi tin nhắn text đến TẤT CẢ người follow OA
    ↓
User nhận thông báo với link Mini App
    ↓
User click link → mở Mini App
```

## Cấu trúc dữ liệu

### Request Payload
```json
{
  "club_id": 1,
  "zalo_gid": "user_zalo_gid"
}
```

### Zalo OA API Payload
```json
{
  "recipient": {
    "user_id": "zalo_gid"
  },
  "message": {
    "attachment": {
      "type": "template",
      "payload": {
        "template_type": "button",
        "text": "📢 Bạn có thông báo điểm danh từ câu lạc bộ",
        "buttons": [
          {
            "title": "Vào điểm danh",
            "type": "oa.open.url",
            "payload": {
              "url": "https://zalo.me/s/{oa_id}?openMiniApp={app_id}"
            }
          }
        ]
      }
    }
  }
}
```

### Response Format
```json
{
  "success": true,
  "message": "Đã gửi thông báo cho 5 thành viên",
  "data": {
    "total_members": 5,
    "success_count": 4,
    "fail_count": 1,
    "errors": ["Thành viên John không có zalo_gid"]
  }
}
```

## Xử lý lỗi

### 1. Lỗi cấu hình
- ZALO_OA_ACCESS_TOKEN không có
- ZALO_APP_ID không có
- ZALO_OA_ID không có

### 2. Lỗi dữ liệu
- Club không tồn tại
- Không có thành viên nào
- Thành viên không có zalo_gid

### 3. Lỗi Zalo API
- Token hết hạn
- Quyền không đủ
- User không follow OA
- Rate limiting

## Monitoring & Logging

### 1. Logs được ghi
- Request/Response của Zalo API
- Lỗi gửi thông báo
- Thống kê gửi thành công/thất bại

### 2. Metrics quan trọng
- Tổng số thành viên
- Số gửi thành công
- Số gửi thất bại
- Thời gian xử lý

## Bảo mật

### 1. Validation
- Kiểm tra club_id tồn tại
- Kiểm tra user có quyền gửi thông báo
- Validate zalo_gid format

### 2. Rate Limiting
- Giới hạn số lần gửi trong một khoảng thời gian
- Queue system cho việc gửi hàng loạt

### 3. Error Handling
- Không expose thông tin nhạy cảm trong error message
- Log đầy đủ để debug nhưng không log sensitive data
