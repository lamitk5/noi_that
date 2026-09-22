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

        <!-- Toolbar: Search, Category Filter, Sort -->
        <div class="mb-8 rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-sm">
            <form method="GET" action="{{ route('products.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-center">
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

                    @if (request('q') || request('category') || (request('sort') && request('sort') !== 'latest'))
                        <a
                            href="{{ route('products.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs font-semibold text-muted hover:text-heading hover:border-primary transition"
                            title="Xóa bộ lọc"
                        >
                            Xóa lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div x-data="{ viewMode: 'grid' }">
            <!-- Active Filters & View Mode Bar -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2 text-xs text-muted">
                    <span>Tìm thấy <strong class="text-heading font-semibold">{{ $products->total() }}</strong> sản phẩm</span>

                    @if ($searchQuery)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-alt border border-ui-border text-heading text-xs">
                            <span>Từ khóa: "{{ $searchQuery }}"</span>
                            <a href="{{ route('products.index', request()->except('q')) }}" class="hover:text-rose-500 font-bold ml-1">✕</a>
                        </span>
                    @endif

                    @if ($currentCategory)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-alt border border-ui-border text-heading text-xs">
                            <span>Danh mục: {{ $currentCategory->name }}</span>
                            <a href="{{ route('products.index', request()->except('category')) }}" class="hover:text-rose-500 font-bold ml-1">✕</a>
                        </span>
                    @endif

                    @if (request('sort') && request('sort') !== 'latest')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-alt border border-ui-border text-heading text-xs">
                            <span>Sắp xếp: {{ request('sort') === 'price_asc' ? 'Giá tăng dần' : 'Giá giảm dần' }}</span>
                            <a href="{{ route('products.index', request()->except('sort')) }}" class="hover:text-rose-500 font-bold ml-1">✕</a>
                        </span>
                    @endif

                    @if ($searchQuery || $currentCategory || (request('sort') && request('sort') !== 'latest'))
                        <a href="{{ route('products.index') }}" class="text-primary hover:underline font-semibold ml-2">Xóa tất cả</a>
                    @endif
                </div>

                <div class="flex items-center gap-3 self-end md:self-auto">
                    @if ($products->hasPages())
                        <span class="text-xs text-muted hidden sm:inline">Trang {{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                    @endif

                    <!-- View Toggle -->
                    <div class="flex items-center rounded-xl border border-ui-border bg-surface p-1 shadow-2xs">
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading'"
                            class="p-1.5 rounded-lg transition"
                            title="Xem dạng lưới"
                            aria-label="Xem dạng lưới"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading'"
                            class="p-1.5 rounded-lg transition"
                            title="Xem dạng danh sách"
                            aria-label="Xem dạng danh sách"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product Grid / List State -->
            @if ($products->count() > 0)
                <div :class="viewMode === 'grid' ? 'grid gap-x-5 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4' : 'flex flex-col gap-4'">
                    @foreach ($products as $product)
                        @php
                            $totalStock = $product->variants->sum('stock');
                        @endphp
                        <article
                            class="product-card group"
                            :class="viewMode === 'list' ? '!p-4 flex flex-col sm:flex-row items-center gap-6' : ''"
                        >
                            <div
                                class="product-media"
                                :class="viewMode === 'list' ? 'sm:size-48 shrink-0 !aspect-square' : ''"
                            >
                                <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                    <img
                                        src="{{ $product->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80' }}"
                                        alt="{{ $product->name }}"
                                        class="size-full object-cover transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                </a>
                                <button
                                    type="button"
                                    class="wishlist-button"
                                    aria-label="Thêm {{ $product->name }} vào yêu thích"
                                >
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                </button>
                                <button
                                    type="button"
                                    class="absolute top-3 left-3 z-10 grid size-9 place-items-center rounded-full bg-surface/90 text-heading shadow-sm backdrop-blur-sm transition hover:scale-110 hover:bg-surface"
                                    @click="MocAnCompare.toggle({{ $product->id }})"
                                    title="So sánh sản phẩm"
                                    aria-label="So sánh {{ $product->name }}"
                                >
                                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18m-7 6h7M7 15l-4 3 4 3"/></svg>
                                </button>
                                <button
                                    type="button"
                                    @click="$dispatch('open-quick-view', {{ $product->id }})"
                                    class="quick-add cursor-pointer"
                                >
                                    Xem nhanh
                                </button>
                            </div>

                            <div class="px-2 pt-5 flex-1 min-w-0" :class="viewMode === 'list' ? '!pt-0 w-full' : ''">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category?->name }}</p>
                                    @if($totalStock > 5)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600">
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span> Còn hàng
                                        </span>
                                    @elseif($totalStock > 0)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600">
                                            <span class="size-1.5 rounded-full bg-amber-500"></span> Còn ít
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-500">
                                            <span class="size-1.5 rounded-full bg-rose-500"></span> Hết hàng
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-2 font-display text-[1.25rem] font-semibold leading-snug text-heading">
                                    <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                </h3>

                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                                    @if($product->variants->count() > 1)
                                        <span class="text-[11px] text-muted">{{ $product->variants->count() }} lựa chọn</span>
                                    @endif
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
</div>
@endsection
