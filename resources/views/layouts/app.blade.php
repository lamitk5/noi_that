<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Nội Thất Sang Trọng - Furniture Shop')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans flex flex-col min-h-screen">
    <!-- Navigation Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2 text-2xl font-bold text-amber-800">
                        <i class="fa-solid fa-couch"></i>
                        <span>LUXURY HOME</span>
                    </a>
                    <nav class="hidden md:flex space-x-6">
                        <a href="{{ route('home') }}" class="text-gray-600 hover:text-amber-800 font-medium">Trang chủ</a>
                        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-amber-800 font-medium">Sản phẩm</a>
                        <a href="{{ route('orders.track') }}" class="text-gray-600 hover:text-amber-800 font-medium">Tra cứu đơn</a>
                    </nav>
                </div>

                <div class="flex items-center space-x-5">
                    <a href="{{ route('cart.index') }}" class="relative text-gray-700 hover:text-amber-800 p-2">
                        <i class="fa-solid fa-cart-shopping text-xl"></i>
                        @php
                            $cartCount = array_sum(array_column(session('furniture_cart', []), 'quantity'));
                        @endphp
                        @if($cartCount > 0)
                            <span class="absolute top-0 right-0 bg-amber-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    @auth
                        <div class="flex items-center space-x-3">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="bg-amber-800 text-white px-3 py-1.5 rounded-md text-sm font-semibold hover:bg-amber-900">
                                    <i class="fa-solid fa-gauge mr-1"></i> Admin Panel
                                </a>
                            @endif
                            <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-red-600 hover:underline">Đăng xuất</button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-amber-800">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="bg-amber-800 text-white px-3.5 py-1.5 rounded-md text-sm font-medium hover:bg-amber-900">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
        @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded shadow-sm mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-4 rounded shadow-sm mb-4">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-10 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p class="text-lg font-bold text-white mb-2">Website Thương Mại Điện Tử Bán Đồ Nội Thất</p>
            <p class="text-gray-400 mb-4">Chuyên cung cấp bàn ghế, sofa, giường tủ cao cấp bằng gỗ tự nhiên và da bò thật.</p>
            <p class="text-gray-500">© 2026 LUXURY HOME Furniture. Giữ toàn quyền bản quyền.</p>
        </div>
    </footer>
</body>
</html>
