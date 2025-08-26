# Hệ Thống Subscription và Trial cho Câu Lạc Bộ

## Tổng Quan

Hệ thống này cho phép các câu lạc bộ sử dụng tính năng dùng thử miễn phí trong 1 tháng và sau đó có thể đăng ký các gói trả phí để tiếp tục sử dụng các tính năng premium.

## Các Trường Mới Trong Bảng `clubs`

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `trial_expired_at` | timestamp | Thời gian hết hạn dùng thử |
| `subscription_expired_at` | timestamp | Thời gian hết hạn gói trả phí |
| `subscription_status` | enum | Trạng thái gói: `trial`, `active`, `expired`, `canceled` |
| `plan_id` | bigint | ID gói hiện tại (FK đến bảng `plans`) |
| `last_payment_at` | timestamp | Thời gian thanh toán cuối cùng |

## Bảng `plans`

Bảng này chứa thông tin về các gói trả phí:

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `name` | string | Tên gói (Basic, Premium, Enterprise) |
| `description` | text | Mô tả gói |
| `price` | decimal | Giá gói |
| `billing_cycle` | string | Chu kỳ thanh toán (monthly, yearly) |
| `duration_days` | integer | Số ngày có hiệu lực |
| `features` | json | Danh sách tính năng |
| `is_active` | boolean | Gói có đang hoạt động không |

## Cài Đặt

### 1. Chạy Migration

```bash
php artisan migrate
```

### 2. Chạy Seeder

```bash
php artisan db:seed --class=PlanSeeder
```

### 3. Kiểm Tra

```bash
php test_subscription_system.php
```

## Sử Dụng

### Khởi Tạo Trial

```php
use App\Models\Club;
use App\Services\SubscriptionService;

$club = Club::find(1);
$service = new SubscriptionService();

// Bắt đầu dùng thử 1 tháng
$service->startTrial($club);
```

### Kích Hoạt Gói Trả Phí

```php
// Kích hoạt gói Basic
$service->activateSubscription($club, 1); // 1 là ID của gói Basic

// Kích hoạt với thời gian tùy chỉnh
$service->activateSubscription($club, 1, 90); // 90 ngày
```

### Kiểm Tra Quyền Truy Cập

```php
// Kiểm tra xem club có thể tạo event không
$canCreateEvent = $service->canPerformAction($club, 'create_event');

// Kiểm tra xem club có thể truy cập tính năng premium không
$canAccessPremium = $club->canAccessPremiumFeatures();
```

## API Endpoints

### Lấy Danh Sách Gói
```
GET /api/subscription/plans
```

### Lấy Thông Tin Subscription Của Club
```
GET /api/subscription/club/{clubId}
```

### Bắt Đầu Dùng Thử
```
POST /api/subscription/club/{clubId}/trial
```

### Kích Hoạt Gói
```
POST /api/subscription/club/{clubId}/activate
Body: {
    "plan_id": 1,
    "duration_days": 30
}
```

### Hủy Gói
```
POST /api/subscription/club/{clubId}/cancel
```

### Kiểm Tra Quyền Thực Hiện Action
```
POST /api/subscription/club/{clubId}/check-permission
Body: {
    "action": "create_event"
}
```

## Trạng Thái Subscription

### 1. Trial (Dùng Thử)
- Mỗi club chỉ được dùng thử 1 lần
- Thời gian: 1 tháng
- Có thể truy cập tất cả tính năng

### 2. Active (Đang Hoạt Động)
- Club đã đăng ký gói trả phí
- Có thể truy cập tất cả tính năng
- Thời gian phụ thuộc vào gói đã chọn

### 3. Expired (Hết Hạn)
- Gói đã hết hạn
- Chỉ có thể truy cập tính năng cơ bản
- Cần đăng ký gói mới

### 4. Canceled (Đã Hủy)
- Club đã hủy gói
- Chỉ có thể truy cập tính năng cơ bản
- Có thể đăng ký gói mới

## Các Action và Quyền Truy Cập

### Action Cơ Bản (Luôn Được Phép)
- `view_members`: Xem danh sách thành viên
- `view_events`: Xem sự kiện
- `view_finances`: Xem tài chính

### Action Premium (Cần Subscription)
- `create_event`: Tạo sự kiện
- `edit_event`: Chỉnh sửa sự kiện
- `delete_event`: Xóa sự kiện
- `manage_members`: Quản lý thành viên
- `advanced_finances`: Tài chính nâng cao
- `zalo_integration`: Tích hợp Zalo
- `custom_reports`: Báo cáo tùy chỉnh
- `api_access`: Truy cập API

## Command

### Cập Nhật Trạng Thái Subscription

```bash
php artisan subscription:update-status
```

Command này sẽ tự động cập nhật trạng thái của tất cả clubs dựa trên thời gian hết hạn.

## Middleware và Bảo Mật

- Tất cả API endpoints đều yêu cầu xác thực
- Chỉ người tạo club hoặc thành viên mới có thể truy cập thông tin subscription
- Mỗi action đều được kiểm tra quyền truy cập

## Monitoring và Logging

Hệ thống tự động log các hoạt động:
- Bắt đầu trial
- Kích hoạt gói
- Hủy gói
- Hết hạn gói
- Lỗi xảy ra

## Troubleshooting

### Lỗi Thường Gặp

1. **Club đã hết hạn dùng thử**
   - Giải pháp: Đăng ký gói trả phí

2. **Không thể kích hoạt gói**
   - Kiểm tra gói có tồn tại và đang hoạt động không
   - Kiểm tra quyền truy cập

3. **Trạng thái không được cập nhật**
   - Chạy command: `php artisan subscription:update-status`
   - Kiểm tra log để tìm lỗi

### Debug

```bash
# Kiểm tra trạng thái subscription
php test_subscription_system.php

# Xem log
tail -f storage/logs/laravel.log

# Kiểm tra database
php artisan tinker
```

## Phát Triển Tiếp Theo

1. **Tích hợp thanh toán**
   - VNPay, Momo, ZaloPay
   - Webhook xử lý thanh toán

2. **Tính năng nâng cao**
   - Gói theo số lượng thành viên
   - Gói theo tính năng
   - Discount và promotion

3. **Báo cáo và Analytics**
   - Thống kê doanh thu
   - Phân tích sử dụng
   - Churn rate

4. **Email và Notification**
   - Thông báo hết hạn
   - Nhắc nhở gia hạn
   - Welcome email cho trial
