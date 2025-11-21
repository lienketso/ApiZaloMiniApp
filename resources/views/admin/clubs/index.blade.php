@extends('layouts.admin')

@section('title', 'Quản lý CLB')
@section('page_title', 'Danh sách câu lạc bộ')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="space-y-8">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-widest text-white/60">Tổng CLB</p>
            <p class="mt-2 text-3xl font-semibold">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-widest text-white/60">Đang hoạt động</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-widest text-white/60">Đang trial</p>
            <p class="mt-2 text-3xl font-semibold text-amber-300">{{ number_format($stats['trial']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-widest text-white/60">Chưa setup</p>
            <p class="mt-2 text-3xl font-semibold text-red-300">{{ number_format($stats['pending_setup']) }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-3xl border border-white/5 bg-white/5 p-6 md:flex-row md:items-center md:justify-between">
        <form method="GET" class="flex flex-1 flex-wrap gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên, môn, email..." class="flex-1 rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-400" />
            <select name="status" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-white/80">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected($status==='active')>Active</option>
                <option value="trial" @selected($status==='trial')>Trial</option>
                <option value="expired" @selected($status==='expired')>Expired</option>
                <option value="canceled" @selected($status==='canceled')>Canceled</option>
            </select>
            <button class="rounded-2xl bg-blue-500 px-4 py-2 text-sm font-semibold">Lọc</button>
        </form>
        <a href="{{ url('/admin/clubs/create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-2 text-sm font-semibold shadow-lg shadow-emerald-500/30">+ Tạo CLB</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/5">
        <table class="min-w-full divide-y divide-white/10">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-widest text-white/60">
                    <th class="px-4 py-3">Tên CLB</th>
                    <th class="px-4 py-3">Môn</th>
                    <th class="px-4 py-3">Liên hệ</th>
                    <th class="px-4 py-3">Thành viên</th>
                    <th class="px-4 py-3">Tổng quỹ</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Cập nhật</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm text-white/80">
                @forelse($clubs as $club)
                    <tr>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $logoUrl = $club->logo
                                        ? (Str::startsWith($club->logo, ['http://', 'https://']) ? $club->logo : url($club->logo))
                                        : 'https://ui-avatars.com/api/?background=1e293b&color=fff&name=' . urlencode($club->name);
                                @endphp
                                <img src="{{ $logoUrl }}" alt="{{ $club->name }}" class="h-12 w-12 rounded-2xl object-cover border border-white/10">
                                <div>
                                    <p class="font-semibold text-white">{{ $club->name }}</p>
                                    <p class="text-xs text-white/50">{{ $club->address }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">{{ $club->sport ?? '—' }}</td>
                        <td class="px-4 py-4">
                            <p>{{ $club->phone ?? '—' }}</p>
                            <p class="text-xs text-white/50">{{ $club->email ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">
                                {{ $club->active_members_count ?? 0 }} 
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $totalFund = $club->total_fund ?? 0;
                                $fundColor = $totalFund >= 0 ? 'text-emerald-300' : 'text-red-300';
                            @endphp
                            <p class="font-semibold {{ $fundColor }}">
                                {{ number_format($totalFund, 0, ',', '.') }} ₫
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $statusColors = [
                                    'active' => 'bg-emerald-500/20 text-emerald-300',
                                    'trial' => 'bg-amber-500/20 text-amber-300',
                                    'expired' => 'bg-red-500/20 text-red-300',
                                    'canceled' => 'bg-slate-500/20 text-slate-300',
                                ];
                                $statusLabel = $club->subscription_status ?? 'Chưa xác định';
                                $badgeClass = $statusColors[$club->subscription_status] ?? 'bg-white/10 text-white/70';
                            @endphp
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ ucfirst($statusLabel) }}</span>
                        </td>
                        <td class="px-4 py-4">{{ optional($club->plan)->name ?? '—' }}</td>
                        <td class="px-4 py-4 text-xs text-white/50">{{ $club->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ url('/admin/clubs/' . $club->id . '/edit') }}" class="rounded-xl border border-white/10 px-3 py-1 text-xs font-semibold text-white hover:bg-white/10">Sửa</a>
                                <form method="POST" action="{{ url('/admin/clubs/' . $club->id) }}" onsubmit="return confirm('Xác nhận xoá CLB này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-500/30 px-3 py-1 text-xs font-semibold text-red-300 hover:bg-red-500/10">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-white/60">Chưa có câu lạc bộ nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $clubs->links() }}
    </div>
</div>
@endsection
