<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản Trị Nội Thất - Admin Panel')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .text-2xs { font-size: 0.625rem; line-height: 0.875rem; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-800 flex items-center space-x-3">
                <i class="fa-solid fa-couch text-amber-500 text-2xl"></i>
                <span class="text-lg font-bold">FURNITURE ADMIN</span>
            </div>
            <nav class="p-4 space-y-1.5 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-amber-800 text-white font-bold' : 'text-gray-300' }}">
                    <i class="fa-solid fa-chart-line w-5"></i>
                    <span>Tổng quan (Dashboard)</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.categories.*') ? 'bg-amber-800 text-white font-bold' : 'text-gray-300' }}">
                    <i class="fa-solid fa-tags w-5"></i>
                    <span>Quản lý Danh mục</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.products.*') ? 'bg-amber-800 text-white font-bold' : 'text-gray-300' }}">
                    <i class="fa-solid fa-couch w-5"></i>
                    <span>Quản lý Sản phẩm</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.orders.*') ? 'bg-amber-800 text-white font-bold' : 'text-gray-300' }}">
                    <i class="fa-solid fa-receipt w-5"></i>
                    <span>Quản lý Đơn hàng</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-gray-800">
            <a href="{{ route('home') }}" target="_blank" class="block text-xs text-amber-400 hover:underline mb-2">
                <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Xem cửa hàng ngoài
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full text-left text-xs text-rose-400 hover:text-rose-300 py-1">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col min-w-0">
        <!-- Top Bar -->
        <header class="bg-white border-b h-16 flex items-center justify-between px-8">
            <h2 class="font-bold text-gray-800 text-lg">@yield('page_title', 'Bảng Điều Khiển')</h2>
            <div class="flex items-center space-x-3 text-sm">
                <span class="font-medium text-gray-700">{{ auth()->user()->name }}</span>
                <span class="bg-amber-100 text-amber-900 text-xs px-2.5 py-0.5 rounded-full font-bold">Admin</span>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-8 mt-4">
            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-3 rounded text-sm mb-3">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-3 rounded text-sm mb-3">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Page body -->
        <main class="p-8 flex-grow">
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
