@extends('layouts.app')

@section('title', 'Danh sách yêu thích | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10 sm:py-14">
    <div class="page-shell">
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <nav class="flex items-center gap-2 text-xs text-muted mb-3" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
                <span>/</span>
                <span class="text-heading font-medium">Danh sách yêu thích</span>
            </nav>
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="eyebrow">Bộ sưu tập cá nhân</p>
                    <h1 class="mt-2 font-display text-3xl sm:text-4xl lg:text-5xl font-semibold text-heading">Danh sách yêu thích</h1>
                    <p class="mt-3 max-w-2xl text-sm sm:text-base text-muted">
                        Lưu giữ những món đồ nội thất bạn yêu thích để dễ dàng theo dõi và mua sắm khi cần.
                    </p>
                </div>
                <div class="text-xs sm:text-sm font-semibold text-muted bg-surface px-4 py-2 rounded-xl border border-ui-border self-start sm:self-end">
                    <span id="wishlist-total-count" class="font-bold text-heading">{{ $products->count() }}</span> sản phẩm đã lưu
                </div>
            </div>
        </div>

        @guest
            <div class="rounded-3xl border border-ui-border bg-surface p-12 text-center max-w-md mx-auto shadow-sm my-12">
                <div class="size-16 rounded-full bg-rose-50 text-rose-500 grid place-items-center mx-auto mb-4">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                    </svg>
                </div>
                <h2 class="font-display text-xl font-semibold text-heading mb-2">Đăng nhập để xem danh sách yêu thích</h2>
                <p class="text-sm text-muted mb-6">Đăng nhập tài khoản Mộc An của bạn để đồng bộ các sản phẩm đã lưu trên mọi thiết bị.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                    >
                        Đăng nhập ngay
                    </a>
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center rounded-full border border-ui-border bg-surface-alt px-7 py-3 text-xs font-bold uppercase tracking-wider text-heading transition hover:border-primary"
                    >
                        Khám phá sản phẩm
                    </a>
                </div>
            </div>
        @else
            @if ($products->isEmpty())
                <div id="wishlist-empty-state" class="rounded-3xl border border-ui-border bg-surface p-12 text-center max-w-md mx-auto shadow-sm my-12">
                    <div class="size-16 rounded-full bg-surface-alt text-muted grid place-items-center mx-auto mb-4">
                        <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                    </div>
                    <h2 class="font-display text-xl font-semibold text-heading mb-2">Danh sách yêu thích trống</h2>
                    <p class="text-sm text-muted mb-6">Bạn chưa lưu sản phẩm nào. Hãy bấm biểu tượng trái tim ở góc các sản phẩm để lưu lại khi ưng ý nhé.</p>
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                    >
                        Khám phá sản phẩm <span aria-hidden="true" class="ml-2">→</span>
                    </a>
                </div>
            @else
                <div id="wishlist-grid" class="grid gap-x-5 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <article class="product-card group relative" id="wishlist-item-{{ $product->id }}">
                            <div class="product-media">
                                <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                    <img
                                        src="{{ $product->primary_image_url }}"
                                        alt="{{ $product->name }}"
                                        class="size-full object-cover transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                </a>

                                <form action="{{ route('wishlist.remove', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="wishlist-remove-btn absolute right-3 top-3 grid size-10 place-items-center rounded-full bg-surface/90 text-rose-500 shadow-sm transition hover:bg-rose-500 hover:text-white cursor-pointer"
                                        data-remove-url="{{ route('wishlist.remove', $product) }}"
                                        data-product-id="{{ $product->id }}"
                                        aria-label="Bỏ {{ $product->name }} khỏi yêu thích"
                                        title="Bỏ khỏi yêu thích"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-5 fill-current" stroke="currentColor" stroke-width="1.7">
                                            <path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/>
                                        </svg>
                                    </button>
                                </form>

                                <a href="{{ route('products.show', $product->slug) }}" class="quick-add">
                                    Xem chi tiết
                                </a>
                            </div>

                            <div class="px-2 pt-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category?->name ?? 'Nội thất' }}</p>
                                <h3 class="mt-2 font-display text-[1.25rem] font-semibold leading-snug text-heading">
                                    <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                </h3>
                                <div class="mt-3 flex items-center justify-between border-t border-ui-border/60 pt-3">
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                                    <a
                                        href="{{ route('products.show', $product->slug) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary transition hover:bg-primary hover:text-primary-foreground"
                                        title="Xem chi tiết và tùy chọn"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="9" cy="19" r="1"/>
                                            <circle cx="17" cy="19" r="1"/>
                                        </svg>
                                        <span>Chọn mua</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div id="wishlist-empty-placeholder" class="hidden rounded-3xl border border-ui-border bg-surface p-12 text-center max-w-md mx-auto shadow-sm my-12">
                    <div class="size-16 rounded-full bg-surface-alt text-muted grid place-items-center mx-auto mb-4">
                        <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                    </div>
                    <h2 class="font-display text-xl font-semibold text-heading mb-2">Danh sách yêu thích trống</h2>
                    <p class="text-sm text-muted mb-6">Bạn đã xóa hết sản phẩm khỏi danh sách yêu thích.</p>
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                    >
                        Khám phá sản phẩm <span aria-hidden="true" class="ml-2">→</span>
                    </a>
                </div>
            @endif
        @endguest
    </div>
</div>
@endsection
