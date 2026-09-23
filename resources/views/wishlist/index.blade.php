@extends('layouts.app')

@section('title', 'Sản phẩm yêu thích | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-14 bg-page">
    <div class="page-shell max-w-6xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Danh sách yêu thích</span>
        </nav>

        <div class="mb-8">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Bộ sưu tập cá nhân</span>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Sản phẩm yêu thích</h1>
            <p class="mt-2 text-sm text-muted">Những món đồ nội thất bạn đã lưu lại để tham khảo hoặc mua sau.</p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($products->isEmpty())
            <div class="rounded-3xl border border-ui-border bg-surface p-10 sm:p-16 text-center shadow-xs">
                <div class="mx-auto size-20 rounded-full bg-rose-500/10 text-rose-500 grid place-items-center mb-5">
                    <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                </div>
                <h2 class="font-display text-2xl font-semibold text-heading">Chưa có sản phẩm nào trong yêu thích</h2>
                <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                    Bấm vào biểu tượng trái tim ở bất kỳ sản phẩm nào để lưu lại danh sách riêng của bạn.
                </p>
                <div class="mt-8">
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-90"
                    >
                        Khám phá sản phẩm ngay
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        @else
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <article class="product-card group relative">
                        <div class="product-media">
                            <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                <img
                                    src="{{ $product->primary_image_url }}"
                                    onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80'"
                                    alt="{{ $product->name }}"
                                    class="size-full object-cover transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                >
                            </a>

                            <!-- Remove from wishlist form button -->
                            <form method="POST" action="{{ route('wishlist.toggle', $product->id) }}" class="absolute top-3 right-3 z-10">
                                @csrf
                                <button
                                    type="submit"
                                    class="size-9 rounded-full bg-surface/90 backdrop-blur-sm border border-ui-border text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center shadow-sm transition"
                                    title="Xóa khỏi yêu thích"
                                    aria-label="Xóa {{ $product->name }} khỏi yêu thích"
                                >
                                    <svg viewBox="0 0 24 24" class="size-4 fill-current" stroke="currentColor" stroke-width="1.5"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                </button>
                            </form>

                            <a href="{{ route('products.show', $product->slug) }}" class="quick-add">
                                Xem chi tiết
                            </a>
                        </div>

                        <div class="px-2 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">
                                {{ $product->category?->name ?? 'Nội thất Mộc An' }}
                            </p>
                            <h3 class="mt-1 font-display text-base font-semibold leading-snug text-heading">
                                <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">
                                    {{ $product->name }}
                                </a>
                            </h3>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm font-bold tracking-tight text-heading">
                                    {{ number_format((float) $product->base_price, 0, ',', '.') }}₫
                                </span>
                                @if ($product->isOutOfStock())
                                    <span class="text-[11px] font-medium text-red-500">Hết hàng</span>
                                @else
                                    <span class="text-[11px] font-medium text-emerald-600">Còn hàng</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
