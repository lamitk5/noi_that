@extends('layouts.app')

@section('title', $product->name . ' - Chi Tiết Sản Phẩm')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500 mb-6 space-x-2">
        <a href="{{ route('home') }}" class="hover:text-amber-800">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('products.index', ['category_id' => $product->category_id]) }}" class="hover:text-amber-800">{{ $product->category->name }}</a>
        <span>/</span>
        <span class="text-gray-800 font-medium truncate max-w-xs">{{ $product->name }}</span>
    </nav>

    <div class="bg-white rounded-2xl border shadow-sm p-6 lg:p-8 mb-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <!-- Image Gallery -->
            <div>
                <div class="h-96 w-full rounded-xl overflow-hidden bg-gray-100 mb-4 border">
                    <img id="mainImage" src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                </div>
                @if($product->images->count() > 1)
                    <div class="flex space-x-3 overflow-x-auto pb-2">
                        @foreach($product->images as $img)
                            <button type="button" onclick="document.getElementById('mainImage').src = '{{ Str::startsWith($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path) }}'" class="w-20 h-20 rounded-lg overflow-hidden border-2 hover:border-amber-700 flex-shrink-0">
                                <img src="{{ Str::startsWith($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path) }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div class="flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800">{{ $product->category->name }}</span>
                    <h1 class="text-3xl font-extrabold text-gray-900 mt-2 mb-2 leading-snug">{{ $product->name }}</h1>
                    <p class="text-xs text-gray-400 mb-4">Mã sản phẩm: <span class="font-mono text-gray-600 font-medium">{{ $product->sku }}</span></p>

                    <!-- Price -->
                    <div class="flex items-baseline space-x-4 mb-6">
                        <span class="text-3xl font-extrabold text-amber-900">{{ number_format($product->final_price, 0, ',', '.') }} đ</span>
                        @if($product->is_on_sale)
                            <span class="text-lg text-gray-400 line-through">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                            <span class="text-xs bg-rose-100 text-rose-800 font-bold px-2 py-0.5 rounded">
                                Tiết kiệm {{ number_format($product->price - $product->sale_price, 0, ',', '.') }} đ
                            </span>
                        @endif
                    </div>

                    <!-- Short description -->
                    <p class="text-sm text-gray-600 mb-6 leading-relaxed">{{ $product->short_description }}</p>

                    <!-- Technical Specifications -->
                    <div class="bg-amber-50/50 rounded-xl p-4 border border-amber-100 space-y-2.5 text-sm mb-6">
                        <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-2">Thông Số Kỹ Thuật Nội Thất</h3>
                        @if($product->material)
                            <div class="flex justify-between"><span class="text-gray-500">Chất liệu:</span> <span class="font-semibold text-gray-800">{{ $product->material }}</span></div>
                        @endif
                        @if($product->dimensions)
                            <div class="flex justify-between"><span class="text-gray-500">Kích thước:</span> <span class="font-semibold text-gray-800">{{ $product->dimensions }}</span></div>
                        @endif
                        @if($product->color)
                            <div class="flex justify-between"><span class="text-gray-500">Màu sắc:</span> <span class="font-semibold text-gray-800">{{ $product->color }}</span></div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tình trạng kho:</span>
                            @if($product->is_in_stock)
                                <span class="text-emerald-700 font-semibold"><i class="fa-solid fa-check-circle mr-1"></i>Còn hàng ({{ $product->stock_quantity }} sản phẩm)</span>
                            @else
                                <span class="text-rose-600 font-semibold"><i class="fa-solid fa-circle-xmark mr-1"></i>Hết hàng</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Button Form -->
                <form action="{{ route('cart.add', $product) }}" method="POST" class="pt-4 border-t">
                    @csrf
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center border rounded-lg overflow-hidden w-32">
                            <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" class="w-full text-center py-3 text-sm font-semibold outline-none" {{ !$product->is_in_stock ? 'disabled' : '' }}>
                        </div>
                        <button type="submit" class="flex-grow bg-amber-800 hover:bg-amber-900 text-white font-bold py-3.5 px-6 rounded-lg transition duration-150 flex items-center justify-center space-x-2 shadow-md {{ !$product->is_in_stock ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$product->is_in_stock ? 'disabled' : '' }}>
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>{{ $product->is_in_stock ? 'Thêm Vào Giỏ Hàng' : 'Hết Hàng' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Long Description -->
        <div class="mt-12 pt-8 border-t">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Chi Tiết & Hướng Dẫn Bảo Quản</h2>
            <div class="text-gray-700 leading-relaxed text-sm space-y-4">
                <p>{{ $product->description }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
