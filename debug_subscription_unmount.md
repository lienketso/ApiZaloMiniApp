# 🐛 Debug Lỗi Unmounting trong Subscription Page

## Vấn Đề
Lỗi "unmounting" xảy ra khi component bị unmount trước khi API call hoàn thành, gây ra:
- Memory leak
- Warning trong console
- State update trên unmounted component
- Crash ứng dụng

## Nguyên Nhân
1. **Navigation nhanh**: User chuyển trang trước khi API hoàn thành
2. **Component unmount**: Parent component bị unmount
3. **Route change**: React Router thay đổi route
4. **Tab switching**: User chuyển tab trong mobile app

## Giải Pháp Đã Áp Dụng

### 1. **useRef để track mounted state**
```typescript
const isMounted = useRef(true);

useEffect(() => {
  return () => {
    isMounted.current = false;
  };
}, []);
```

### 2. **Kiểm tra mounted trước khi update state**
```typescript
// Trước khi update state
if (!isMounted.current) return;

// Sau khi update state
if (isMounted.current) {
  setLoading(false);
}
```

### 3. **Cleanup function trong useEffect**
```typescript
useEffect(() => {
  let isSubscribed = true;
  
  const fetchData = async () => {
    try {
      const data = await apiCall();
      if (isSubscribed) {
        setData(data);
      }
    } catch (error) {
      if (isSubscribed) {
        setError(error);
      }
    }
  };
  
  fetchData();
  
  return () => {
    isSubscribed = false;
  };
}, []);
```

## Cách Test

### 1. **Test Navigation nhanh**
```bash
# Mở trang subscription
# Click nút "Quản lý gói" từ club-list
# Ngay lập tức click nút back
# Kiểm tra console có warning không
```

### 2. **Test API call chậm**
```bash
# Thêm delay vào API call
setTimeout(() => {
  // API response
}, 3000);

# Trong thời gian chờ, navigate away
# Kiểm tra component có crash không
```

### 3. **Test Multiple API calls**
```bash
# Click nhiều button liên tiếp
# Nâng cấp gói -> Hủy -> Nâng cấp lại
# Kiểm tra state có bị conflict không
```

## Debug Tools

### 1. **Console Logging**
```typescript
useEffect(() => {
  console.log('🔍 SubscriptionPage mounted');
  return () => {
    console.log('🔍 SubscriptionPage unmounted');
  };
}, []);
```

### 2. **React DevTools**
- Component tab: Kiểm tra lifecycle
- Profiler: Kiểm tra render cycles
- Network tab: Kiểm tra API calls

### 3. **Error Boundary**
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

## Best Practices

### 1. **Sử dụng AbortController**
```typescript
useEffect(() => {
  const abortController = new AbortController();
  
  const fetchData = async () => {
    try {
      const response = await fetch(url, {
        signal: abortController.signal
      });
      // Process response
    } catch (error) {
      if (error.name === 'AbortError') {
        console.log('Fetch aborted');
      } else {
        console.error('Fetch error:', error);
      }
    }
  };
  
  fetchData();
  
  return () => {
    abortController.abort();
  };
}, []);
```

### 2. **Sử dụng React Query/SWR**
```typescript
import { useQuery } from 'react-query';

const { data, isLoading, error } = useQuery('plans', fetchPlans, {
  staleTime: 5 * 60 * 1000, // 5 minutes
  cacheTime: 10 * 60 * 1000, // 10 minutes
});
```

### 3. **Sử dụng Custom Hook**
```typescript
const useSubscriptionData = (clubId: number) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    let isMounted = true;
    
    const fetchData = async () => {
      if (!isMounted) return;
      // Fetch logic
    };
    
    fetchData();
    
    return () => {
      isMounted = false;
    };
  }, [clubId]);
  
  return { data, loading, error };
};
```

## Kiểm Tra Sau Khi Sửa

### 1. **Console không có warning**
- Không có "Can't perform a React state update on an unmounted component"
- Không có memory leak warning

### 2. **State update an toàn**
- Loading state được reset đúng cách
- Error state được clear khi cần
- Data được update chỉ khi component mounted

### 3. **Navigation mượt mà**
- Không có crash khi chuyển trang nhanh
- API calls được cancel đúng cách
- Memory usage ổn định

## Troubleshooting

### Nếu vẫn còn lỗi:
1. **Kiểm tra React version**: Đảm bảo dùng React 16.8+
2. **Kiểm tra Strict Mode**: Có thể gây double mount/unmount
3. **Kiểm tra Parent component**: Có bị unmount không
4. **Kiểm tra Route config**: Có vấn đề gì với routing không

### Debug commands:
```bash
# Kiểm tra React version
npm list react

# Kiểm tra console errors
# Mở DevTools > Console

# Kiểm tra memory usage
# Mở DevTools > Memory tab
```

## Kết Luận
Lỗi unmounting đã được fix bằng cách:
- Sử dụng useRef để track mounted state
- Kiểm tra mounted trước khi update state
- Thêm cleanup functions
- Xử lý error states đúng cách

Component giờ đây an toàn và không bị crash khi navigate nhanh.
