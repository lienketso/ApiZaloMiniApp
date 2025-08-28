# Tính năng Google Map cho Câu lạc bộ

## Tổng quan

Tính năng này cho phép các câu lạc bộ thiết lập và hiển thị vị trí địa lý của mình trên bản đồ Google Maps. Người dùng có thể nhập tọa độ thủ công hoặc tìm kiếm địa điểm để lấy tọa độ chính xác.

## Các trường dữ liệu mới

### Bảng `clubs`

- `latitude` (decimal 10,8): Vĩ độ của câu lạc bộ (-90 đến 90)
- `longitude` (decimal 11,8): Kinh độ của câu lạc bộ (-180 đến 180)
- `place_id` (string): Google Place ID của địa điểm
- `formatted_address` (text): Địa chỉ được định dạng bởi Google
- `map_url` (string): URL Google Maps của địa điểm

## Cách sử dụng

### 1. Thiết lập vị trí câu lạc bộ

#### Cách 1: Nhập tọa độ thủ công
1. Vào trang thông tin câu lạc bộ
2. Nhấn "Thiết lập vị trí" hoặc "Chỉnh sửa vị trí"
3. Chọn tab "Nhập tọa độ"
4. Nhập vĩ độ và kinh độ chính xác
5. Nhấn "Lưu tọa độ"

#### Cách 2: Tìm kiếm địa điểm
1. Vào trang thông tin câu lạc bộ
2. Nhấn "Thiết lập vị trí" hoặc "Chỉnh sửa vị trí"
3. Chọn tab "Tìm kiếm địa điểm"
4. Nhập tên địa điểm hoặc địa chỉ
5. Chọn địa điểm từ danh sách kết quả

### 2. Xem vị trí trên bản đồ

- **Xem trên Maps**: Mở Google Maps với vị trí câu lạc bộ
- **Chỉ đường**: Mở Google Maps với tính năng chỉ đường đến câu lạc bộ

## API Endpoints

### Tìm kiếm địa điểm
```
GET /api/places/search?query={search_term}
```

**Response:**
```json
{
  "success": true,
  "results": [
    {
      "place_id": "ChIJN1t_tDeuEmsRUsoyG83frY4",
      "name": "Sân cầu lông ABC",
      "formatted_address": "123 Đường ABC, Quận 1, TP.HCM",
      "geometry": {
        "location": {
          "lat": 10.762622,
          "lng": 106.660172
        }
      }
    }
  ]
}
```

### Lấy chi tiết địa điểm
```
GET /api/places/details?place_id={place_id}
```

## Cấu hình Google Places API

Để sử dụng Google Places API thực tế (thay vì mock data):

1. Tạo Google Cloud Project
2. Bật Google Places API
3. Tạo API Key
4. Thêm vào file `.env`:
```
GOOGLE_PLACES_API_KEY=your_api_key_here
```

5. Cập nhật `PlacesController` để sử dụng API key thực:
```php
$apiKey = config('services.google.places_api_key');
$response = Http::get("https://maps.googleapis.com/maps/api/place/textsearch/json", [
    'query' => $query,
    'key' => $apiKey
]);
```

## Các component React

### GoogleMapLocation
Component để chỉnh sửa vị trí câu lạc bộ với 2 tab:
- Nhập tọa độ thủ công
- Tìm kiếm địa điểm

### ClubLocationMap
Component để hiển thị vị trí câu lạc bộ với:
- Hiển thị tọa độ
- Bản đồ đơn giản (placeholder)
- Nút mở Google Maps
- Nút chỉ đường

## Tính năng bổ sung

### Tính khoảng cách
Model `Club` có method `distanceTo()` để tính khoảng cách giữa 2 tọa độ:
```php
$distance = $club->distanceTo($lat, $lng); // Kết quả tính bằng km
```

### Kiểm tra vị trí
```php
if ($club->hasLocation()) {
    $coordinates = $club->getCoordinates();
    $mapUrl = $club->getGoogleMapsUrl();
}
```

## Lưu ý bảo mật

1. **API Key**: Không bao giờ commit API key vào source code
2. **Rate Limiting**: Google Places API có giới hạn số lượng request
3. **Validation**: Luôn validate tọa độ đầu vào (-90 đến 90 cho vĩ độ, -180 đến 180 cho kinh độ)

## Troubleshooting

### Lỗi thường gặp

1. **"Không tìm thấy địa điểm nào"**
   - Kiểm tra kết nối internet
   - Kiểm tra API key (nếu sử dụng Google API thực)
   - Thử tìm kiếm với từ khóa khác

2. **"Tọa độ không hợp lệ"**
   - Vĩ độ phải từ -90 đến 90
   - Kinh độ phải từ -180 đến 180
   - Kiểm tra định dạng số thập phân

3. **"Không thể mở Google Maps"**
   - Kiểm tra quyền truy cập internet
   - Kiểm tra URL Google Maps có hợp lệ không

### Debug

Để debug, kiểm tra:
- Console log trong browser
- Laravel log (`storage/logs/laravel.log`)
- Network tab trong Developer Tools

## Tương lai

Các tính năng có thể phát triển thêm:
- Hiển thị bản đồ thực tế với Google Maps JavaScript API
- Tính toán thời gian di chuyển
- Gợi ý địa điểm gần nhất
- Tích hợp với các ứng dụng bản đồ khác
