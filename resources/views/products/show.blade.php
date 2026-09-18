@extends('layouts.app')

@section('title', $product->name . ' | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-8 sm:py-12">
    <div class="page-shell">
        <!-- Breadcrumbs -->
        <nav class="flex items-center flex-wrap gap-2 text-xs text-muted mb-8" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('products.index') }}" class="hover:text-heading transition-colors">Sản phẩm</a>
            @if ($product->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-heading transition-colors">
                    {{ $product->category->name }}
                </a>
            @endif
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium truncate max-w-[200px] sm:max-w-xs" aria-current="page">{{ $product->name }}</span>
        </nav>

        <!-- Main Product Section: 2 Columns on Desktop -->
        <div
            class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start"
            x-data="productDetail({
                basePrice: {{ (float) $product->base_price }},
                baseSku: '{{ $product->sku }}',
                variants: {{ Js::from($product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'color' => $v->color,
                    'size' => $v->size,
                    'material' => $v->material,
                    'price' => (float) $v->price,
                    'stock' => (int) $v->stock,
                ])) }},
                totalStock: {{ $product->totalStock() }}
            })"
        >
            <!-- Left Column: Gallery -->
            <div class="lg:col-span-7">
                @php
                    $allImages = $product->images->isNotEmpty()
                        ? $product->images
                        : collect([
                            (object) [
                                'image_path' => $product->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80',
                                'is_primary' => true,
                                'id' => 0
                            ]
                        ]);
                    $primaryImg = $product->primaryImage?->image_path ?? $allImages->first()->image_path;
                @endphp

                <div
                    class="space-y-4"
                    x-data="{
                        activeImage: '{{ $primaryImg }}',
                        setActive(img) { this.activeImage = img; }
                    }"
                >
                    <!-- Main Large Image Wrapper -->
                    <div class="relative aspect-4/3 sm:aspect-16/10 w-full overflow-hidden rounded-2xl sm:rounded-3xl border border-ui-border bg-surface shadow-sm">
                        <img
                            :src="activeImage"
                            src="{{ $primaryImg }}"
                            alt="{{ $product->name }}"
                            class="size-full object-cover transition-opacity duration-300"
                            loading="eager"
                        >
                        <!-- Stock Status Floating Badge -->
                        <div class="absolute top-4 left-4">
                            @if ($product->isOutOfStock())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/90 text-white px-3.5 py-1 text-xs font-bold tracking-wide shadow-sm backdrop-blur-sm">
                                    <span class="size-1.5 rounded-full bg-white animate-pulse"></span>
                                    Hết hàng
                                </span>
                            @elseif ($product->isLowStock())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/90 text-white px-3.5 py-1 text-xs font-bold tracking-wide shadow-sm backdrop-blur-sm">
                                    <span class="size-1.5 rounded-full bg-white animate-pulse"></span>
                                    Sắp hết hàng (còn {{ $product->totalStock() }})
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600/90 text-white px-3.5 py-1 text-xs font-bold tracking-wide shadow-sm backdrop-blur-sm">
                                    <span class="size-1.5 rounded-full bg-white"></span>
                                    Còn hàng
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Thumbnails Carousel / Grid -->
                    @if ($allImages->count() > 1)
                        <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1" role="tablist" aria-label="Hình ảnh sản phẩm">
                            @foreach ($allImages as $img)
                                <button
                                    type="button"
                                    class="relative shrink-0 size-20 sm:size-24 rounded-xl sm:rounded-2xl overflow-hidden border-2 transition focus:outline-none"
                                    :class="activeImage === '{{ $img->image_path }}' ? 'border-primary ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40 opacity-70 hover:opacity-100'"
                                    @click="setActive('{{ $img->image_path }}')"
                                    aria-label="Xem ảnh {{ $loop->iteration }}"
                                >
                                    <img
                                        src="{{ $img->image_path }}"
                                        alt="{{ $product->name }} thu nhỏ {{ $loop->iteration }}"
                                        class="size-full object-cover"
                                        loading="lazy"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Product Information -->
            <div class="lg:col-span-5 flex flex-col">
                <!-- Category & SKU -->
                <div class="flex items-center justify-between gap-4 text-xs">
                    <p class="font-semibold uppercase tracking-[0.18em] text-accent">
                        {{ $product->category?->name ?? 'Nội thất Mộc An' }}
                    </p>
                    <span class="font-mono text-muted tracking-wider" x-text="'SKU: ' + activeSku">
                        SKU: {{ $product->sku }}
                    </span>
                </div>

                <!-- Product Name -->
                <h1 class="mt-3 font-display text-2xl sm:text-3xl lg:text-4xl font-semibold text-heading leading-snug">
                    {{ $product->name }}
                </h1>

                <!-- Price Block -->
                <div class="mt-4 flex items-baseline gap-3 pb-6 border-b border-ui-border">
                    <span class="text-2xl sm:text-3xl font-bold tracking-tight text-heading" x-text="formatCurrency(activePrice)">
                        {{ number_format((float) $product->base_price, 0, ',', '.') }}₫
                    </span>
                    <span class="text-xs text-muted">Đã bao gồm VAT</span>
                </div>

                <!-- Short Description -->
                @if ($product->short_description)
                    <div class="mt-6 text-sm text-body leading-relaxed">
                        {{ $product->short_description }}
                    </div>
                @endif

                <!-- Variant Selector (if variants exist) -->
                @if ($product->variants->isNotEmpty())
                    <div class="mt-6 space-y-4 rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-heading">Phiên bản / Biến thể</span>
                            <span class="text-muted" x-text="'Tồn kho: ' + activeStock + ' sản phẩm'"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($product->variants as $variant)
                                <button
                                    type="button"
                                    class="flex flex-col text-left p-3 rounded-xl border transition text-xs relative"
                                    :class="{
                                        'border-primary bg-surface-alt ring-2 ring-primary/20': selectedVariantId === {{ $variant->id }},
                                        'border-ui-border bg-surface hover:border-heading/40': selectedVariantId !== {{ $variant->id }} && {{ $variant->stock }} > 0,
                                        'opacity-40 cursor-not-allowed border-ui-border bg-surface-alt': {{ $variant->stock }} <= 0
                                    }"
                                    :disabled="{{ $variant->stock }} <= 0"
                                    @click="selectVariant({{ $variant->id }})"
                                >
                                    <div class="flex items-center justify-between w-full font-semibold text-heading">
                                        <span>{{ $variant->color ?? ($variant->size ?? 'Mặc định') }}</span>
                                        <span>{{ number_format((float) $variant->price, 0, ',', '.') }}₫</span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between w-full text-[11px] text-muted">
                                        <span>{{ implode(' · ', array_filter([$variant->size, $variant->material])) }}</span>
                                        @if ($variant->stock <= 0)
                                            <span class="text-red-500 font-semibold">Hết hàng</span>
                                        @else
                                            <span>Còn {{ $variant->stock }}</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Quantity and Cart Action Preparation -->
                <div class="mt-8 space-y-4">
                    <div class="flex items-center gap-4">
                        <label for="quantity-input" class="text-xs font-bold text-heading">Số lượng:</label>
                        <div class="flex items-center rounded-xl border border-ui-border bg-surface-alt">
                            <button
                                type="button"
                                class="size-10 flex items-center justify-center text-body hover:text-heading transition disabled:opacity-30 disabled:cursor-not-allowed"
                                @click="decreaseQuantity()"
                                :disabled="quantity <= 1 || isOutOfStock"
                                aria-label="Giảm số lượng"
                            >
                                -
                            </button>
                            <input
                                id="quantity-input"
                                type="number"
                                min="1"
                                :max="activeStock"
                                x-model.number="quantity"
                                :disabled="isOutOfStock"
                                class="w-14 text-center text-sm font-semibold text-heading bg-transparent border-none focus:outline-none focus:ring-0 py-2 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            >
                            <button
                                type="button"
                                class="size-10 flex items-center justify-center text-body hover:text-heading transition disabled:opacity-30 disabled:cursor-not-allowed"
                                @click="increaseQuantity()"
                                :disabled="quantity >= activeStock || isOutOfStock"
                                aria-label="Tăng số lượng"
                            >
                                +
                            </button>
                        </div>
                        <span class="text-xs text-muted" x-text="stockStatusMessage"></span>
                    </div>

                    <!-- Cart Form -->
                    <form method="POST" action="{{ route('cart.store') }}" class="pt-2">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedVariantId">
                        <input type="hidden" name="quantity" :value="quantity">

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <button
                                type="submit"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="isOutOfStock || !selectedVariantId"
                                title="Thêm sản phẩm vào giỏ hàng"
                            >
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                                <span>Thêm vào giỏ</span>
                            </button>
                            <button
                                type="button"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-ui-border bg-surface px-5 py-3.5 text-sm font-bold text-heading hover:border-primary transition"
                                aria-label="Thêm vào danh sách yêu thích"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 text-muted hover:text-red-500 transition-colors" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                <span class="sm:hidden">Yêu thích</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Commitment & Value Props -->
                <div class="mt-8 grid grid-cols-2 gap-4 pt-6 border-t border-ui-border text-xs text-muted">
                    <div class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" class="size-5 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 13l4 4L19 7"/></svg>
                        <span>Gỗ tuyển chọn, bền đẹp</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" class="size-5 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 12V8H4v10a2 2 0 002 2h12a2 2 0 002-2v-4z"/><path d="M4 8l8-4 8 4"/></svg>
                        <span>Bảo hành 24 tháng</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" class="size-5 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12h18M12 3v18"/></svg>
                        <span>Giao & lắp đặt tận nơi</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" class="size-5 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Đổi trả trong 7 ngày</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Product Description Section -->
        @if ($product->description)
            <div class="mt-16 sm:mt-24 rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
                <h2 class="font-display text-2xl font-semibold text-heading mb-6">Chi tiết sản phẩm</h2>
                <div class="prose prose-stone dark:prose-invert max-w-none text-body leading-relaxed space-y-4 text-sm sm:text-base">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
        @endif

        <!-- Related Products Section -->
        @if ($relatedProducts->isNotEmpty())
            <div class="mt-16 sm:mt-24">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <p class="eyebrow">Cùng bộ sưu tập</p>
                        <h2 class="mt-1 font-display text-2xl sm:text-3xl font-semibold text-heading">Sản phẩm liên quan</h2>
                    </div>
                    @if ($product->category)
                        <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="text-link text-xs sm:text-sm">
                            Xem tất cả {{ $product->category->name }} <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>

                <div class="grid gap-x-5 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <article class="product-card group">
                            <div class="product-media">
                                <a href="{{ route('products.show', $related->slug) }}" class="block size-full">
                                    <img
                                        src="{{ $related->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80' }}"
                                        alt="{{ $related->name }}"
                                        class="size-full object-cover transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                </a>
                                <button class="wishlist-button" type="button" aria-label="Thêm {{ $related->name }} vào yêu thích">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                </button>
                                <a href="{{ route('products.show', $related->slug) }}" class="quick-add">
                                    Xem chi tiết
                                </a>
                            </div>
                            <div class="px-2 pt-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $related->category?->name }}</p>
                                <h3 class="mt-2 font-display text-[1.25rem] font-semibold leading-snug text-heading">
                                    <a class="transition-colors hover:text-accent" href="{{ route('products.show', $related->slug) }}">{{ $related->name }}</a>
                                </h3>
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $related->base_price, 0, ',', '.') }}₫</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function productDetail(config) {
        return {
            basePrice: config.basePrice,
            baseSku: config.baseSku,
            variants: config.variants,
            totalStock: config.totalStock,
            selectedVariantId: config.variants.length > 0 ? (config.variants.find(v => v.stock > 0)?.id ?? config.variants[0]?.id) : null,
            quantity: 1,

            get selectedVariant() {
                return this.variants.find(v => v.id === this.selectedVariantId) || null;
            },
            get activePrice() {
                return this.selectedVariant ? this.selectedVariant.price : this.basePrice;
            },
            get activeSku() {
                return this.selectedVariant ? this.selectedVariant.sku : this.baseSku;
            },
            get activeStock() {
                return this.selectedVariant ? this.selectedVariant.stock : this.totalStock;
            },
            get isOutOfStock() {
                return this.activeStock <= 0;
            },
            get stockStatusMessage() {
                if (this.isOutOfStock) return 'Hết hàng';
                if (this.activeStock <= 5) return 'Chỉ còn ' + this.activeStock + ' sản phẩm';
                return 'Còn hàng';
            },
            selectVariant(id) {
                this.selectedVariantId = id;
                this.quantity = 1;
            },
            increaseQuantity() {
                if (this.quantity < this.activeStock) this.quantity++;
            },
            decreaseQuantity() {
                if (this.quantity > 1) this.quantity--;
            },
            formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
            }
        };
    }
</script>
@endsection
