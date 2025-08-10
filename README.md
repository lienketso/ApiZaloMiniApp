<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Club Management System - Tính năng Thông tin Ngân hàng

## Tổng quan
Hệ thống quản lý câu lạc bộ đã được bổ sung tính năng quản lý thông tin ngân hàng để thành viên có thể chuyển tiền quỹ câu lạc bộ một cách dễ dàng.

## Tính năng mới

### 1. Thông tin Ngân hàng
- **Tên ngân hàng**: Tên ngân hàng của câu lạc bộ
- **Tên chủ tài khoản**: Tên chủ tài khoản ngân hàng
- **Số tài khoản**: Số tài khoản ngân hàng

### 2. Cách sử dụng

#### Thiết lập thông tin ngân hàng
1. Vào trang **Thiết lập câu lạc bộ** (`/club-setup`)
2. Cuộn xuống phần **"Thông tin ngân hàng"**
3. Điền các thông tin:
   - Tên ngân hàng (VD: Vietcombank, BIDV, Agribank...)
   - Tên chủ tài khoản
   - Số tài khoản
4. Nhấn **"Hoàn tất thiết lập"**

#### Chỉnh sửa thông tin ngân hàng
1. Vào trang **Cá nhân** → **Thông tin câu lạc bộ**
2. Nhấn **"Chỉnh sửa thông tin CLB"**
3. Cập nhật thông tin ngân hàng
4. Nhấn **"Cập nhật thông tin"**

#### Xem thông tin ngân hàng
Thông tin ngân hàng được hiển thị ở các vị trí:
- **Trang chủ**: Thông tin ngắn gọn với khả năng mở rộng
- **Trang Quản lý quỹ**: Hiển thị đầy đủ thông tin
- **Trang Cá nhân**: Thông tin câu lạc bộ

### 3. Tính năng Copy
- Mỗi trường thông tin ngân hàng có nút **"Copy"**
- Nhấn để sao chép thông tin vào clipboard
- Hỗ trợ sao chép: Tên chủ tài khoản, Số tài khoản

### 4. Lưu ý quan trọng
- Thông tin ngân hàng là **không bắt buộc**
- Sau khi chuyển tiền, thành viên cần liên hệ admin để xác nhận giao dịch
- Chỉ admin mới có thể chỉnh sửa thông tin ngân hàng

## Cấu trúc kỹ thuật

### Backend (Laravel)
- **Migration**: `2025_08_08_000000_add_bank_info_to_clubs_table.php`
- **Model**: `Club` - thêm fields: `bank_name`, `account_name`, `account_number`
- **Controller**: `ClubController` - cập nhật validation và logic xử lý

### Frontend (React + ZMP-UI)
- **Types**: Cập nhật interface `Club` và `ClubSetupData`
- **Components**: 
  - `BankInfoDisplay`: Hiển thị thông tin ngân hàng đầy đủ
  - `QuickBankInfo`: Hiển thị thông tin ngắn gọn trên trang chủ
  - `ClubInfo`: Component thông tin câu lạc bộ (đã cập nhật)
- **Pages**: 
  - `club-setup`: Form thiết lập/chỉnh sửa thông tin
  - `fund-management`: Hiển thị thông tin ngân hàng
  - `profile`: Hiển thị thông tin câu lạc bộ

## API Endpoints

### Club Setup/Update
```
POST /api/club/setup
```
**Request Body:**
```json
{
  "name": "Tên CLB",
  "sport": "Bộ môn",
  "address": "Địa chỉ",
  "phone": "Số điện thoại",
  "email": "Email",
  "description": "Mô tả",
  "bank_name": "Tên ngân hàng",
  "account_name": "Tên chủ tài khoản",
  "account_number": "Số tài khoản"
}
```

### Get Club Info
```
GET /api/club/info
```

## Cài đặt và triển khai

### 1. Chạy Migration
```bash
php artisan migrate
```

### 2. Kiểm tra Database
Đảm bảo bảng `clubs` có các cột mới:
- `bank_name` (nullable)
- `account_name` (nullable) 
- `account_number` (nullable)

### 3. Test tính năng
1. Thiết lập thông tin câu lạc bộ với thông tin ngân hàng
2. Kiểm tra hiển thị trên các trang
3. Test chức năng copy thông tin
4. Test chỉnh sửa thông tin

## Troubleshooting

### Lỗi thường gặp
1. **Migration không chạy**: Kiểm tra kết nối database và quyền
2. **Thông tin không hiển thị**: Kiểm tra API response và component props
3. **Copy không hoạt động**: Kiểm tra HTTPS và quyền clipboard

### Debug
- Kiểm tra console browser để xem lỗi JavaScript
- Kiểm tra Laravel logs để xem lỗi backend
- Sử dụng các component debug trong thư mục `debugs/`

## Tương lai
- Tích hợp với hệ thống ngân hàng để tự động xác nhận giao dịch
- Thêm lịch sử giao dịch và báo cáo
- Hỗ trợ nhiều tài khoản ngân hàng
- Tích hợp với Zalo Pay để chuyển tiền trực tiếp
# ApiZaloMiniApp
