<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Club Management')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 50;
        }
        
        .main-content {
            padding-bottom: 80px; /* Để tránh bị navbar che nội dung */
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item.active {
            color: #3b82f6;
        }
        
        .nav-item.active .nav-icon {
            color: #3b82f6;
        }
        
        .nav-item:not(.active) {
            color: #6b7280;
        }
        
        .nav-item:not(.active) .nav-icon {
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-800">@yield('header_title', 'Club Management')</h1>
                </div>
                <div class="flex items-center space-x-4">
                    @if(session('user'))
                        <span class="text-gray-700">Xin chào, {{ session('user')['name'] ?? 'User' }}</span>
                    @endif
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm">
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mx-4 mt-4 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mx-4 mt-4 mb-6">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="flex justify-around items-center h-16 px-2">
            <a href="{{ route('club.index') }}" class="nav-item flex flex-col items-center justify-center flex-1 py-2 {{ request()->routeIs('club.index') ? 'active' : '' }}">
                <i class="fas fa-home nav-icon text-lg mb-1"></i>
                <span class="text-xs">Trang chủ</span>
            </a>
            
            <a href="{{ route('club.members') }}" class="nav-item flex flex-col items-center justify-center flex-1 py-2 {{ request()->routeIs('club.members') ? 'active' : '' }}">
                <i class="fas fa-users nav-icon text-lg mb-1"></i>
                <span class="text-xs">Thành viên</span>
            </a>
            
            <a href="{{ route('club.attendance') }}" class="nav-item flex flex-col items-center justify-center flex-1 py-2 {{ request()->routeIs('club.attendance') ? 'active' : '' }}">
                <i class="fas fa-calendar-check nav-icon text-lg mb-1"></i>
                <span class="text-xs">Điểm danh</span>
            </a>
            
            <a href="{{ route('club.matches') }}" class="nav-item flex flex-col items-center justify-center flex-1 py-2 {{ request()->routeIs('club.matches') ? 'active' : '' }}">
                <i class="fas fa-trophy nav-icon text-lg mb-1"></i>
                <span class="text-xs">Trận đấu</span>
            </a>
            
            <a href="{{ route('club.profile') }}" class="nav-item flex flex-col items-center justify-center flex-1 py-2 {{ request()->routeIs('club.profile') ? 'active' : '' }}">
                <i class="fas fa-user nav-icon text-lg mb-1"></i>
                <span class="text-xs">Cá nhân</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>
