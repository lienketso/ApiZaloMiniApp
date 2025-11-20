<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 min-h-screen text-white">
    @php
        $user = session('user');
        $metrics = $metrics ?? [];
        $clubs = $clubs ?? [];
        $activities = $activities ?? [];
        $menu = $menu ?? [];
        $systemStatus = $systemStatus ?? [];
    @endphp

    <div class="flex h-screen">
        <aside class="hidden w-72 flex-col border-r border-white/5 bg-slate-900/60 px-6 py-8 lg:flex">
            <div class="flex items-center gap-3 text-lg font-semibold">
                <span class="inline-flex size-11 items-center justify-center rounded-2xl bg-white/5 text-base ring-1 ring-white/20">CM</span>
                Super Admin Panel
            </div>
            <div class="mt-10 flex-1 space-y-1">
                @foreach($menu as $item)
                    <a href="{{ $item['route'] ?? '#' }}"
                       class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium transition
                       {{ $item['active'] ?? false ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5' }}">
                        <span>{{ $item['label'] }}</span>
                        @if($item['active'] ?? false)
                            <span class="inline-flex items-center justify-center rounded-full bg-emerald-400/20 px-2 py-0.5 text-xs text-emerald-300">Live</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="rounded-2xl border border-white/5 bg-white/5 p-4 text-sm text-white/70">
                <p class="font-medium text-white">Phiên đăng nhập</p>
                <p class="mt-2">Xin chào, {{ $user['name'] ?? 'Super Admin' }}</p>
                <p class="text-xs text-white/50">Vai trò: {{ $user['role'] ?? 'Super Admin' }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/20">
                        Đăng xuất an toàn
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 overflow-y-auto">
            <header class="border-b border-white/5 bg-slate-900/40 px-6 py-6 backdrop-blur">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Bảng điều khiển tổng quan</p>
                        <h1 class="mt-2 text-3xl font-semibold">Xin chào, {{ $user['name'] ?? 'Super Admin' }}</h1>
                        <p class="text-sm text-white/60">Theo dõi toàn bộ hoạt động của hệ thống câu lạc bộ trong một màn hình.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium hover:bg-white/10">
                            Tải dữ liệu mới
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/30">
                            + Tạo sự kiện
                        </button>
                    </div>
                </div>
                @if(session('success'))
                    <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mt-4 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        {{ session('error') }}
                    </div>
                @endif
            </header>

            <main class="px-6 py-8 space-y-8">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($metrics as $metric)
                        <div class="rounded-3xl border border-white/5 bg-gradient-to-br {{ $metric['color'] }} p-5 text-slate-900">
                            <p class="text-sm font-medium text-white/70 uppercase tracking-wide">{{ $metric['label'] }}</p>
                            <p class="mt-4 text-3xl font-bold text-white">{{ $metric['value'] }}</p>
                            <p class="mt-1 text-sm text-white/80">{{ $metric['trend'] }}</p>
                        </div>
                    @endforeach
                </section>

                <section class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="rounded-3xl border border-white/5 bg-white/5 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-white/60 uppercase tracking-widest">Hiệu suất hệ thống</p>
                                    <h2 class="mt-1 text-2xl font-semibold text-white">Dòng tiền & hoạt động</h2>
                                </div>
                                <button class="text-sm text-white/70 hover:text-white">Xem chi tiết</button>
                            </div>
                            <div class="mt-6 grid gap-6 md:grid-cols-2">
                                <div>
                                    <p class="text-sm text-white/60">Dòng tiền 30 ngày</p>
                                    <p class="mt-2 text-3xl font-semibold">+182 triệu</p>
                                    <p class="text-xs text-emerald-300">+12,7% so với cùng kỳ</p>
                                </div>
                                <div>
                                    <p class="text-sm text-white/60">Công việc ưu tiên</p>
                                    <ul class="mt-3 space-y-3 text-sm text-white/80">
                                        <li class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-2">
                                            Phê duyệt CLB mới
                                            <span class="text-white/50">04 pending</span>
                                        </li>
                                        <li class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-2">
                                            Đánh giá báo cáo quý
                                            <span class="text-amber-300">Due 2 ngày</span>
                                        </li>
                                        <li class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-2">
                                            Kiểm tra bảo mật
                                            <span class="text-emerald-300">Hoàn tất 78%</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/5 bg-white/5 p-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-semibold">Hiệu suất từng CLB</h2>
                                <button class="text-sm text-blue-300 hover:text-blue-200">Xem tất cả</button>
                            </div>
                            <div class="mt-6 space-y-4">
                                @foreach($clubs as $club)
                                    <div class="rounded-2xl border border-white/5 bg-white/5 px-4 py-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-base font-semibold">{{ $club['name'] ?? 'CLB chưa đặt tên' }}</p>
                                                <p class="text-sm text-white/60">{{ $club['members'] ?? 0 }} thành viên • {{ $club['status'] ?? 'Đang cập nhật' }}</p>
                                            </div>
                                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-white/70">{{ $club['badge'] ?? 'Standard' }}</span>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between text-sm text-white/70">
                                            <span>Doanh thu: {{ $club['revenue'] ?? '0 ₫' }}</span>
                                            <span class="{{ $club['trend_class'] ?? 'text-white/60' }}">{{ $club['trend'] ?? '0%' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-white/5 bg-white/5 p-6">
                            <h2 class="text-xl font-semibold">Hoạt động gần đây</h2>
                            <div class="mt-5 space-y-4">
                                @foreach($activities as $activity)
                                    <div class="rounded-2xl border border-white/5 bg-white/5 px-4 py-3">
                                        <p class="text-xs uppercase tracking-widest text-white/50">{{ $activity['time'] }}</p>
                                        <p class="mt-1 text-base font-semibold">{{ $activity['title'] }}</p>
                                        <p class="text-sm text-white/70">{{ $activity['detail'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/5 bg-gradient-to-br from-indigo-500 to-blue-500 p-6 text-white">
                            <p class="text-sm font-semibold uppercase tracking-widest text-white/80">Tình trạng hệ thống</p>
                            <h2 class="mt-2 text-2xl font-semibold">Hệ thống ổn định</h2>
                            <p class="mt-3 text-sm text-white/90">100% dịch vụ nền đang hoạt động bình thường. Không có cảnh báo mới.</p>
                            @if(!empty($systemStatus))
                                <div class="mt-5 space-y-2 text-sm text-white/80">
                                    @foreach($systemStatus as $line)
                                        <p>• {{ $line }}</p>
                                    @endforeach
                                </div>
                            @endif
                            <button class="mt-6 w-full rounded-2xl bg-white/15 py-2 text-sm font-semibold backdrop-blur hover:bg-white/25">Xem trung tâm giám sát</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
