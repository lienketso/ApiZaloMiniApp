<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Event;
use App\Models\FundTransaction;
use App\Models\UserClub;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $prevStartOfMonth = $startOfMonth->copy()->subMonth();
        $prevEndOfMonth = $startOfMonth->copy()->subDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();

        $activeClubsTotal = Club::where(function ($query) {
            $query->where('subscription_status', 'active')
                  ->orWhere('is_setup', true);
        })->count();

        $newClubsCurrent = Club::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $newClubsPrevious = Club::whereBetween('created_at', [$prevStartOfMonth, $prevEndOfMonth])->count();

        $activeMembers = UserClub::where('status', 'active')
            ->distinct()
            ->count('user_id');
        $newMembersCurrent = UserClub::where('status', 'active')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->distinct()
            ->count('user_id');
        $newMembersPrevious = UserClub::where('status', 'active')
            ->whereBetween('created_at', [$prevStartOfMonth, $prevEndOfMonth])
            ->distinct()
            ->count('user_id');

        $revenueCurrent = FundTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $revenuePrevious = FundTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$prevStartOfMonth, $prevEndOfMonth])
            ->sum('amount');

        $eventsThisWeek = Event::whereBetween('start_date', [$startOfWeek, $endOfWeek])->count();
        $eventsOngoing = Event::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->count();

        $metrics = [
            [
                'label' => 'CLB đang hoạt động',
                'value' => number_format($activeClubsTotal),
                'trend' => $this->formatPercentChange($newClubsCurrent, $newClubsPrevious),
                'color' => 'from-emerald-400 to-emerald-500',
            ],
            [
                'label' => 'Thành viên toàn hệ thống',
                'value' => number_format($activeMembers),
                'trend' => $this->formatPercentChange($newMembersCurrent, $newMembersPrevious),
                'color' => 'from-violet-400 to-indigo-500',
            ],
            [
                'label' => 'Doanh thu tháng',
                'value' => $this->formatCurrency($revenueCurrent),
                'trend' => $this->formatRevenueDelta($revenueCurrent, $revenuePrevious),
                'color' => 'from-amber-300 to-orange-400',
            ],
            [
                'label' => 'Sự kiện trong tuần',
                'value' => number_format($eventsThisWeek),
                'trend' => $eventsOngoing . ' đang diễn ra',
                'color' => 'from-sky-400 to-blue-500',
            ],
        ];

        $clubs = $this->buildClubPerformanceCards($startOfMonth, $endOfMonth, $prevStartOfMonth, $prevEndOfMonth);
        $activities = $this->buildActivitiesFeed();

        return view('dashboard', [
            'metrics' => $metrics,
            'clubs' => $clubs,
            'activities' => $activities,
            'menu' => $this->buildMenu(),
            'systemStatus' => $this->buildSystemStatus($now, $revenueCurrent),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildClubPerformanceCards(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        return Club::with('plan')
            ->withCount(['userClubs as active_members' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderByDesc('active_members')
            ->take(3)
            ->get()
            ->map(function (Club $club) use ($currentStart, $currentEnd, $previousStart, $previousEnd) {
                $currentRevenue = $club->fundTransactions()
                    ->where('type', 'income')
                    ->whereBetween('transaction_date', [$currentStart, $currentEnd])
                    ->sum('amount');

                $previousRevenue = $club->fundTransactions()
                    ->where('type', 'income')
                    ->whereBetween('transaction_date', [$previousStart, $previousEnd])
                    ->sum('amount');

                return [
                    'name' => $club->name ?? 'Chưa đặt tên',
                    'members' => $club->active_members ?? 0,
                    'status' => $this->formatSubscriptionStatus($club->subscription_status),
                    'revenue' => $this->formatCurrency($currentRevenue),
                    'trend' => $this->formatPercentChange($currentRevenue, $previousRevenue),
                    'trend_class' => $currentRevenue >= $previousRevenue ? 'text-emerald-300' : 'text-red-300',
                    'badge' => $club->plan->name ?? $this->formatBadgeFromStatus($club->subscription_status),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildActivitiesFeed(): array
    {
        $now = Carbon::now();

        $activities = collect();

        $activities = $activities->merge(
            Event::with('club')
                ->latest('updated_at')
                ->take(3)
                ->get()
                ->map(function (Event $event) {
                    $timestamp = $event->updated_at ?? $event->start_date ?? Carbon::now();

                    return [
                        'timestamp' => $timestamp,
                        'time' => $timestamp->diffForHumans(),
                        'title' => $event->club->name ?? 'Sự kiện mới',
                        'detail' => 'Cập nhật sự kiện: ' . $event->title,
                        'type' => 'event',
                    ];
                })
        );

        $activities = $activities->merge(
            FundTransaction::with('club')
                ->latest('transaction_date')
                ->take(3)
                ->get()
                ->map(function (FundTransaction $transaction) {
                    $timestamp = Carbon::parse($transaction->transaction_date ?? $transaction->created_at ?? now());
                    $clubName = $transaction->club->name ?? 'Hệ thống';

                    return [
                        'timestamp' => $timestamp,
                        'time' => $timestamp->diffForHumans(),
                        'title' => 'Giao dịch mới',
                        'detail' => $clubName . ' ghi nhận ' . ($transaction->type === 'income' ? 'thu ' : 'chi ') . $this->formatCurrency($transaction->amount),
                        'type' => 'finance',
                    ];
                })
        );

        $activities = $activities->merge(
            UserClub::with(['club', 'user'])
                ->latest()
                ->take(3)
                ->get()
                ->map(function (UserClub $membership) {
                    $timestamp = $membership->created_at ?? Carbon::now();
                    $clubName = $membership->club->name ?? 'CLB';
                    $userName = $membership->user->name ?? 'Thành viên mới';

                    return [
                        'timestamp' => $timestamp,
                        'time' => $timestamp->diffForHumans(),
                        'title' => 'Thành viên mới',
                        'detail' => $userName . ' tham gia ' . $clubName . ' (' . ($membership->status ?? 'pending') . ')',
                        'type' => 'member',
                    ];
                })
        );

        $activities = $activities
            ->sortByDesc('timestamp')
            ->take(6)
            ->map(function ($item) {
                unset($item['timestamp']);
                return $item;
            })
            ->values();

        if ($activities->isEmpty()) {
            return [[
                'time' => $now->diffForHumans(),
                'title' => 'Chưa có dữ liệu',
                'detail' => 'Bảng điều khiển sẽ hiển thị hoạt động ngay khi có dữ liệu thực tế.',
                'type' => 'info',
            ]];
        }

        return $activities->toArray();
    }

    private function buildMenu(): array
    {
        return [
            [
                'label' => 'Tổng quan',
                'route' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
            ],
            [
                'label' => 'Quản lý CLB',
                'route' => route('admin.clubs.index'),
                'active' => request()->routeIs('admin.clubs.*'),
            ],
            [
                'label' => 'Thành viên',
                'route' => route('admin.members.index'),
                'active' => request()->routeIs('admin.members.*'),
            ],
            ['label' => 'Tài chính', 'route' => '#'],
            ['label' => 'Sự kiện', 'route' => '#'],
            ['label' => 'Hệ thống & phân quyền', 'route' => '#'],
        ];
    }

    private function buildSystemStatus(Carbon $now, float|int $monthlyRevenue): array
    {
        return [
            'uptime' => '100% dịch vụ ổn định',
            'latency' => 'Độ trễ API trung bình: 186ms',
            'traffic' => 'Lưu lượng: 21k request/phút',
            'backup' => 'Sao lưu gần nhất: ' . $now->copy()->subMinutes(5)->diffForHumans(),
            'revenue' => 'Doanh thu tháng: ' . $this->formatCurrency($monthlyRevenue),
        ];
    }

    private function formatPercentChange(float|int $current, float|int $previous): string
    {
        if ($previous == 0) {
            if ($current == 0) {
                return '0%';
            }
            return '+100%';
        }

        $change = (($current - $previous) / $previous) * 100;
        $prefix = $change >= 0 ? '+' : '';

        return $prefix . number_format($change, 1) . '%';
    }

    private function formatRevenueDelta(float|int $current, float|int $previous): string
    {
        $diff = $current - $previous;
        $prefix = $diff >= 0 ? '+' : '-';

        return $prefix . $this->formatCurrency(abs($diff)) . ' so với T trước';
    }

    private function formatCurrency(float|int|null $value): string
    {
        $amount = $value ?? 0;

        return number_format($amount, 0, ',', '.') . ' ₫';
    }

    private function formatSubscriptionStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Hoạt động',
            'trial' => 'Đang dùng thử',
            'expired' => 'Hết hạn',
            'canceled' => 'Đã hủy',
            default => 'Chưa cấu hình',
        };
    }

    private function formatBadgeFromStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Premium',
            'trial' => 'Trial',
            'expired' => 'Audit',
            default => 'Standard',
        };
    }
}

