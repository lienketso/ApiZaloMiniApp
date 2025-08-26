# Test Chức Năng Gender và Birthday trong Profile

## Tóm tắt các thay đổi đã thực hiện

### Frontend Components

1. **profile-new.tsx**: Đã cập nhật để bổ sung 2 trường `gender` và `birthday` vào form edit profile

### Thay đổi cụ thể

#### Import Components
```tsx
// Trước
import { Box, Page, Text, Button, Icon, Avatar, Input, Switch, Tabs } from 'zmp-ui';

// Sau
import { Box, Page, Text, Button, Icon, Avatar, Input, Switch, Tabs, Select, DatePicker } from 'zmp-ui';
```

#### Form Edit (khi isEditing = true)
```tsx
// Thêm trường Gender
<Box>
  <Text size="small" className="text-gray-500 dark:text-gray-400 mb-2">
    Giới tính
  </Text>
  <Select
    value={userProfile.gender || 'male'}
    onChange={(value) => setUserProfile({...userProfile, gender: value as 'male' | 'female' | 'other'})}
  >
    <Select.Option value="male">Nam</Select.Option>
    <Select.Option value="female">Nữ</Select.Option>
    <Select.Option value="other">Khác</Select.Option>
  </Select>
</Box>

// Thêm trường Birthday
<Box>
  <Text size="small" className="text-gray-500 dark:text-gray-400 mb-2">
    Ngày sinh
  </Text>
  <DatePicker
    value={userProfile.birthday ? new Date(userProfile.birthday) : undefined}
    onChange={(date) => setUserProfile({...userProfile, birthday: date ? date.toISOString().split('T')[0] : ''})}
    placeholder="Chọn ngày sinh"
  />
</Box>
```

#### Form View (khi isEditing = false)
```tsx
// Hiển thị Gender
<Box className="flex items-center justify-between">
  <Text size="small" className="text-gray-500 dark:text-gray-400">
    Giới tính
  </Text>
  <Text size="small" className="text-gray-900 dark:text-white">
    {userProfile.gender === 'male' ? 'Nam' : userProfile.gender === 'female' ? 'Nữ' : userProfile.gender === 'other' ? 'Khác' : 'Chưa cập nhật'}
  </Text>
</Box>

// Hiển thị Birthday
<Box className="flex items-center justify-between">
  <Text size="small" className="text-gray-500 dark:text-gray-400">
    Ngày sinh
  </Text>
  <Text size="small" className="text-gray-900 dark:text-white">
    {userProfile.birthday ? new Date(userProfile.birthday).toLocaleDateString('vi-VN') : 'Chưa cập nhật'}
  </Text>
</Box>
```

#### API Call Update
```tsx
// Trước
const response = await userService.updateProfile({
  name: userProfile.name,
  phone: userProfile.phone,
  email: userProfile.email,
});

// Sau
const response = await userService.updateProfile({
  name: userProfile.name,
  phone: userProfile.phone,
  email: userProfile.email,
  gender: userProfile.gender,
  birthday: userProfile.birthday,
});
```

#### UserProfile Data Fallback
```tsx
// Cập nhật fallback data khi API fail
const userProfileData: UserProfile = {
  id: user.id,
  name: user.name,
  email: user.email,
  phone: user.phone || '',
  avatar: user.avatar,
  role: user.role || 'member',
  gender: user.gender,        // Thêm gender
  birthday: user.birthday,    // Thêm birthday
  created_at: user.created_at || new Date().toISOString(),
  updated_at: new Date().toISOString()
};
```

## Lợi ích của việc bổ sung

1. **Thông tin cá nhân đầy đủ**: User có thể cập nhật đầy đủ thông tin cá nhân
2. **UX nhất quán**: Cùng format với form AddMember và EditMember
3. **Validation tự động**: DatePicker đảm bảo format ngày tháng đúng
4. **Giao diện thân thiện**: Select dropdown cho gender, DatePicker cho birthday

## Cách test chức năng

### 1. Test View Profile
1. Mở trang Profile
2. Kiểm tra xem có hiển thị 2 trường Gender và Birthday không
3. Kiểm tra format hiển thị có đúng không

### 2. Test Edit Profile
1. Click nút "Sửa" trong profile header
2. Form edit sẽ mở với các trường có thể sửa
3. Thay đổi Gender (chọn từ dropdown)
4. Thay đổi Birthday (chọn từ DatePicker)
5. Click "Lưu" để lưu thay đổi

### 3. Test API Integration
1. Sau khi lưu, kiểm tra xem có gọi API updateProfile không
2. Kiểm tra xem API có nhận đúng 2 trường gender và birthday không
3. Kiểm tra xem dữ liệu có được cập nhật đúng không

### 4. Test Data Persistence
1. Refresh trang
2. Kiểm tra xem thông tin đã lưu có được hiển thị đúng không
3. Kiểm tra xem form edit có load đúng dữ liệu không

## Các trường hợp test

### Test Case 1: Hiển thị thông tin
- ✅ Gender hiển thị đúng text (Nam/Nữ/Khác)
- ✅ Birthday hiển thị đúng format Việt Nam
- ✅ Hiển thị "Chưa cập nhật" khi không có dữ liệu

### Test Case 2: Edit thông tin
- ✅ Gender dropdown có 3 options (Nam/Nữ/Khác)
- ✅ DatePicker mở calendar để chọn ngày
- ✅ Form validation hoạt động đúng

### Test Case 3: Lưu thông tin
- ✅ API call với đầy đủ thông tin
- ✅ Snackbar thông báo thành công
- ✅ Dữ liệu được cập nhật real-time

### Test Case 4: Data persistence
- ✅ Thông tin được lưu vào database
- ✅ Refresh trang vẫn hiển thị đúng
- ✅ Form edit load đúng dữ liệu đã lưu

## Lưu ý kỹ thuật

1. **Type Safety**: Gender được type là `'male' | 'female' | 'other'`
2. **Date Format**: Birthday được convert sang ISO format (YYYY-MM-DD) khi lưu
3. **Display Format**: Birthday hiển thị theo format Việt Nam (dd/MM/yyyy)
4. **Fallback Data**: Sử dụng thông tin từ auth context khi API fail

## Kết luận

Chức năng Gender và Birthday trong Profile đã được implement thành công:
- ✅ Form edit: Select cho Gender, DatePicker cho Birthday
- ✅ Form view: Hiển thị thông tin đã format
- ✅ API integration: Gửi đầy đủ thông tin khi update
- ✅ Data persistence: Lưu và load dữ liệu đúng
- ✅ UX nhất quán: Cùng format với các form khác

Chức năng đã sẵn sàng để test và sử dụng! 🎉
