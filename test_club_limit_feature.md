# Test Tính Năng Giới Hạn Số Lượng Câu Lạc Bộ

## Mô tả
Tính năng này giới hạn mỗi user chỉ được tạo tối đa 2 câu lạc bộ, sử dụng trường `created_by` trong bảng `clubs`.

## Các Thay Đổi Đã Thực Hiện

### Backend (Laravel)

#### 1. ClubController.php
- **Thêm validation trong method `setup`:**
  - Kiểm tra số lượng câu lạc bộ đã tạo bởi user
  - Trả về lỗi nếu đã đạt giới hạn 2 câu lạc bộ
  - Response code: `MAX_CLUBS_REACHED`

- **Thêm method `getUserClubCount`:**
  - Trả về thông tin số lượng câu lạc bộ hiện tại
  - Bao gồm: `current_count`, `max_allowed`, `can_create_more`, `remaining_slots`

#### 2. Routes (api.php)
- **Thêm route mới:**
  - `GET /clubs/user-count` → `ClubController@getUserClubCount`

### Frontend (React/TypeScript)

#### 1. API Service (api.ts)
- **Thêm endpoint mới:**
  - `CLUB_USER_COUNT: '/clubs/user-count'`

- **Thêm method mới vào clubService:**
  - `getUserClubCount()` để gọi API kiểm tra số lượng câu lạc bộ

#### 2. ClubSetupPage (club-setup.tsx)
- **Thêm state `clubCount`:**
  - Lưu trữ thông tin về số lượng câu lạc bộ
  - Kiểm tra giới hạn trước khi cho phép tạo

- **Thêm validation:**
  - Disable nút tạo câu lạc bộ khi đã đạt giới hạn
  - Hiển thị thông báo về giới hạn

- **Thêm component `MaxClubsReached`:**
  - Hiển thị khi user đã tạo đủ câu lạc bộ
  - Cung cấp hướng dẫn và nút điều hướng

#### 3. ClubListPage (club-list.tsx)
- **Thêm state `clubCount`:**
  - Load thông tin số lượng câu lạc bộ khi component mount
  - Refresh sau khi tạo câu lạc bộ thành công

- **Cập nhật UI:**
  - Hiển thị thông tin về số lượng câu lạc bộ
  - Disable nút tạo câu lạc bộ khi đã đạt giới hạn
  - Hiển thị thông báo về giới hạn

- **Thêm validation trong `handleCreateClub`:**
  - Kiểm tra giới hạn trước khi tạo
  - Hiển thị thông báo lỗi nếu đã đạt giới hạn

#### 4. Components Khác
- **ClubSetupGuide:** Thêm thông báo về giới hạn
- **ClubSetupCheck:** Cập nhật để kiểm tra số lượng câu lạc bộ
- **NoClubMessage:** Thêm thông tin về giới hạn và disable nút tạo
- **MaxClubsReached:** Component mới để hiển thị khi đã đạt giới hạn

## Cách Hoạt Động

### 1. Khi User Truy Cập Trang Tạo Câu Lạc Bộ
- Hệ thống tự động kiểm tra số lượng câu lạc bộ đã tạo
- Hiển thị thông tin: "Đã tạo X / 2 câu lạc bộ"
- Nếu đã đạt giới hạn:
  - Disable nút tạo câu lạc bộ
  - Hiển thị component `MaxClubsReached`
  - Cung cấp hướng dẫn và nút điều hướng

### 2. Khi User Cố Gắng Tạo Câu Lạc Bộ
- **Frontend validation:** Disable nút và hiển thị thông báo
- **Backend validation:** Trả về lỗi `MAX_CLUBS_REACHED` nếu vượt quá giới hạn
- **Response format:**
  ```json
  {
    "success": false,
    "message": "Bạn đã tạo tối đa 2 câu lạc bộ. Không thể tạo thêm câu lạc bộ mới.",
    "code": "MAX_CLUBS_REACHED",
    "existing_count": 2,
    "max_allowed": 2
  }
  ```

### 3. Cập Nhật Real-time
- Sau khi tạo câu lạc bộ thành công, thông tin số lượng được refresh
- UI tự động cập nhật để phản ánh trạng thái mới

## Test Cases

### Test Case 1: User Chưa Có Câu Lạc Bộ
- **Kết quả mong đợi:** Có thể tạo câu lạc bộ, hiển thị "0 / 2 câu lạc bộ"

### Test Case 2: User Có 1 Câu Lạc Bộ
- **Kết quả mong đợi:** Có thể tạo thêm 1 câu lạc bộ, hiển thị "1 / 2 câu lạc bộ"

### Test Case 3: User Có 2 Câu Lạc Bộ
- **Kết quả mong đợi:** 
  - Không thể tạo thêm câu lạc bộ
  - Hiển thị "2 / 2 câu lạc bộ"
  - Nút tạo bị disable
  - Hiển thị component `MaxClubsReached`

### Test Case 4: User Cố Gắng Tạo Câu Lạc Bộ Thứ 3
- **Kết quả mong đợi:** 
  - Frontend: Nút bị disable, hiển thị thông báo
  - Backend: Trả về lỗi `MAX_CLUBS_REACHED`

## Lưu Ý Kỹ Thuật

### 1. Database
- Sử dụng trường `created_by` trong bảng `clubs` để đếm số lượng
- Query: `Club::where('created_by', $userId)->count()`

### 2. Performance
- Thông tin số lượng câu lạc bộ được cache trong state
- Chỉ refresh khi cần thiết (sau khi tạo câu lạc bộ)

### 3. User Experience
- Thông báo rõ ràng về giới hạn
- Cung cấp hướng dẫn và lựa chọn thay thế
- Disable các chức năng không khả dụng

### 4. Security
- Validation ở cả frontend và backend
- Kiểm tra quyền truy cập trước khi cho phép tạo câu lạc bộ

## Kết Luận
Tính năng giới hạn số lượng câu lạc bộ đã được implement đầy đủ với:
- ✅ Backend validation
- ✅ Frontend validation và UI updates
- ✅ Real-time updates
- ✅ User-friendly error messages
- ✅ Alternative navigation options
- ✅ Comprehensive test coverage
