@extends('layouts.admin')

@section('title', 'Quản lý thành viên')
@section('page_title', 'Quản lý thành viên toàn hệ thống')

@section('content')
<div class="space-y-8">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-white/60">Tổng thành viên</p>
            <p class="mt-3 text-3xl font-semibold">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-white/60">Super Admin</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-300">{{ number_format($stats['super_admin']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-white/60">Admin CLB</p>
            <p class="mt-3 text-3xl font-semibold text-blue-300">{{ number_format($stats['admin']) }}</p>
        </div>
        <div class="rounded-3xl border border-white/5 bg-white/5 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-white/60">Member</p>
            <p class="mt-3 text-3xl font-semibold text-amber-300">{{ number_format($stats['member']) }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-3xl border border-white/5 bg-white/5 p-6 md:flex-row md:items-center md:justify-between">
        <form method="GET" class="flex flex-1 flex-wrap gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tên, email, số điện thoại" class="flex-1 rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm" />
            <select name="role" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-white/80">
                <option value="">Tất cả vai trò</option>
                <option value="super_admin" @selected($role==='super_admin')>Super Admin</option>
                <option value="admin" @selected($role==='admin')>Admin CLB</option>
                <option value="member" @selected($role==='member')>Member</option>
            </select>
            <select name="club_id" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-white/80 min-w-[200px]">
                <option value="">Tất cả CLB</option>
                @foreach($clubs as $club)
                    <option value="{{ $club->id }}" @selected($clubId==$club->id)>{{ $club->name }}</option>
                @endforeach
            </select>
            <button class="rounded-2xl bg-blue-500 px-4 py-2 text-sm font-semibold">Lọc</button>
        </form>
        <a href="{{ route('admin.members.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-2 text-sm font-semibold shadow-lg shadow-emerald-500/30">+ Thêm thành viên</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-white/5">
        <table class="min-w-full divide-y divide-white/10">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-white/60">
                    <th class="px-4 py-3">Thành viên</th>
                    <th class="px-4 py-3">Liên hệ</th>
                    <th class="px-4 py-3">Vai trò</th>
                    <th class="px-4 py-3">CLB </th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Ngày tạo</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm text-white/80">
                @forelse($members as $member)
                    <tr>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $member->avatar ?? 'https://ui-avatars.com/api/?background=0f172a&color=fff&name=' . urlencode($member->name) }}" alt="{{ $member->name }}" class="h-12 w-12 rounded-full object-cover border border-white/10">
                                <div>
                                    <p class="font-semibold text-white">{{ $member->name }}</p>
                                    <p class="text-xs text-white/50">ID: {{ $member->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p>{{ $member->phone ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $roleColors = [
                                    'super_admin' => 'bg-purple-500/20 text-purple-300',
                                    'admin' => 'bg-blue-500/20 text-blue-300',
                                    'member' => 'bg-slate-500/20 text-slate-300',
                                ];
                                $roleLabel = $member->role ?? 'member';
                            @endphp
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $roleColors[$roleLabel] ?? 'bg-white/10 text-white/70' }}">{{ ucfirst(str_replace('_', ' ', $roleLabel)) }}</span>
                        </td>
                        <td class="px-4 py-4 text-xs text-white/70">
                            @if($member->clubs->isEmpty())
                                <span class="text-white/40">Chưa tham gia</span>
                            @else
                                <div class="space-y-1">
                                    @foreach($member->clubs as $club)
                                        <p class="flex items-center justify-between rounded-xl bg-white/5 px-3 py-1">{{ $club->name }} <span class="text-white/50">{{ $club->pivot->role }}</span></p>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $isOnline = $member->last_seen_at && $member->last_seen_at->gt(now()->subMinutes(5));
                            @endphp
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $isOnline ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-300 animate-pulse' : 'bg-slate-400' }}"></span>
                                {{ $isOnline ? 'Online' : 'Offline' }}
                            </span>
                            <p class="mt-2 text-xs text-white/50">
                                Last login: {{ $member->last_login_at ? $member->last_login_at->diffForHumans() : 'Chưa đăng nhập' }}
                            </p>
                        </td>
                        <td class="px-4 py-4 text-xs text-white/50">{{ $member->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.members.edit', $member) }}" class="rounded-xl border border-white/10 px-3 py-1 text-xs font-semibold text-white hover:bg-white/10">Sửa</a>
                                <form method="POST" action="{{ route('admin.members.destroy', $member) }}" onsubmit="return confirm('Xoá thành viên này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-500/30 px-3 py-1 text-xs font-semibold text-red-300 hover:bg-red-500/10">Xoá</button>
                                </form>
                                <form method="POST" action="{{ route('admin.members.reset-password', $member) }}" onsubmit="return confirm('Reset mật khẩu?');">
                                    @csrf
                                    <button class="rounded-xl border border-amber-500/30 px-3 py-1 text-xs font-semibold text-amber-300 hover:bg-amber-500/10">Reset mật khẩu</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-white/60">Chưa có thành viên nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $members->links() }}
    </div>
</div>
@endsection
