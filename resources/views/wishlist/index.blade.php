@extends('layouts.app')

@section('title', 'Danh Sách Yêu Thích')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Danh Sách Yêu Thích</h1>
        <span class="text-sm text-gray-500">{{ $products->count() }} sản phẩm</span>
    </div>

    @guest
        <div class="bg-white rounded-xl p-12 text-center border shadow-sm">
            <i class="fa-solid fa-heart text-5xl text-rose-300 mb-4"></i>
            <p class="text-gray-600 mb-6">Đăng nhập để xem và lưu các sản phẩm bạn yêu thích.</p>
            <a href="{{ route('login') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold px-6 py-2.5 rounded-lg transition inline-block">
                Đăng Nhập
            </a>
        </div>
    @else
        @if($products->isEmpty())
            <div class="bg-white rounded-xl p-12 text-center border shadow-sm">
                <i class="fa-solid fa-heart-crack text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-6">Chưa có sản phẩm nào trong danh sách yêu thích.</p>
                <a href="{{ route('products.index') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold px-6 py-2.5 rounded-lg transition inline-block">
                    Khám Phá Sản Phẩm
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <article class="bg-white rounded-xl border shadow-sm overflow-hidden group relative">
                        <div class="relative h-52 overflow-hidden bg-gray-100">
                            <a href="{{ route('products.show', $product->slug) }}" class="block h-full">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </a>
                            <form action="{{ route('wishlist.remove', $product) }}" method="POST" class="absolute top-3 right-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 rounded-full bg-white/95 text-rose-600 shadow hover:bg-rose-600 hover:text-white transition flex items-center justify-center" title="Bỏ khỏi yêu thích">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-amber-700 font-semibold uppercase tracking-wider">{{ $product->category->name ?? 'Nội thất' }}</p>
                            <h3 class="font-bold text-gray-900 mt-1 line-clamp-2 hover:text-amber-800">
                                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <div class="mt-3 pt-3 border-t flex items-center justify-between">
                                <span class="text-lg font-extrabold text-amber-900">{{ number_format($product->starting_price, 0, ',', '.') }} đ</span>
                                <a href="{{ route('products.show', $product->slug) }}" class="p-2 bg-amber-800 hover:bg-amber-900 text-white rounded-lg transition" title="Chọn size & màu">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endguest
</div>
@endsection
