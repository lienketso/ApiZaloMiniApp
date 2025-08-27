# 🚨 Debug Màn Hình Trắng Subscription Page

## Vấn Đề
- Click "Quản lý gói" → Màn hình trắng
- Component bị unmount ngay lập tức
- Không có error message

## Các Biện Pháp Đã Áp Dụng

### ✅ **1. Error Boundary**
```typescript
// subscription-error-boundary.tsx
class SubscriptionErrorBoundary extends React.Component {
  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('🚨 Subscription Error Boundary caught an error:', error, errorInfo);
  }
}
```

### ✅ **2. Safe Wrapper**
```typescript
// subscription-safe-wrapper.tsx
const SubscriptionSafeWrapper = ({ children, club }) => {
  // Kiểm tra club data
  // Loading state
  // Error handling
}
```

### ✅ **3. Detailed Logging**
```typescript
// subscription.tsx
console.log('🔍 SubscriptionPage mounted with club:', club);
console.log('🔍 loadPlans called, isMounted:', isMountedRef.current);
console.log('🔍 Safe state update:', value);
```

## Cách Debug

### **Bước 1: Kiểm tra Console Logs**
```bash
# Mở DevTools > Console
# Tìm các log sau:
🔍 SubscriptionSafeWrapper mounted
🔍 SubscriptionPage mounted with club: {...}
🔍 loadPlans called, isMounted: true
🔍 Calling subscriptionService.getPlans()
```

### **Bước 2: Kiểm tra Network Tab**
```bash
# Mở DevTools > Network
# Filter by "subscription"
# Kiểm tra có API call nào không
# Kiểm tra response status
```

### **Bước 3: Kiểm tra React DevTools**
```bash
# Mở DevTools > Components
# Tìm SubscriptionSafeWrapper
# Tìm SubscriptionPage
# Kiểm tra props và state
```

## Các Nguyên Nhân Có Thể

### **1. Club Data Missing**
```typescript
// Kiểm tra club object
console.log('Club data:', club);
console.log('Club ID:', club?.id);
console.log('Club name:', club?.name);
```

### **2. API Service Error**
```typescript
// Kiểm tra subscriptionService
console.log('SubscriptionService:', subscriptionService);
console.log('API_ENDPOINTS:', API_ENDPOINTS);
```

### **3. Navigation Issue**
```typescript
// Kiểm tra navigation
console.log('Current pathname:', window.location.pathname);
console.log('Navigation history:', window.history);
```

### **4. ZMP-UI Component Error**
```typescript
// Kiểm tra ZMP-UI components
console.log('ZMP-UI available:', typeof Box, typeof Page, typeof Text);
```

## Test Commands

### **1. Test Club Data**
```typescript
// Trong club-list.tsx, trước khi navigate
console.log('🔍 Navigating to subscription with club:', club);
console.log('🔍 Club ID:', club.id);
console.log('🔍 Club subscription status:', club.subscription_status);
```

### **2. Test API Service**
```typescript
// Test trực tiếp subscriptionService
try {
  const plans = await subscriptionService.getPlans();
  console.log('🔍 Plans API test success:', plans);
} catch (error) {
  console.error('🔍 Plans API test failed:', error);
}
```

### **3. Test Component Mount**
```typescript
// Trong subscription.tsx
useEffect(() => {
  console.log('🔍 Component mounted at:', new Date().toISOString());
  console.log('🔍 Club prop:', club);
  console.log('🔍 Window location:', window.location.href);
  
  return () => {
    console.log('🔍 Component unmounting at:', new Date().toISOString());
  };
}, []);
```

## Emergency Debug

### **1. Disable All Features**
```typescript
// Comment out tất cả logic phức tạp
return (
  <Page className="bg-gray-50">
    <Box className="p-4">
      <Text>Debug: Subscription Page</Text>
      <Text>Club ID: {club?.id}</Text>
      <Text>Club Name: {club?.name}</Text>
    </Box>
  </Page>
);
```

### **2. Check Parent Component**
```typescript
// Trong club-list.tsx
useEffect(() => {
  console.log('🔍 ClubList mounted');
  return () => {
    console.log('🔍 ClubList unmounting');
  };
}, []);

// Trước khi navigate
console.log('🔍 About to navigate to subscription');
```

### **3. Check Route Configuration**
```typescript
// Kiểm tra route config
// Có thể có route guard hoặc middleware
console.log('🔍 Route params:', routeParams);
console.log('🔍 Route path:', routePath);
```

## Nếu Vẫn Không Được

### **1. Check Browser Console Errors**
```bash
# Mở DevTools > Console
# Tìm JavaScript errors
# Tìm React errors
# Tìm Network errors
```

### **2. Check React Strict Mode**
```typescript
// Trong main.tsx
// Comment out StrictMode
// <React.StrictMode>
  <App />
// </React.StrictMode>
```

### **3. Check ZMP Environment**
```typescript
// Kiểm tra ZMP environment
console.log('🔍 ZMP Environment:', typeof window !== 'undefined' && !!(window as any).ZaloSocialKit);
console.log('🔍 User Agent:', navigator.userAgent);
```

## Kết Luận

**Đã áp dụng tất cả biện pháp bảo vệ có thể:**

1. ✅ Error Boundary - Bắt lỗi React
2. ✅ Safe Wrapper - Kiểm tra data và loading
3. ✅ Detailed Logging - Track mọi bước
4. ✅ Unmounting Protection - Chặn state update
5. ✅ API Service - Centralized API calls

**Nếu vẫn còn màn hình trắng, có thể do:**

- Club data bị null/undefined
- API service không load được
- ZMP-UI component conflict
- Route configuration issue
- Parent component unmount

**Hãy cung cấp console logs cụ thể để debug tiếp!** 🔍
