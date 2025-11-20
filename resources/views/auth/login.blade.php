<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin | Đăng nhập hệ thống</title>
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
<body class="min-h-screen bg-slate-950">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden lg:flex">
            <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1080&q=80" alt="Club management" class="object-cover w-full" loading="lazy">
            <div class="absolute inset-0 bg-slate-900/80"></div>
            <div class="absolute inset-0 flex flex-col justify-between p-10 text-white">
                <div class="flex items-center gap-3 text-xl font-semibold">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-blue-500/20 ring-1 ring-white/30">CM</span>
                    Club Management Portal
                </div>
                <div>
                    <p class="text-3xl font-semibold leading-tight">Quản trị toàn diện mạng lưới câu lạc bộ</p>
                    <ul class="mt-8 space-y-4 text-base text-white/80">
                        <li class="flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-white/10 border border-white/20 text-sm">1</span>Tổng quan hoạt động tức thời</li>
                        <li class="flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-white/10 border border-white/20 text-sm">2</span>Giám sát thành viên và chỉ số tài chính</li>
                        <li class="flex items-center gap-3"><span class="inline-flex size-7 items-center justify-center rounded-full bg-white/10 border border-white/20 text-sm">3</span>Phân quyền bảo mật dành cho Super Admin</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex flex-col justify-center px-6 py-12 lg:px-16">
            <div class="mx-auto w-full max-w-md space-y-10">
                <div class="space-y-3 text-center lg:text-left">
                    <p class="text-sm font-semibold uppercase tracking-wider text-blue-500">Super Admin Portal</p>
                    <h1 class="text-3xl font-semibold text-white lg:text-slate-900">Đăng nhập hệ thống</h1>
                    <p class="text-base text-slate-300 lg:text-slate-500">Nhập thông tin xác thực do bộ phận vận hành cung cấp để truy cập bảng điều khiển quản trị.</p>
                </div>

                @if(session('success'))
                    <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    <div class="space-y-2">
                        <label for="phone" class="block text-sm font-medium text-white lg:text-slate-700">Số điện thoại</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h2l2 5-2 5H3m5-10h13M8 15h13M8 10h13m-3 5v5m-8-5v5"/>
                                </svg>
                            </span>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                required
                                class="block w-full rounded-2xl border border-white/10 bg-white/10 px-11 py-3 text-white placeholder:text-white/40 backdrop-blur-lg focus:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-slate-950 lg:bg-white lg:text-slate-900 lg:placeholder:text-slate-400 lg:border-slate-200"
                                placeholder="Nhập số điện thoại đăng ký"
                            >
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-white lg:text-slate-700">Mật khẩu</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6a4.5 4.5 0 10-9 0v4.5m-2.25 3h13.5M6.75 13.5v6.75m10.5-6.75v6.75"/>
                                </svg>
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                class="block w-full rounded-2xl border border-white/10 bg-white/10 px-11 py-3 text-white placeholder:text-white/40 backdrop-blur-lg focus:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-slate-950 lg:bg-white lg:text-slate-900 lg:placeholder:text-slate-400 lg:border-slate-200"
                                placeholder="••••••••"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm text-slate-400 lg:text-slate-500">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="rounded border-white/30 bg-white/10 text-blue-500 focus:ring-blue-500 lg:border-slate-300 lg:bg-white">
                            Ghi nhớ đăng nhập
                        </label>
                        <a href="#" class="font-medium text-blue-400 hover:text-blue-300 lg:text-blue-600">Quên mật khẩu?</a>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-500 px-4 py-3 text-base font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:shadow-blue-500/60 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-slate-950"
                    >
                        Truy cập bảng điều khiển
                    </button>
                </form>

                <div class="space-y-2 text-center text-sm text-slate-400 lg:text-slate-500">
                    <p>Không thể đăng nhập? Liên hệ bộ phận vận hành để được cấp/trợ giúp tài khoản Super Admin.</p>
                    <div class="flex items-center justify-center gap-2 text-indigo-300 lg:text-indigo-600">
                        <span class="h-px w-10 bg-current/30"></span>
                        <span>Hotline hỗ trợ 24/7</span>
                        <span class="h-px w-10 bg-current/30"></span>
                    </div>
                    <a href="tel:0123456789" class="text-lg font-semibold text-white lg:text-slate-900">0123 456 789</a>
                </div>

                <div class="text-center text-xs text-slate-500">
                    &copy; {{ date('Y') }} Club Management Platform. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
