# 🧪 Test và Debug Lỗi Unmounting

## Vấn Đề Vẫn Còn
Nếu vẫn bị lỗi unmounting, có thể do:

1. **Navigation conflict** - Multiple navigation calls
2. **Parent component unmount** - Club list bị unmount
3. **Route change** - React Router issues
4. **State update race condition** - Multiple state updates

## Cách Test Triệt Để

### 1. **Test Navigation Nhanh**
```bash
# Bước 1: Mở club-list
# Bước 2: Click "Quản lý gói" trên một club
# Bước 3: Ngay lập tức click nút back (trong vòng 100ms)
# Bước 4: Kiểm tra console có warning không
```

### 2. **Test API Call Chậm**
```bash
# Bước 1: Mở trang subscription
# Bước 2: Click nút "Nâng cấp gói"
# Bước 3: Trong thời gian loading, click back
# Bước 4: Kiểm tra component có crash không
```

### 3. **Test Multiple Actions**
```bash
# Bước 1: Click "Nâng cấp gói"
# Bước 2: Ngay lập tức click "Hủy gói"
# Bước 3: Kiểm tra state có conflict không
```

## Debug Commands

### 1. **Console Logging**
Mở DevTools > Console và tìm:
```
🔍 SubscriptionPage mounted
🔍 SubscriptionPage unmounted
Request was aborted
Navigation blocked: already navigating or component unmounted
```

### 2. **React DevTools**
- **Components tab**: Kiểm tra lifecycle
- **Profiler**: Kiểm tra render cycles
- **Network tab**: Kiểm tra API calls

### 3. **Memory Tab**
- Kiểm tra memory leak
- Kiểm tra component instances

## Các Biện Pháp Đã Áp Dụng

### 1. **AbortController**
```typescript
const controller = new AbortController();
abortControllerRef.current = controller;

const response = await fetch(url, {
  signal: controller.signal
});
```

### 2. **Safe State Update**
```typescript
const safeSetState = useCallback(<T,>(
  setter: React.Dispatch<React.SetStateAction<T>>,
  value: T
) => {
  if (isMountedRef.current) {
    setter(value);
  }
}, []);
```

### 3. **Navigation Protection**
```typescript
const safeNavigate = (to: string | number) => {
  if (isNavigating.current || !isMounted.current) {
    console.log('Navigation blocked');
    return;
  }
  // Perform navigation
};
```

## Nếu Vẫn Còn Lỗi

### 1. **Kiểm tra Parent Component**
```typescript
// Trong club-list.tsx
useEffect(() => {
  console.log('🔍 ClubList mounted');
  return () => {
    console.log('🔍 ClubList unmounted');
  };
}, []);
```

### 2. **Kiểm tra Route Config**
```typescript
// Kiểm tra có nested routes không
// Kiểm tra có route guards không
// Kiểm tra có navigation middleware không
```

### 3. **Kiểm tra React Version**
```bash
npm list react
npm list react-dom
```

### 4. **Kiểm tra Strict Mode**
```typescript
// Trong main.tsx hoặc App.tsx
// Kiểm tra có <React.StrictMode> không
```

## Emergency Fix

Nếu vẫn không được, hãy thử:

### 1. **Disable Strict Mode**
```typescript
// Trong main.tsx
// Comment out StrictMode
// <React.StrictMode>
  <App />
// </React.StrictMode>
```

### 2. **Use Error Boundary**
```typescript
class SubscriptionErrorBoundary extends React.Component {
  componentDidCatch(error, errorInfo) {
    console.error('Subscription Error:', error, errorInfo);
  }
  
  render() {
    if (this.state.hasError) {
      return <h1>Something went wrong.</h1>;
    }
    return this.props.children;
  }
}
```

### 3. **Force Cleanup**
```typescript
useEffect(() => {
  const cleanup = () => {
    // Force cleanup tất cả
    setLoading(false);
    setError(null);
    setPlans([]);
    setSelectedPlan(null);
    setShowUpgradeModal(false);
  };

  return cleanup;
}, []);
```

## Test Commands

### 1. **Test API Endpoints**
```bash
# Test plans API
curl -s "https://api.lienketso.vn/public/api/subscription/plans"

# Test club subscription API
curl -s "https://api.lienketso.vn/public/api/subscription/club/1"
```

### 2. **Test Navigation**
```bash
# Mở DevTools > Console
# Thực hiện navigation nhanh
# Kiểm tra console logs
```

### 3. **Test Memory**
```bash
# Mở DevTools > Memory
# Take heap snapshot
# Thực hiện navigation
# Take heap snapshot again
# Compare snapshots
```

## Kết Luận

Nếu vẫn còn lỗi sau khi áp dụng tất cả biện pháp trên:

1. **Kiểm tra React version** - Có thể cần upgrade
2. **Kiểm tra ZMP-UI version** - Có thể có conflict
3. **Kiểm tra routing library** - Có thể có bug
4. **Kiểm tra parent components** - Có thể bị unmount

**Hãy cung cấp console logs cụ thể để debug tiếp!**

