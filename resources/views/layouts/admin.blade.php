<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin')</title>
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
    <header class="border-b border-white/5 bg-slate-900/70 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-300">Super Admin Panel</p>
                <h1 class="text-2xl font-semibold text-white">@yield('page_title', 'Quản trị hệ thống')</h1>
            </div>
            <nav class="flex flex-wrap gap-2 text-sm font-semibold text-white/60">
                <a href="{{ route('dashboard') }}" class="rounded-2xl border border-white/10 px-3 py-1.5 hover:text-white {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : '' }}">Dashboard</a>
                <a href="{{ route('admin.clubs.index') }}" class="rounded-2xl border border-white/10 px-3 py-1.5 hover:text-white {{ request()->routeIs('admin.clubs.*') ? 'bg-white/10 text-white' : '' }}">Quản lý CLB</a>
                <a href="{{ route('admin.members.index') }}" class="rounded-2xl border border-white/10 px-3 py-1.5 hover:text-white {{ request()->routeIs('admin.members.*') ? 'bg-white/10 text-white' : '' }}">Quản lý thành viên</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-6 py-8">
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
