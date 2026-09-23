@extends('layouts.app')

@section('title', 'Sản phẩm | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10 sm:py-14">
    <div class="page-shell">
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <nav class="flex items-center gap-2 text-xs text-muted mb-3" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
                <span>/</span>
                <span class="text-heading font-medium">Sản phẩm</span>
            </nav>
            <p class="eyebrow">Bộ sưu tập nội thất</p>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl lg:text-5xl font-semibold text-heading">Sản phẩm</h1>
            <p class="mt-3 max-w-2xl text-sm sm:text-base text-muted">
                Nội thất tuyển chọn cho không gian sống hiện đại, bền vững và ấm áp.
            </p>
        </div>

        <!-- Toolbar: Search, Category Filter, Sort, Price & Material -->
        <div class="mb-8 rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-sm">
            <form method="GET" action="{{ route('products.index') }}" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-center">
                    <!-- Search Input -->
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="search-input" class="sr-only">Tìm kiếm theo tên sản phẩm</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-muted">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                            </span>
                            <input
                                id="search-input"
                                type="text"
                                name="q"
                                value="{{ $searchQuery }}"
                                placeholder="Tìm kiếm theo tên sản phẩm..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt pl-10 pr-4 py-2.5 text-sm text-body placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition"
                            >
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="lg:col-span-3">
                        <label for="category-filter" class="sr-only">Lọc theo danh mục</label>
                        <select
                            id="category-filter"
                            name="category"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-sm text-body focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition cursor-pointer"
                            onchange="this.form.submit()"
                        >
                            <option value="">Tất cả danh mục</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort Filter -->
                    <div class="lg:col-span-3">
                        <label for="sort-filter" class="sr-only">Sắp xếp</label>
                        <select
                            id="sort-filter"
                            name="sort"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-sm text-body focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition cursor-pointer"
                            onchange="this.form.submit()"
                        >
                            <option value="latest" @selected($sort === 'latest')>Mới nhất</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Giá tăng dần</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Giá giảm dần</option>
                        </select>
                    </div>

                    <!-- Actions: Submit & Reset -->
                    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-2 lg:justify-end">
                        <button
                            type="submit"
                            class="inline-flex flex-1 lg:flex-none items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-90"
                        >
                            Lọc
                        </button>

                        @if (request('q') || request('category') || request('min_price') || request('max_price') || request('material') || (request('sort') && request('sort') !== 'latest'))
                            <a
                                href="{{ route('products.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs font-semibold text-muted hover:text-heading hover:border-primary transition"
                                title="Xóa bộ lọc"
                            >
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Sub-filters: Price Range & Material -->
                <div class="pt-3 border-t border-ui-border/60 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs">
                    <!-- Price Range -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-muted shrink-0">Khoảng giá:</span>
                        <input
                            type="number"
                            name="min_price"
                            value="{{ request('min_price') }}"
                            placeholder="Từ (đ)"
                            min="0"
                            step="100000"
                            class="w-28 rounded-lg border border-ui-border bg-surface-alt px-2.5 py-1.5 text-xs text-body focus:border-primary focus:outline-none"
                        >
                        <span class="text-muted">-</span>
                        <input
                            type="number"
                            name="max_price"
                            value="{{ request('max_price') }}"
                            placeholder="Đến (đ)"
                            min="0"
                            step="100000"
                            class="w-28 rounded-lg border border-ui-border bg-surface-alt px-2.5 py-1.5 text-xs text-body focus:border-primary focus:outline-none"
                        >
                    </div>

                    <!-- Material Selector -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-semibold text-muted mr-1">Chất liệu:</span>
                        @foreach ($availableMaterials as $mat)
                            <button
                                type="submit"
                                name="material"
                                value="{{ request('material') === $mat ? '' : $mat }}"
                                class="px-2.5 py-1 rounded-lg border text-xs transition cursor-pointer {{ request('material') === $mat ? 'bg-primary text-primary-foreground border-primary font-bold shadow-xs' : 'bg-surface-alt text-muted border-ui-border hover:text-heading hover:border-ui-border/80' }}"
                            >
                                {{ $mat }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        <!-- Filter Summary / Count -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs sm:text-sm text-muted">
            <div class="flex flex-wrap items-center gap-2">
                <span>Tìm thấy <span class="font-bold text-heading">{{ $products->total() }}</span> sản phẩm</span>
                @if ($searchQuery)
                    <span>cho từ khóa <span class="font-semibold text-heading">"{{ $searchQuery }}"</span></span>
                @endif
                @if ($currentCategory)
                    <span>trong danh mục <span class="font-semibold text-heading">{{ $currentCategory->name }}</span></span>
                @endif
                @if (request('min_price') || request('max_price'))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-alt border border-ui-border text-xs text-heading font-medium">
                        Giá: {{ request('min_price') ? number_format(request('min_price'), 0, ',', '.') . 'đ' : '0' }} - {{ request('max_price') ? number_format(request('max_price'), 0, ',', '.') . 'đ' : '∞' }}
                    </span>
                @endif
                @if (request('material'))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-900 border border-amber-200 text-xs font-bold dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800">
                        Chất liệu: {{ request('material') }}
                    </span>
                @endif
            </div>
            @if ($products->hasPages())
                <div>
                    Trang {{ $products->currentPage() }} / {{ $products->lastPage() }}
                </div>
            @endif
        </div>

        <!-- Product Grid / Empty State -->
        @if ($products->count() > 0)
            <div class="grid gap-x-5 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <article class="product-card group">
                        <div class="product-media">
                            <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                <img
                                    src="{{ $product->primary_image_url }}"
                                    alt="{{ $product->name }}"
                                    class="size-full object-cover transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                >
                            </a>
                            <button class="wishlist-button" type="button" aria-label="Thêm {{ $product->name }} vào yêu thích">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                            </button>
                            <a href="{{ route('products.show', $product->slug) }}" class="quick-add">
                                Xem chi tiết
                            </a>
                        </div>
                        <div class="px-2 pt-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category?->name }}</p>
                            <h3 class="mt-2 font-display text-[1.25rem] font-semibold leading-snug text-heading">
                                <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-12">
                {{ $products->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="rounded-3xl border border-ui-border bg-surface p-12 text-center max-w-md mx-auto shadow-sm my-12">
                <div class="size-16 rounded-full bg-surface-alt text-muted grid place-items-center mx-auto mb-4">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </div>
                <h2 class="font-display text-xl font-semibold text-heading mb-2">Không tìm thấy sản phẩm phù hợp.</h2>
                <p class="text-sm text-muted mb-6">Hãy thử tìm kiếm với từ khóa khác hoặc xóa bộ lọc để xem toàn bộ danh mục.</p>
                <a
                    href="{{ route('products.index') }}"
                    class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                >
                    Xóa bộ lọc
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
