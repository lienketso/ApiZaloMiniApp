# 🧪 Test Subscription API Service

## Đã Hoàn Thành

### ✅ **1. Tạo Subscription Service trong api.ts**
```typescript
// Thêm vào API_ENDPOINTS
SUBSCRIPTION_PLANS: '/subscription/plans',
SUBSCRIPTION_CLUB_INFO: '/subscription/club',
SUBSCRIPTION_START_TRIAL: '/subscription/club',
SUBSCRIPTION_ACTIVATE: '/subscription/club',
SUBSCRIPTION_CANCEL: '/subscription/club',
SUBSCRIPTION_CHECK_PERMISSION: '/subscription/club',

// Thêm subscriptionService
export const subscriptionService = {
  getPlans: async () => { ... },
  getClubSubscriptionInfo: async (clubId: number) => { ... },
  startTrial: async (clubId: number) => { ... },
  activateSubscription: async (clubId: number, planId: number) => { ... },
  cancelSubscription: async (clubId: number) => { ... },
  checkActionPermission: async (clubId: number, action: string) => { ... },
};
```

### ✅ **2. Cập nhật subscription.tsx**
- Thay thế tất cả `fetch()` calls bằng `subscriptionService`
- Loại bỏ AbortController (không cần thiết nữa)
- Giữ nguyên safe state update logic
- Giữ nguyên unmounting protection

## Cách Test

### **Bước 1: Kiểm tra Import**
```typescript
// Trong subscription.tsx
import { subscriptionService } from '@/services/api';
```

### **Bước 2: Test API Calls**
```typescript
// Test getPlans
const plans = await subscriptionService.getPlans();
console.log('Plans:', plans);

// Test startTrial
const trial = await subscriptionService.startTrial(1);
console.log('Trial:', trial);

// Test activateSubscription
const activation = await subscriptionService.activateSubscription(1, 1);
console.log('Activation:', activation);
```

### **Bước 3: Test Error Handling**
```typescript
try {
  const result = await subscriptionService.getPlans();
} catch (error) {
  console.error('API Error:', error);
  // Error sẽ được handle bởi handleGlobalError trong api.ts
}
```

## Lợi Ích Của Việc Sử Dụng API Service

### **1. Centralized Error Handling**
- Tất cả errors được handle ở một nơi
- Consistent error format
- Global error logging

### **2. Authentication Management**
- Tự động thêm auth headers
- Token refresh logic
- Unauthorized handling

### **3. Request/Response Logging**
- Log tất cả API calls
- Debug dễ dàng hơn
- Performance monitoring

### **4. Code Reusability**
- Có thể dùng ở nhiều components
- Consistent API interface
- Easy to maintain

## Debug Commands

### **1. Kiểm tra Console Logs**
```bash
# Mở DevTools > Console
# Tìm các log sau:
API Request: https://api.lienketso.vn/public/api/subscription/plans
API Response Status: 200
✅ Global error handlers đã được đăng ký
```

### **2. Kiểm tra Network Tab**
```bash
# Mở DevTools > Network
# Filter by "subscription"
# Kiểm tra request/response
```

### **3. Test API Endpoints**
```bash
# Test plans API
curl -s "https://api.lienketso.vn/public/api/subscription/plans"

# Test club subscription API
curl -s "https://api.lienketso.vn/public/api/subscription/club/1"
```

## Nếu Vẫn Còn Lỗi

### **1. Kiểm tra API Response**
```typescript
// Trong subscriptionService
getPlans: async () => {
  try {
    const response = await apiService.get(API_ENDPOINTS.SUBSCRIPTION_PLANS);
    console.log('Raw API Response:', response);
    return response;
  } catch (error) {
    console.error('Subscription Service Error:', error);
    throw error;
  }
},
```

### **2. Kiểm tra API Endpoints**
```typescript
// Kiểm tra URL có đúng không
console.log('API Endpoint:', API_ENDPOINTS.SUBSCRIPTION_PLANS);
console.log('Full URL:', buildApiUrl(API_ENDPOINTS.SUBSCRIPTION_PLANS));
```

### **3. Kiểm tra Authentication**
```typescript
// Kiểm tra token có được gửi không
console.log('Auth Token:', localStorage.getItem('auth_token'));
console.log('Zalo GID:', localStorage.getItem('user_zalo_gid'));
```

## Kết Luận

**Đã hoàn thành việc chuyển đổi API calls vào file api.ts!**

### **Những gì đã thay đổi:**
1. ✅ Tạo `subscriptionService` trong `api.ts`
2. ✅ Thêm subscription endpoints vào `API_ENDPOINTS`
3. ✅ Cập nhật `subscription.tsx` để sử dụng service
4. ✅ Loại bỏ direct fetch calls
5. ✅ Giữ nguyên unmounting protection

### **Lợi ích:**
- 🚀 Code sạch hơn, dễ maintain
- 🛡️ Centralized error handling
- 🔐 Automatic authentication
- 📊 Better logging và debugging
- ♻️ Reusable API functions

**Bây giờ hãy test để đảm bảo không còn lỗi unmounting khi click "Quản lý gói"!** 🎯

