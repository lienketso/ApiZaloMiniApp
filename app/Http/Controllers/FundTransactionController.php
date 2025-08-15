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
        if ($request && $request->has('club_id')) {
            return $request->input('club_id');
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
            $clubId = $this->getCurrentUserClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            $transactions = FundTransaction::with('creator')
                ->byClub($clubId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Lấy danh sách giao dịch thành công'
            ]);
        } catch (\Exception $e) {
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
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:income,expense',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'transaction_date' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $clubId = $this->getCurrentUserClubId($request);
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

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
                'created_by' => $userId
            ]);

            $transaction->load('creator');

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Thêm giao dịch thành công'
            ], 201);

        } catch (\Exception $e) {
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
            $fundTransaction->load('creator');

            return response()->json([
                'success' => true,
                'data' => $fundTransaction,
                'message' => 'Lấy thông tin giao dịch thành công'
            ]);
        } catch (\Exception $e) {
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
            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|in:income,expense',
                'amount' => 'sometimes|numeric|min:0',
                'description' => 'sometimes|string|max:255',
                'category' => 'nullable|string|max:100',
                'transaction_date' => 'sometimes|date',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fundTransaction->update($request->only([
                'type', 'amount', 'description', 'category', 'transaction_date', 'notes'
            ]));

            $fundTransaction->load('creator');

            return response()->json([
                'success' => true,
                'data' => $fundTransaction,
                'message' => 'Cập nhật giao dịch thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FundTransaction $fundTransaction)
    {
        try {
            $fundTransaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa giao dịch thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa giao dịch: ' . $e->getMessage()
            ], 500);
        }
    }

    // Thống kê quỹ
    public function getFundStats(): JsonResponse
    {
        try {
            $clubId = $this->getCurrentUserClubId();
            
            if (!$clubId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy câu lạc bộ'
                ], 404);
            }

            $currentMonth = now()->month;
            $currentYear = now()->year;

            $totalFund = FundTransaction::byClub($clubId)->selectRaw('
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense
            ')->first();

            $monthlyIncome = FundTransaction::byClub($clubId)->income()
                ->byMonth($currentMonth, $currentYear)
                ->sum('amount');

            $monthlyExpense = FundTransaction::byClub($clubId)->expense()
                ->byMonth($currentMonth, $currentYear)
                ->sum('amount');

            $recentTransactions = FundTransaction::with('creator')
                ->byClub($clubId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $stats = [
                'total_fund' => ($totalFund->total_income ?? 0) - ($totalFund->total_expense ?? 0),
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
                'recent_transactions' => $recentTransactions
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Lấy thống kê quỹ thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê quỹ: ' . $e->getMessage()
            ], 500);
        }
    }

}
