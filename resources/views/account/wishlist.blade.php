@extends('layouts.app')

@section('title', 'Sản phẩm yêu thích - Mộc An')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-muted mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}" class="hover:text-primary transition">Tài khoản</a>
        <span>/</span>
        <span class="text-heading font-semibold">Danh sách yêu thích</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Sidebar Navigation -->
        <div class="md:col-span-1">
            <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-1.5 text-xs font-semibold">
                <a href="{{ route('account.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Hồ sơ cá nhân</span>
                </a>
                <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Lịch sử đơn hàng</span>
                </a>
                <a href="{{ route('account.wishlist') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-primary text-primary-foreground transition shadow-xs">
                    <span>Sản phẩm yêu thích</span>
                </a>
                <a href="{{ route('account.loyalty') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Điểm thưởng & Hạng thẻ</span>
                </a>
                <a href="{{ route('account.notifications') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Thông báo</span>
                </a>
            </div>
        </div>

        <!-- Wishlist Content -->
        <div class="md:col-span-3 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold font-display text-heading">Sản phẩm yêu thích</h1>
                    <p class="text-xs text-muted mt-1">Các sản phẩm bạn đã lưu để xem lại hoặc đặt mua sau.</p>
                </div>
                <div class="text-xs text-muted">
                    <span>{{ $products->total() }} sản phẩm</span>
                </div>
            </div>

            @if(session('success'))
                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if($products->isEmpty())
                <div class="p-12 text-center rounded-2xl bg-surface border border-ui-border">
                    <div class="size-14 mx-auto rounded-full bg-surface-alt text-muted grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Danh sách yêu thích trống</h3>
                    <p class="text-xs text-muted mt-1 max-w-sm mx-auto">Bạn chưa lưu sản phẩm nào. Hãy khám phá bộ sưu tập nội thất để tìm sản phẩm ưng ý.</p>
                    <div class="mt-4">
                        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs">
                            Khám phá sản phẩm
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($products as $product)
                        <div class="group rounded-2xl bg-surface border border-ui-border overflow-hidden shadow-xs hover:border-primary/50 transition flex flex-col justify-between">
                            <div>
                                <!-- Image & Favorite Toggle -->
                                <div class="relative aspect-square bg-surface-alt overflow-hidden">
                                    <a href="{{ route('products.show', $product->slug) }}">
                                        @if($product->primaryImage)
                                            <img src="{{ asset('storage/' . $product->primaryImage->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-muted">
                                                <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                            </div>
                                        @endif
                                    </a>

                                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="absolute top-2.5 right-2.5">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="size-8 rounded-full bg-surface/90 backdrop-blur-xs border border-ui-border text-red-500 hover:bg-surface grid place-items-center transition shadow-xs"
                                            title="Bỏ khỏi yêu thích"
                                        >
                                            <svg viewBox="0 0 24 24" class="size-4 fill-current" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                        </button>
                                    </form>
                                </div>

                                <div class="p-4 space-y-1.5">
                                    <div class="text-[11px] text-muted">{{ $product->category?->name ?? 'Nội thất' }}</div>
                                    <h3 class="text-xs font-bold text-heading line-clamp-1 group-hover:text-primary transition">
                                        <a href="{{ route('products.show', $product->slug) }}">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    <div class="text-sm font-bold text-primary pt-1">
                                        {{ number_format($product->base_price, 0, ',', '.') }}đ
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 pt-0">
                                <a
                                    href="{{ route('products.show', $product->slug) }}"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-surface-alt border border-ui-border text-xs font-semibold text-heading hover:bg-primary hover:text-primary-foreground hover:border-primary transition"
                                >
                                    <span>Xem chi tiết & Mua</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="pt-4">
                        {{ $products->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
