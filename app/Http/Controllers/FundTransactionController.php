<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FundTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class FundTransactionController extends Controller
{
    /**
     * Get current user's club ID
     */
    private function getCurrentUserClubId(Request $request = null)
    {
        // Thử lấy từ authenticated user trước
        if (Auth::check()) {
            $userId = Auth::id();
            $club = \App\Models\Club::where('created_by', $userId)->first();
            if ($club) {
                return $club->id;
            }
        }
        
        // Nếu không có auth, thử lấy từ request hoặc sử dụng fallback
        if ($request) {
            // Ưu tiên lấy từ query params trước
            if ($request->query('club_id')) {
                return $request->query('club_id');
            }
            // Sau đó lấy từ input
            if ($request->input('club_id')) {
                return $request->input('club_id');
            }
        }
        
        // Fallback: lấy club đầu tiên
        $firstClub = \App\Models\Club::first();
        return $firstClub ? $firstClub->id : null;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            \Log::info('FundTransactionController::index - Fetching transactions:', [
                'request_params' => $request->all(),
                'query_params' => $request->query(),
                'club_id_from_query' => $request->query('club_id'),
                'club_id_from_input' => $request->input('club_id')
            ]);

            $clubId = $this->getCurrentUserClubId($request);
            
            if (!$clubId) {
                \Log::warning('FundTransactionController::index - No club found');
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            \Log::info('FundTransactionController::index - Using club_id:', ['club_id' => $clubId]);

            // Cập nhật club_id cho transactions cũ có club_id = NULL
            $updatedCount = FundTransaction::whereNull('club_id')->update(['club_id' => $clubId]);
            if ($updatedCount > 0) {
                \Log::info('FundTransactionController::index - Updated club_id for old transactions:', [
                    'updated_count' => $updatedCount,
                    'club_id' => $clubId
                ]);
            }

            $query = FundTransaction::with(['creator', 'user'])
                ->byClub($clubId);

            // Filter theo trạng thái nếu có
            if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'cancelled'])) {
                $query->where('status', $request->status);
            }

            // Filter theo loại giao dịch nếu có
            if ($request->has('type') && in_array($request->type, ['income', 'expense'])) {
                $query->where('type', $request->type);
            }

            // Filter theo match_id nếu có (để xem giao dịch của trận đấu cụ thể)
            if ($request->has('match_id')) {
                $query->where('match_id', $request->match_id);
            }

            $transactions = $query
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('FundTransactionController::index - Found transactions:', [
                'count' => $transactions->count(),
                'club_id' => $clubId,
                'sample_transaction' => $transactions->first() ? $transactions->first()->toArray() : null,
                'all_transactions_club_ids' => $transactions->pluck('club_id')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Lấy danh sách giao dịch thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error('FundTransactionController::index - Error fetching transactions:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Xử lý Laravel method override cho DELETE
        if ($request->has('_method') && $request->input('_method') === 'DELETE') {
            $id = $request->route('id') ?? $request->input('id');
            if ($id) {
                return $this->performDelete($request, $id);
            }
        }
        
        // Xử lý tạo giao dịch mới
        try {
            \Log::info('FundTransactionController::store - Creating new transaction:', [
                'request_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:income,expense',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'transaction_date' => 'required|date',
                'notes' => 'nullable|string',
                'status' => 'required|in:pending,completed,cancelled',
                'user_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                \Log::warning('FundTransactionController::store - Validation failed:', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $this->getCurrentUserClubId($request);
            
            if (!$clubId) {
                \Log::warning('FundTransactionController::store - No club found');
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            \Log::info('FundTransactionController::store - Using club_id:', ['club_id' => $clubId]);

            // Lấy user ID từ auth hoặc từ request
            $userId = Auth::id() ?? $request->input('created_by', 1);

            $transaction = FundTransaction::create([
                'club_id' => $clubId,
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
                'category' => $request->category,
                'transaction_date' => $request->transaction_date,
                'notes' => $request->notes,
                'status' => $request->status,
                'user_id' => $request->user_id,
                'created_by' => $userId
            ]);

            $transaction->load(['creator', 'user']);

            \Log::info('FundTransactionController::store - Transaction created successfully:', [
                'transaction_id' => $transaction->id,
                'club_id' => $transaction->club_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Thêm giao dịch thành công'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::store - Error creating transaction:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(fundTransaction $fundTransaction)
    {
        try {
            \Log::info('FundTransactionController::show - Fetching transaction:', [
                'transaction_id' => $fundTransaction->id,
                'club_id' => $fundTransaction->club_id
            ]);

            $fundTransaction->load(['creator', 'user']);

            return response()->json([
                'success' => true,
                'data' => $fundTransaction,
                'message' => 'Lấy thông tin giao dịch thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error('FundTransactionController::show - Error fetching transaction:', [
                'transaction_id' => $fundTransaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FundTransaction $fundTransaction)
    {
        try {
            \Log::info('FundTransactionController::update - Updating transaction:', [
                'transaction_id' => $fundTransaction->id,
                'transaction_club_id' => $fundTransaction->club_id,
                'request_data' => $request->all(),
                'request_headers' => $request->headers->all()
            ]);

            // Lấy club_id từ request hoặc header
            $clubIdFromInput = $request->input('club_id');
            $clubIdFromHeader = $request->header('X-Club-ID');
            $clubId = $clubIdFromInput ?? $clubIdFromHeader;
            
            \Log::info('FundTransactionController::update - Club ID extraction:', [
                'club_id_from_input' => $clubIdFromInput,
                'club_id_from_header' => $clubIdFromHeader,
                'final_club_id' => $clubId,
                'all_request_inputs' => $request->all(),
                'all_request_headers' => $request->headers->all()
            ]);
            
            if (!$clubId) {
                \Log::warning('FundTransactionController::update - No club_id provided');
                return response()->json([
                    'success' => false,
                    'message' => 'club_id is required'
                ], 400);
            }

            // Kiểm tra xem giao dịch có thuộc club này không
            if (!$fundTransaction->club_id) {
                \Log::warning('FundTransactionController::update - Transaction missing club_id, updating it:', [
                    'transaction_id' => $fundTransaction->id,
                    'request_club_id' => $clubId
                ]);
                
                // Cập nhật club_id cho transaction nếu nó bị NULL
                $fundTransaction->update(['club_id' => $clubId]);
                \Log::info('FundTransactionController::update - Updated transaction club_id from NULL to:', ['club_id' => $clubId]);
            } elseif ((int)$fundTransaction->club_id !== (int)$clubId) {
                \Log::warning('FundTransactionController::update - Transaction does not belong to club:', [
                    'transaction_club_id' => $fundTransaction->club_id,
                    'transaction_club_id_type' => gettype($fundTransaction->club_id),
                    'request_club_id' => $clubId,
                    'request_club_id_type' => gettype($clubId),
                    'strict_equal' => (int)$fundTransaction->club_id === (int)$clubId,
                    'loose_equal' => $fundTransaction->club_id == $clubId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền cập nhật giao dịch này'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|in:income,expense',
                'amount' => 'sometimes|numeric|min:0',
                'description' => 'sometimes|string|max:255',
                'category' => 'nullable|string|max:100',
                'transaction_date' => 'sometimes|date',
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:pending,completed,cancelled',
                'user_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                \Log::warning('FundTransactionController::update - Validation failed:', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            \Log::info('FundTransactionController::update - Validation passed, updating transaction');

            $fundTransaction->update($request->only([
                'type', 'amount', 'description', 'category', 'transaction_date', 'notes', 'status', 'user_id'
            ]));

            $fundTransaction->load(['creator', 'user']);

            \Log::info('FundTransactionController::update - Transaction updated successfully:', [
                'transaction_id' => $fundTransaction->id,
                'updated_data' => $fundTransaction->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $fundTransaction,
                'message' => 'Cập nhật giao dịch thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::update - Error updating transaction:', [
                'transaction_id' => $fundTransaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        // Xử lý Laravel method override (_method: DELETE)
        if ($request->has('_method') && $request->input('_method') === 'DELETE') {
            return $this->performDelete($request, $id);
        }
        
        // Xử lý DELETE method thông thường
        return $this->performDelete($request, $id);
    }

    /**
     * Perform the actual deletion logic
     */
    private function performDelete(Request $request, $id)
    {
        try {
            \Log::info('FundTransactionController::destroy - Attempting to delete transaction:', [
                'transaction_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Lấy club_id từ request hoặc header
            $clubId = $request->input('club_id') ?? $request->header('X-Club-ID');
            
            if (!$clubId) {
                \Log::warning('FundTransactionController::destroy - No club_id provided');
                return response()->json([
                    'success' => false,
                    'message' => 'club_id is required'
                ], 400);
            }

            \Log::info('FundTransactionController::destroy - Using club_id:', ['club_id' => $clubId]);

            // Tìm giao dịch theo ID và club_id
            $transaction = FundTransaction::where('id', $id)
                ->where('club_id', $clubId)
                ->first();

            if (!$transaction) {
                \Log::warning('FundTransactionController::destroy - Transaction not found:', [
                    'transaction_id' => $id,
                    'club_id' => $clubId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch quỹ'
                ], 404);
            }

            \Log::info('FundTransactionController::destroy - Found transaction:', [
                'transaction_id' => $transaction->id,
                'club_id' => $transaction->club_id,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'description' => $transaction->description
            ]);

            // Thử xóa giao dịch
            $deleted = $transaction->delete();
            
            \Log::info('FundTransactionController::destroy - Delete result:', [
                'transaction_id' => $id,
                'deleted' => $deleted
            ]);

            if ($deleted) {
                // Kiểm tra xem giao dịch có thực sự bị xóa không
                $stillExists = FundTransaction::where('id', $id)->exists();
                
                \Log::info('FundTransactionController::destroy - Verification after delete:', [
                    'transaction_id' => $id,
                    'still_exists' => $stillExists
                ]);

                if (!$stillExists) {
                    \Log::info('FundTransactionController::destroy - Transaction successfully deleted from database');
                    return response()->json([
                        'success' => true,
                        'message' => 'Xóa giao dịch thành công'
                    ]);
                } else {
                    \Log::error('FundTransactionController::destroy - Delete failed: transaction still exists in database');
                    return response()->json([
                        'success' => false,
                        'message' => 'Lỗi: Giao dịch không được xóa khỏi database'
                    ], 500);
                }
            } else {
                \Log::error('FundTransactionController::destroy - Delete operation returned false');
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi xóa giao dịch'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::destroy - Exception occurred:', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái giao dịch quỹ (từ pending sang completed)
     */
    public function updateTransactionStatus(Request $request, $id): JsonResponse
    {
        try {
            \Log::info('FundTransactionController::updateTransactionStatus - Updating transaction status:', [
                'transaction_id' => $id,
                'request_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,cancelled',
                'club_id' => 'required|integer|exists:clubs,id'
            ]);

            if ($validator->fails()) {
                \Log::warning('FundTransactionController::updateTransactionStatus - Validation failed:', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->input('club_id');
            
            \Log::info('FundTransactionController::updateTransactionStatus - Using club_id:', ['club_id' => $clubId]);
            
            // Tìm giao dịch theo ID và club_id
            $transaction = FundTransaction::where('id', $id)
                ->where('club_id', $clubId)
                ->first();

            if (!$transaction) {
                \Log::warning('FundTransactionController::updateTransactionStatus - Transaction not found:', [
                    'transaction_id' => $id,
                    'club_id' => $clubId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch quỹ'
                ], 404);
            }

            \Log::info('FundTransactionController::updateTransactionStatus - Found transaction:', [
                'transaction_id' => $transaction->id,
                'club_id' => $transaction->club_id,
                'old_status' => $transaction->status
            ]);

            // Cập nhật trạng thái
            $oldStatus = $transaction->status;
            $transaction->update(['status' => $request->status]);

            // Log thay đổi trạng thái
            \Log::info('FundTransactionController::updateTransactionStatus - Status updated successfully:', [
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'club_id' => $clubId,
                'description' => $transaction->description
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction->fresh()->load(['creator', 'user']),
                'message' => 'Cập nhật trạng thái giao dịch thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::updateTransactionStatus - Error updating status:', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách giao dịch quỹ theo trạng thái
     */
    public function getTransactionsByStatus(Request $request): JsonResponse
    {
        try {
            \Log::info('FundTransactionController::getTransactionsByStatus - Fetching transactions by status:', [
                'request_data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,cancelled',
                'club_id' => 'required|integer|exists:clubs,id'
            ]);

            if ($validator->fails()) {
                \Log::warning('FundTransactionController::getTransactionsByStatus - Validation failed:', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $request->input('club_id');
            $status = $request->input('status');

            \Log::info('FundTransactionController::getTransactionsByStatus - Using club_id and status:', [
                'club_id' => $clubId,
                'status' => $status
            ]);

            $transactions = FundTransaction::with(['creator', 'club', 'user'])
                ->where('club_id', $clubId)
                ->where('status', $status)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('FundTransactionController::getTransactionsByStatus - Found transactions:', [
                'count' => $transactions->count(),
                'club_id' => $clubId,
                'status' => $status
            ]);

            $statusLabels = [
                'pending' => 'Chờ nộp',
                'completed' => 'Đã nộp',
                'cancelled' => 'Đã hủy'
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'status_label' => $statusLabels[$status],
                    'total_count' => $transactions->count(),
                    'total_amount' => $transactions->sum('amount')
                ],
                'message' => 'Lấy danh sách giao dịch theo trạng thái thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::getTransactionsByStatus - Error fetching transactions:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    // Thống kê quỹ
    public function getFundStats(Request $request = null): JsonResponse
    {
        try {
            \Log::info('FundTransactionController::getFundStats - Fetching fund statistics');
            
            $clubId = $this->getCurrentUserClubId($request);
            
            if (!$clubId) {
                \Log::warning('FundTransactionController::getFundStats - No club found');
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            \Log::info('FundTransactionController::getFundStats - Using club_id:', ['club_id' => $clubId]);

            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Tính tổng quỹ từ giao dịch đã hoàn thành
            $totalFund = FundTransaction::byClub($clubId)
                ->where('status', 'completed') // Chỉ tính giao dịch đã hoàn thành
                ->selectRaw('
                    SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense
                ')->first();

            // Debug log để kiểm tra
            \Log::info('Fund calculation debug:', [
                'club_id' => $clubId,
                'total_income_raw' => $totalFund->total_income ?? 'null',
                'total_expense_raw' => $totalFund->total_expense ?? 'null',
                'total_income_type' => gettype($totalFund->total_income ?? null),
                'total_expense_type' => gettype($totalFund->total_expense ?? null)
            ]);

            // Đảm bảo giá trị là số
            $totalIncome = is_numeric($totalFund->total_income) ? (float)$totalFund->total_income : 0;
            $totalExpense = is_numeric($totalFund->total_expense) ? (float)$totalFund->total_expense : 0;
            $calculatedTotalFund = $totalIncome - $totalExpense;

            \Log::info('Fund calculation result:', [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'calculated_total_fund' => $calculatedTotalFund
            ]);

            $monthlyIncome = FundTransaction::byClub($clubId)
                ->where('status', 'completed') // Chỉ tính giao dịch đã hoàn thành
                ->income()
                ->byMonth($currentMonth, $currentYear)
                ->sum('amount');

            $monthlyExpense = FundTransaction::byClub($clubId)
                ->where('status', 'completed') // Chỉ tính giao dịch đã hoàn thành
                ->expense()
                ->byMonth($currentMonth, $currentYear)
                ->sum('amount');

            $recentTransactions = FundTransaction::with(['creator', 'user'])
                ->byClub($clubId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Thống kê giao dịch pending (chờ nộp)
            $pendingTransactions = FundTransaction::byClub($clubId)
                ->where('status', 'pending')
                ->get();

            $pendingIncome = $pendingTransactions->where('type', 'income')->sum('amount');
            $pendingExpense = $pendingTransactions->where('type', 'expense')->sum('amount');

            $stats = [
                'total_fund' => $calculatedTotalFund,
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
                'pending_income' => $pendingIncome, // Thu chờ nộp
                'pending_expense' => $pendingExpense, // Chi chờ xử lý
                'pending_count' => $pendingTransactions->count(),
                'recent_transactions' => $recentTransactions,
                'club_id' => $clubId
            ];

            \Log::info('FundTransactionController::getFundStats - Statistics calculated successfully:', [
                'club_id' => $clubId,
                'total_fund' => $calculatedTotalFund,
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
                'pending_count' => $pendingTransactions->count(),
                'pending_income' => $pendingIncome,
                'pending_expense' => $pendingExpense
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Lấy thống kê quỹ thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('FundTransactionController::getFundStats - Error calculating statistics:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê quỹ: ' . $e->getMessage()
            ], 500);
        }
    }

}
