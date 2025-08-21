# Test Chức Năng Tạo Giao Dịch Quỹ Tự Động

## Mô tả chức năng

Khi cập nhật kết quả trận đấu, hệ thống sẽ tự động tạo giao dịch quỹ cho đội thua:

### ✅ Các bước thực hiện:

1. **Cập nhật kết quả trận đấu** (điểm số + đội thắng)
2. **Xác định đội thua** dựa trên kết quả
3. **Lấy danh sách cầu thủ** của đội thua
4. **Tính số tiền mỗi người** (chia đều tổng số tiền cược)
5. **Tạo giao dịch quỹ** cho từng cầu thủ

### 📊 Cấu trúc giao dịch quỹ:

```php
$transactionData = [
    'type' => 'expense',           // Loại: chi tiêu (nộp quỹ)
    'amount' => $amountPerPlayer,  // Số tiền mỗi người phải nộp
    'description' => "Nộp quỹ trận đấu: {$match->title} - Đội {$loserTeam->name} thua",
    'transaction_date' => now()->format('Y-m-d'),
    'notes' => "Trận đấu ID: {$match->id}, Đội thua: {$loserTeam->name}, Cầu thủ: {$player->name}",
    'created_by' => $player->id,   // ID của cầu thủ phải nộp quỹ
    'created_at' => now(),
    'updated_at' => now()
];
```

### 🔄 Luồng xử lý:

1. **Frontend**: Gọi API `PUT /matches/{id}/result`
2. **Backend**: 
   - Validate dữ liệu
   - Cập nhật điểm số và đội thắng
   - Gọi `createFundTransactionsForLosers()`
   - Tạo giao dịch quỹ cho từng cầu thủ đội thua
3. **Database**: Lưu giao dịch vào bảng `fund_transactions`
4. **Response**: Trả về thông báo thành công

### 📋 Các trường hợp test:

#### Test Case 1: Trận đấu bình thường
- **Input**: Đội A thắng 3-1, đội B thua
- **Expected**: Tạo 2 giao dịch quỹ cho đội B, mỗi người nộp `betAmount/2`

#### Test Case 2: Đội thua có nhiều cầu thủ
- **Input**: Đội A thắng 2-0, đội B có 3 cầu thủ
- **Expected**: Tạo 3 giao dịch quỹ, mỗi người nộp `betAmount/3`

#### Test Case 3: Đội thua không có cầu thủ
- **Input**: Đội A thắng 1-0, đội B không có cầu thủ
- **Expected**: Không tạo giao dịch quỹ, log warning

#### Test Case 4: Lỗi database
- **Input**: Database connection lỗi
- **Expected**: Log error, không ảnh hưởng đến việc cập nhật kết quả

### 🧪 Cách test:

1. **Tạo trận đấu** với bet amount > 0
2. **Thêm cầu thủ** vào cả 2 đội
3. **Bắt đầu trận đấu** (status = 'ongoing')
4. **Cập nhật kết quả** với điểm số khác nhau
5. **Kiểm tra database**:
   ```sql
   SELECT * FROM fund_transactions 
   WHERE notes LIKE '%Trận đấu ID: {match_id}%'
   ORDER BY created_at DESC;
   ```

### 📝 Logs cần kiểm tra:

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep "createFundTransactionsForLosers"
```

### 🎯 Kết quả mong đợi:

- ✅ **Kết quả trận đấu** được cập nhật thành công
- ✅ **Giao dịch quỹ** được tạo cho đội thua
- ✅ **Số tiền chia đều** cho từng cầu thủ
- ✅ **Thông báo frontend** rõ ràng
- ✅ **Logs chi tiết** để debug

### ⚠️ Lưu ý:

- Giao dịch quỹ được tạo với `type = 'expense'`
- Số tiền được chia đều cho tất cả cầu thủ đội thua
- Nếu có lỗi tạo giao dịch, không ảnh hưởng đến việc cập nhật kết quả
- Tất cả giao dịch đều có `notes` chi tiết để trace
