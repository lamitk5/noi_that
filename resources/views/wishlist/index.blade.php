@extends('layouts.app')

@section('title', 'Danh Sách Yêu Thích')

@section('content')
<div class="page-shell py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-heading">Danh Sách Yêu Thích</h1>
        <span class="text-sm text-muted">{{ $products->count() }} sản phẩm</span>
    </div>

    @guest
        <div class="bg-surface rounded-2xl p-12 text-center border border-ui-border shadow-sm">
            <i class="fa-solid fa-heart text-5xl text-rose-300 dark:text-rose-500/60 mb-4"></i>
            <p class="text-muted mb-6">Đăng nhập để xem và lưu các sản phẩm bạn yêu thích.</p>
            <a href="{{ route('login') }}" class="bg-primary hover:opacity-90 text-primary-foreground font-semibold px-6 py-2.5 rounded-xl transition inline-block shadow-sm">
                Đăng Nhập
            </a>
        </div>
    @else
        @if($products->isEmpty())
            <div class="bg-surface rounded-2xl p-12 text-center border border-ui-border shadow-sm">
                <i class="fa-solid fa-heart-crack text-5xl text-muted mb-4"></i>
                <p class="text-muted mb-6">Chưa có sản phẩm nào trong danh sách yêu thích.</p>
                <a href="{{ route('products.index') }}" class="bg-primary hover:opacity-90 text-primary-foreground font-semibold px-6 py-2.5 rounded-xl transition inline-block shadow-sm">
                    Khám Phá Sản Phẩm
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <article class="bg-surface rounded-2xl border border-ui-border shadow-sm overflow-hidden group relative">
                        <div class="relative h-52 overflow-hidden bg-surface-alt">
                            <a href="{{ route('products.show', $product->slug) }}" class="block h-full">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </a>
                            <form action="{{ route('wishlist.remove', $product) }}" method="POST" class="absolute top-3 right-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 rounded-full bg-surface/90 text-rose-600 shadow hover:bg-rose-600 hover:text-white transition flex items-center justify-center border border-ui-border cursor-pointer" title="Bỏ khỏi yêu thích">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            </form>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-accent font-semibold uppercase tracking-wider">{{ $product->category->name ?? 'Nội thất' }}</p>
                            <h3 class="font-bold text-heading mt-1 line-clamp-2 hover:text-primary transition">
                                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <div class="mt-3 pt-3 border-t border-ui-border flex items-center justify-between">
                                <span class="text-lg font-extrabold text-heading">{{ number_format($product->starting_price, 0, ',', '.') }} đ</span>
                                <a href="{{ route('products.show', $product->slug) }}" class="p-2 bg-primary hover:opacity-90 text-primary-foreground rounded-lg transition cursor-pointer" title="Chọn size & màu">
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
