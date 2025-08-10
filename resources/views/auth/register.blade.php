<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Club Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">Đăng ký</h1>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form method="POST" action="/register" class="space-y-4">
            @csrf
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" id="password" name="password" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <button type="submit" 
                    class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Đăng ký
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="/login" class="text-blue-600 hover:text-blue-800 text-sm">Đã có tài khoản? Đăng nhập</a>
        </div>
        
        <div class="mt-2 text-center">
            <a href="/" class="text-blue-600 hover:text-blue-800 text-sm">Quay về trang chủ</a>
        </div>
    </div>
</body>
</html>
