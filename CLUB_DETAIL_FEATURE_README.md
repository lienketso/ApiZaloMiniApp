# Tính năng Xem Chi tiết Câu lạc bộ

## Tổng quan

Tính năng này cho phép người dùng xem thông tin chi tiết của các câu lạc bộ trước khi quyết định tham gia. Người dùng có thể xem đầy đủ thông tin câu lạc bộ bao gồm vị trí, thống kê, thông tin ngân hàng và gói dịch vụ.

## Các tính năng chính

### 1. **Nút "Xem thông tin"**
- Được thêm vào tất cả các box câu lạc bộ trong trang `club-list.tsx`
- Hiển thị ở cả câu lạc bộ đã tham gia và câu lạc bộ có thể tham gia
- Mở trang chi tiết câu lạc bộ mới

### 2. **Trang Chi tiết Câu lạc bộ (`club-detail.tsx`)**
- Hiển thị đầy đủ thông tin câu lạc bộ
- Giao diện đẹp, responsive và hỗ trợ dark mode
- Các thông tin được tổ chức theo từng section rõ ràng

## Cấu trúc trang chi tiết

### Header
- Nút quay lại
- Tiêu đề "Chi tiết câu lạc bộ"

### Thông tin cơ bản
- Logo câu lạc bộ (nếu có)
- Tên câu lạc bộ
- Môn thể thao
- Địa chỉ
- Số điện thoại
- Email
- Mô tả

### Vị trí câu lạc bộ
- Component `ClubLocationMap` hiển thị vị trí
- Tọa độ (nếu có)
- Nút mở Google Maps
- Nút chỉ đường

### Thống kê câu lạc bộ
- Số lượng thành viên
- Số lượng sự kiện
- Số lượng trận đấu

### Thông tin ngân hàng (nếu có)
- Tên ngân hàng
- Tên chủ tài khoản
- Số tài khoản

### Thông tin gói dịch vụ (nếu có)
- Trạng thái gói (Đang hoạt động/Dùng thử/Hết hạn/Đã hủy)
- Tên gói hiện tại
- Ngày hết hạn dùng thử
- Ngày hết hạn gói

### Hành động
- Nút "Gửi yêu cầu tham gia"
- Nút "Quay lại danh sách"

## Cách sử dụng

### 1. **Từ trang danh sách câu lạc bộ**
1. Vào trang `/club-list`
2. Tìm câu lạc bộ muốn xem chi tiết
3. Nhấn nút "Xem thông tin" (màu xám)
4. Trang chi tiết sẽ mở với URL `/club-detail/{club_id}`

### 2. **Từ trang chi tiết câu lạc bộ**
1. Xem đầy đủ thông tin câu lạc bộ
2. Sử dụng các nút hành động:
   - **Gửi yêu cầu tham gia**: Gửi yêu cầu tham gia câu lạc bộ
   - **Quay lại danh sách**: Quay về trang danh sách câu lạc bộ

## Routing

### Route mới được thêm
```tsx
<Route path="/club-detail/:id" element={
  <ProtectedRoute requireClub={false}>
    <ClubDetailPage />
  </ProtectedRoute>
}></Route>
```

### Tham số URL
- `:id` - ID của câu lạc bộ cần xem chi tiết
- Ví dụ: `/club-detail/123` để xem câu lạc bộ có ID 123

## Các component được sử dụng

### 1. **ClubDetailPage** (`/src/pages/club-detail.tsx`)
- Trang chính hiển thị chi tiết câu lạc bộ
- Quản lý state và logic của trang

### 2. **ClubLocationMap** (`/src/components/club-location-map.tsx`)
- Hiển thị vị trí câu lạc bộ
- Các nút mở Google Maps và chỉ đường

### 3. **Các component ZMP UI**
- `Page`, `Box`, `Text`, `Button`, `Icon`, `Spinner`
- Hỗ trợ responsive và dark mode

## API calls

### 1. **Lấy thông tin câu lạc bộ**
```typescript
const response = await clubService.getById(clubId);
```

### 2. **Gửi yêu cầu tham gia**
```typescript
const response = await clubService.joinClub(club.id);
```

## State management

### Local state
```typescript
const [club, setClub] = useState<Club | null>(null);
const [loading, setLoading] = useState(true);
const [error, setError] = useState<string | null>(null);
```

### Loading states
- Hiển thị spinner khi đang tải dữ liệu
- Disable các nút hành động khi đang xử lý

## Error handling

### 1. **Lỗi tải dữ liệu**
- Hiển thị thông báo lỗi
- Nút "Quay lại" để quay về trang trước

### 2. **Lỗi gửi yêu cầu tham gia**
- Hiển thị thông báo lỗi
- Giữ nguyên trang để người dùng có thể thử lại

## Responsive design

### Mobile-first approach
- Sử dụng Tailwind CSS classes
- Grid layout cho thống kê (3 cột trên mobile)
- Spacing và padding phù hợp với mobile

### Dark mode support
- Tự động theo theme của Zalo
- Sử dụng các class dark: của Tailwind

## Performance optimization

### 1. **Lazy loading**
- Chỉ tải dữ liệu khi cần thiết
- Sử dụng useEffect với dependency array

### 2. **Conditional rendering**
- Chỉ hiển thị các section có dữ liệu
- Ẩn thông tin ngân hàng nếu không có

## Security

### 1. **Protected route**
- Yêu cầu đăng nhập để xem chi tiết
- Không yêu cầu phải là thành viên câu lạc bộ

### 2. **Data validation**
- Kiểm tra club ID hợp lệ
- Xử lý trường hợp club không tồn tại

## Testing

### 1. **Test cases cần kiểm tra**
- Tải thông tin câu lạc bộ thành công
- Xử lý lỗi khi tải dữ liệu
- Gửi yêu cầu tham gia thành công
- Xử lý lỗi khi gửi yêu cầu
- Navigation giữa các trang

### 2. **Edge cases**
- Club ID không hợp lệ
- Club không tồn tại
- Mạng chậm hoặc mất kết nối
- User chưa đăng nhập

## Tương lai

### Các tính năng có thể phát triển thêm
- **Đánh giá và nhận xét**: Cho phép thành viên đánh giá câu lạc bộ
- **Hình ảnh gallery**: Hiển thị hình ảnh hoạt động của câu lạc bộ
- **Lịch sử hoạt động**: Hiển thị các sự kiện và trận đấu gần đây
- **So sánh câu lạc bộ**: So sánh nhiều câu lạc bộ với nhau
- **Chia sẻ**: Chia sẻ thông tin câu lạc bộ lên mạng xã hội
- **Báo cáo**: Báo cáo câu lạc bộ vi phạm quy định

## Troubleshooting

### Lỗi thường gặp

1. **"Không thể tải thông tin câu lạc bộ"**
   - Kiểm tra kết nối mạng
   - Kiểm tra club ID có hợp lệ không
   - Kiểm tra quyền truy cập

2. **"Không thể tham gia câu lạc bộ"**
   - Kiểm tra đã đăng nhập chưa
   - Kiểm tra đã gửi yêu cầu trước đó chưa
   - Kiểm tra quyền của câu lạc bộ

3. **Trang không hiển thị đúng**
   - Kiểm tra console log
   - Kiểm tra network requests
   - Refresh trang

### Debug

Để debug, kiểm tra:
- Console log trong browser
- Network tab trong Developer Tools
- React DevTools
- State của component

## Kết luận

Tính năng xem chi tiết câu lạc bộ cung cấp cho người dùng thông tin đầy đủ để đưa ra quyết định tham gia câu lạc bộ phù hợp. Giao diện thân thiện, thông tin được tổ chức rõ ràng và hỗ trợ đầy đủ các thiết bị mobile.
