<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Club Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-800">Club Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    @if(session('user'))
                        <span class="text-gray-700">Xin chào, {{ session('user')['name'] ?? 'User' }}</span>
                    @endif
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="px-4 py-6 sm:px-0">
            <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 flex items-center justify-center">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Chào mừng đến với Club Management</h2>
                    <p class="text-gray-600">Bạn đã đăng nhập thành công!</p>
                    <div class="mt-6 space-x-4">
                        <a href="/" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                            Về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
