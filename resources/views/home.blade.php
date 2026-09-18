@extends('layouts.app')

@section('title', 'Trang Chủ - Nội Thất Sang Trọng')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero Banner -->
    <div class="relative bg-amber-950 text-white rounded-2xl overflow-hidden shadow-xl mb-12">
        <div class="absolute inset-0 opacity-40 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1600&q=80');"></div>
        <div class="relative max-w-2xl px-8 py-20 lg:py-28">
            <span class="inline-block px-3 py-1 bg-amber-600 text-xs font-semibold uppercase tracking-wider rounded-full mb-4">Bộ Sưu Tập Mới 2026</span>
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight mb-4">Kiến Tạo Không Gian Sống Đẳng Cấp</h1>
            <p class="text-lg text-gray-200 mb-8">Trải nghiệm những kiệt tác nội thất từ gỗ sồi tự nhiên, da bò Ý nguyên tấm và mặt đá ceramic chống trầy.</p>
            <a href="{{ route('products.index') }}" class="inline-block bg-amber-600 hover:bg-amber-700 text-white font-semibold px-8 py-3.5 rounded-lg shadow-lg transition duration-200">
                Khám Phá Ngay
            </a>
        </div>
    </div>

    <!-- Featured Categories -->
    <div class="mb-14">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Danh Mục Nổi Bật</h2>
                <p class="text-sm text-gray-500">Lựa chọn không gian nội thất theo phong cách của bạn</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-amber-700 hover:text-amber-800 font-semibold text-sm">Xem tất cả →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach($featuredCategories as $cat)
                <a href="{{ route('products.index', ['category_id' => $cat->id]) }}" class="group block bg-white rounded-xl shadow-sm border hover:shadow-md transition p-4 text-center">
                    <div class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden bg-amber-100 flex items-center justify-center">
                        <img src="{{ $cat->image ?? 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=300&q=80' }}" alt="{{ $cat->name }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                    </div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-amber-800 text-sm mb-1">{{ $cat->name }}</h3>
                    <p class="text-xs text-gray-400">{{ $cat->products_count ?? 0 }} sản phẩm</p>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Featured Products -->
    <div class="mb-14">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Sản Phẩm Tiêu Biểu</h2>
                <p class="text-sm text-gray-500">Những thiết kế được yêu thích nhất cho ngôi nhà hiện đại</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-amber-700 hover:text-amber-800 font-semibold text-sm">Xem danh mục →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-lg transition duration-200 flex flex-col">
                    <a href="{{ route('products.show', $product->slug) }}" class="relative block h-56 bg-gray-100 overflow-hidden">
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                        @if($product->is_on_sale)
                            <span class="absolute top-2 left-2 bg-rose-600 text-white text-xs font-bold px-2 py-1 rounded">
                                Giảm giá
                            </span>
                        @endif
                    </a>
                    <div class="p-4 flex-grow flex flex-col justify-between">
                        <div>
                            <span class="text-xs text-amber-700 font-medium">{{ $product->category->name ?? 'Nội thất' }}</span>
                            <h3 class="font-bold text-gray-800 text-base mt-1 line-clamp-2 hover:text-amber-800">
                                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            @if($product->material)
                                <p class="text-xs text-gray-500 mt-1"><i class="fa-solid fa-gem mr-1"></i>{{ $product->material }}</p>
                            @endif
                        </div>
                        <div class="mt-4 pt-3 border-t flex justify-between items-center">
                            <div>
                                <span class="text-lg font-extrabold text-amber-900">{{ number_format($product->final_price, 0, ',', '.') }} đ</span>
                                @if($product->is_on_sale)
                                    <span class="block text-xs text-gray-400 line-through">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                                @endif
                            </div>
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2.5 bg-amber-800 hover:bg-amber-900 text-white rounded-lg transition" title="Thêm vào giỏ">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
