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
                    $placeholder = 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80';

                    // Always resolve to absolute media URLs (never raw picture/... paths)
                    $galleryUrls = $product->images
                        ->map(fn ($img) => $img->url)
                        ->filter()
                        ->values();

                    if ($galleryUrls->isEmpty()) {
                        $galleryUrls = collect([$product->primary_image_url ?: $placeholder]);
                    }

                    $primaryUrl = $product->primaryImage?->url ?: ($galleryUrls->first() ?: $placeholder);
                @endphp

                <div
                    class="space-y-4"
                    x-data="{
                        activeImage: @js($primaryUrl),
                        setActive(img) { this.activeImage = img; }
                    }"
                >
                    <!-- Main Large Image Wrapper -->
                    <div class="relative aspect-4/3 sm:aspect-16/10 w-full overflow-hidden rounded-2xl sm:rounded-3xl border border-ui-border bg-surface shadow-sm">
                        <img
                            :src="activeImage"
                            src="{{ $primaryUrl }}"
                            alt="{{ $product->name }}"
                            class="h-full w-full object-cover rounded-2xl sm:rounded-3xl transition-opacity duration-300"
                            loading="eager"
                            onerror="this.onerror=null;this.src='{{ $placeholder }}'"
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
                    @if ($galleryUrls->count() > 1)
                        <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1" role="tablist" aria-label="Hình ảnh sản phẩm">
                            @foreach ($galleryUrls as $url)
                                <button
                                    type="button"
                                    class="relative shrink-0 size-20 sm:size-24 rounded-xl sm:rounded-2xl overflow-hidden border-2 transition focus:outline-none"
                                    :class="activeImage === @js($url) ? 'border-primary ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40 opacity-70 hover:opacity-100'"
                                    @click="setActive(@js($url))"
                                    aria-label="Xem ảnh {{ $loop->iteration }}"
                                >
                                    <img
                                        src="{{ $url }}"
                                        alt="{{ $product->name }} thu nhỏ {{ $loop->iteration }}"
                                        class="h-full w-full object-cover rounded-xl sm:rounded-2xl"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ $placeholder }}'"
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

                <!-- Rating Summary -->
                <div class="mt-2.5 flex items-center gap-2 text-xs">
                    <div class="flex items-center text-amber-500">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="size-4 {{ $i <= round($product->averageRating()) ? 'fill-amber-400 text-amber-400' : 'fill-stone-200 text-stone-200 dark:fill-stone-700 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="font-bold text-heading">{{ number_format($product->averageRating(), 1) }}</span>
                    <span class="text-muted">({{ $product->reviewsCount() }} đánh giá)</span>
                    <a href="#reviews-section" class="ml-1 text-accent hover:underline font-medium">Xem chi tiết</a>
                </div>

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

                    <!-- Cart Form & Buy Now -->
                    <form method="POST" action="{{ route('cart.store') }}" class="pt-2">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedVariantId">
                        <input type="hidden" name="quantity" :value="quantity">

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <button
                                type="submit"
                                name="buy_now"
                                value="0"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-xl border-2 border-primary bg-surface py-3.5 px-5 text-sm font-bold text-primary shadow-xs transition hover:bg-primary/5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                :disabled="isOutOfStock || !selectedVariantId"
                                title="Thêm sản phẩm vào giỏ hàng"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                                <span>Thêm vào giỏ</span>
                            </button>

                            <button
                                type="submit"
                                name="buy_now"
                                value="1"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary py-3.5 px-5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-95 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                :disabled="isOutOfStock || !selectedVariantId"
                                title="Mua nhanh và thanh toán ngay"
                            >
                                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                                <span>Mua nhanh</span>
                            </button>
                        </div>
                    </form>

                    <!-- Wishlist Toggle -->
                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-ui-border bg-surface px-5 py-2.5 text-xs font-bold text-heading hover:border-rose-300 transition"
                            aria-label="Lưu vào danh sách yêu thích"
                        >
                            <svg viewBox="0 0 24 24" class="size-4 {{ auth()->check() && $product->isWishlistedBy(auth()->user()) ? 'fill-rose-500 text-rose-500' : 'text-muted hover:text-rose-500' }} transition-colors" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                            <span>{{ auth()->check() && $product->isWishlistedBy(auth()->user()) ? 'Đã lưu yêu thích' : 'Thêm vào yêu thích' }}</span>
                        </button>
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

        <!-- Room Bundle Combo Section -->
        @if (isset($bundleProducts) && $bundleProducts->count() > 0)
            @php
                $currentMainVariant = $product->variants->first();
                $comboVariants = collect([$currentMainVariant])->filter();
                foreach($bundleProducts as $bp) {
                    if ($v = $bp->variants->first()) {
                        $comboVariants->push($v);
                    }
                }
                $comboTotalPrice = $comboVariants->sum('price');
                $comboDiscountPrice = $comboTotalPrice * 0.90;
                $savings = $comboTotalPrice - $comboDiscountPrice;
            @endphp
            @if ($comboVariants->count() >= 2)
                <section class="mt-16 sm:mt-24 rounded-3xl border-2 border-primary/30 bg-gradient-to-br from-surface via-surface-alt/60 to-surface p-6 sm:p-10 shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-ui-border">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/60 dark:text-amber-200 dark:border-amber-800">
                                🌟 COMBO PHỐI PHÒNG TIẾT KIỆM 10%
                            </span>
                            <h2 class="mt-2 font-display text-2xl sm:text-3xl font-bold text-heading">
                                Gợi ý không gian hoàn hảo cùng Mộc An
                            </h2>
                            <p class="text-xs sm:text-sm text-muted mt-1">
                                Mua cùng các sản phẩm đồng bộ phong cách và chất liệu gỗ để nhận ưu đãi giảm giá đặc quyền.
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                        <!-- Main Product -->
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-surface border border-ui-border">
                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-20 h-20 object-cover rounded-xl shrink-0">
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold text-primary uppercase">Sản phẩm đang xem</span>
                                <h3 class="text-sm font-bold text-heading truncate">{{ $product->name }}</h3>
                                <p class="text-xs font-semibold text-primary mt-1">{{ number_format($currentMainVariant ? $currentMainVariant->price : $product->base_price, 0, ',', '.') }}₫</p>
                            </div>
                        </div>

                        <!-- Plus Sign & Bundle Items -->
                        @foreach ($bundleProducts as $bp)
                            @php $bpVar = $bp->variants->first(); @endphp
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-surface border border-ui-border relative">
                                <span class="hidden md:flex absolute -left-5 top-1/2 -translate-y-1/2 w-6 h-6 items-center justify-center rounded-full bg-primary text-primary-foreground font-bold text-xs shadow z-10">+</span>
                                <img src="{{ $bp->primary_image_url }}" alt="{{ $bp->name }}" class="w-20 h-20 object-cover rounded-xl shrink-0">
                                <div class="min-w-0">
                                    <span class="text-[10px] font-bold text-muted uppercase">Gợi ý phối cùng</span>
                                    <h3 class="text-sm font-bold text-heading truncate">{{ $bp->name }}</h3>
                                    <p class="text-xs font-semibold text-heading mt-1">{{ number_format($bpVar ? $bpVar->price : $bp->base_price, 0, ',', '.') }}₫</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Combo Price & Add to Cart -->
                    <div class="mt-8 pt-6 border-t border-ui-border flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div>
                            <div class="flex items-baseline gap-3">
                                <span class="text-xs text-muted">Giá trọn bộ combo:</span>
                                <span class="text-2xl sm:text-3xl font-extrabold text-primary">{{ number_format($comboDiscountPrice, 0, ',', '.') }}₫</span>
                                <span class="text-sm text-muted line-through">{{ number_format($comboTotalPrice, 0, ',', '.') }}₫</span>
                            </div>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
                                🎉 Tiết kiệm ngay {{ number_format($savings, 0, ',', '.') }}₫ khi mua trọn bộ combo hôm nay!
                            </p>
                        </div>

                        <form method="POST" action="{{ route('cart.bundle') }}" class="w-full sm:w-auto">
                            @csrf
                            @foreach ($comboVariants as $cv)
                                <input type="hidden" name="variant_ids[]" value="{{ $cv->id }}">
                            @endforeach
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl text-sm font-bold bg-primary text-primary-foreground hover:opacity-90 shadow-md transition">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <span>Mua Trọn Bộ Combo (Giảm 10%)</span>
                            </button>
                        </form>
                    </div>
                </section>
            @endif
        @endif

        <!-- Full Product Description Section -->
        @if ($product->description)
            <div class="mt-16 sm:mt-24 rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
                <h2 class="font-display text-2xl font-semibold text-heading mb-6">Chi tiết sản phẩm</h2>
                <div class="prose prose-stone dark:prose-invert max-w-none text-body leading-relaxed space-y-4 text-sm sm:text-base">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
        @endif

        <!-- Customer Reviews & Ratings Section -->
        <section id="reviews-section" class="mt-16 sm:mt-24 scroll-mt-24">
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-8 border-b border-ui-border">
                    <div>
                        <p class="eyebrow">Phản hồi thực tế</p>
                        <h2 class="mt-1 font-display text-2xl sm:text-3xl font-semibold text-heading">Đánh giá từ khách hàng</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <div class="text-3xl font-bold text-heading">{{ number_format($product->averageRating(), 1) }}<span class="text-lg text-muted font-normal"> / 5</span></div>
                            <div class="flex items-center gap-1 justify-end text-amber-500 mt-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="size-4 {{ $i <= round($product->averageRating()) ? 'fill-amber-400 text-amber-400' : 'fill-stone-200 text-stone-200 dark:fill-stone-700 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs text-muted mt-1">{{ $product->reviewsCount() }} lượt đánh giá</p>
                        </div>
                    </div>
                </div>

                <!-- Review Notifications / Status -->
                @if (session('success'))
                    <div class="mt-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-4 text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
                        <svg class="size-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->has('review'))
                    <div class="mt-6 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-4 text-sm text-red-800 dark:text-red-200 flex items-center gap-3">
                        <svg class="size-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>{{ $errors->first('review') }}</span>
                    </div>
                @endif

                <!-- Review Action Area -->
                <div class="mt-8">
                    @guest
                        <div class="rounded-2xl border border-ui-border bg-surface-alt p-6 text-center">
                            <p class="text-sm text-body">Vui lòng đăng nhập bằng tài khoản đã mua sản phẩm để viết đánh giá.</p>
                            <a href="{{ route('login') }}" class="mt-3 inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-xs font-semibold text-primary-foreground hover:opacity-95 transition">
                                Đăng nhập ngay
                            </a>
                        </div>
                    @else
                        @if ($userReview)
                            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 dark:bg-emerald-950/20 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-emerald-600 text-white text-xs">✓</span>
                                        <h3 class="text-sm font-semibold text-heading">Đánh giá của bạn</h3>
                                    </div>
                                    <span class="text-xs text-muted">{{ $userReview->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="mt-2 flex items-center gap-1 text-amber-500">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="size-4 {{ $i <= $userReview->rating ? 'fill-amber-400 text-amber-400' : 'fill-stone-200 text-stone-200 dark:fill-stone-700 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="ml-2 text-xs font-bold text-heading">{{ $userReview->rating }} sao</span>
                                </div>
                                <p class="mt-2 text-sm text-body leading-relaxed">{{ $userReview->comment }}</p>
                            </div>
                        @elseif ($canReview)
                            <div class="rounded-2xl border border-ui-border bg-surface-alt p-6" x-data="{ rating: 5, hoverRating: 5 }">
                                <h3 class="text-base font-semibold text-heading mb-1">Gửi đánh giá sản phẩm</h3>
                                <p class="text-xs text-muted mb-4">Bạn đã hoàn thành đơn hàng cho sản phẩm này. Hãy chia sẻ cảm nhận trải nghiệm!</p>

                                <form method="POST" action="{{ route('products.reviews.store', $product->slug) }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="rating" :value="rating">

                                    <div>
                                        <label class="block text-xs font-semibold text-heading mb-1.5">Số sao đánh giá</label>
                                        <div class="flex items-center gap-1.5" @mouseleave="hoverRating = rating">
                                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                                <button
                                                    type="button"
                                                    class="p-1 text-stone-300 transition-colors focus:outline-none"
                                                    :class="{ 'text-amber-400': star <= (hoverRating || rating) }"
                                                    @mouseover="hoverRating = star"
                                                    @click="rating = star"
                                                >
                                                    <svg class="size-7 fill-current" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                </button>
                                            </template>
                                            <span class="ml-2 text-xs font-bold text-heading" x-text="rating + ' sao'"></span>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="review-comment" class="block text-xs font-semibold text-heading mb-1.5">Nội dung nhận xét</label>
                                        <textarea
                                            id="review-comment"
                                            name="comment"
                                            rows="3"
                                            required
                                            minlength="5"
                                            placeholder="Chất lượng sản phẩm, độ hoàn thiện của gỗ, dịch vụ giao hàng..."
                                            class="w-full rounded-xl border border-ui-border bg-surface px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        >{{ old('comment') }}</textarea>
                                        @error('comment')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-xs font-semibold text-primary-foreground hover:opacity-95 transition"
                                    >
                                        Gửi đánh giá
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="rounded-2xl border border-ui-border bg-surface-alt p-5 text-xs text-muted flex items-center gap-3">
                                <svg class="size-5 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Chỉ khách hàng đã đặt mua và nhận hàng thành công sản phẩm này mới có thể viết đánh giá xác thực.</span>
                            </div>
                        @endif
                    @endguest
                </div>

                <!-- Reviews List -->
                <div class="mt-10 divide-y divide-ui-border">
                    @forelse ($product->approvedReviews as $rev)
                        <div class="py-6 first:pt-0 last:pb-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">
                                        {{ mb_substr($rev->user?->name ?? 'K', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-sm text-heading">{{ $rev->user?->name ?? 'Khách hàng' }}</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-400">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Đã mua hàng
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1 text-amber-500 mt-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="size-3.5 {{ $i <= $rev->rating ? 'fill-amber-400 text-amber-400' : 'fill-stone-200 text-stone-200 dark:fill-stone-700 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <span class="text-xs text-muted">{{ $rev->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-3 text-sm text-body leading-relaxed pl-13">
                                {{ $rev->comment }}
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-muted text-sm">
                            Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên trải nghiệm và chia sẻ nhận xét!
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

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
                                        src="{{ $related->primary_image_url }}"
                                        alt="{{ $related->name }}"
                                        class="h-full w-full object-cover rounded-[1.1rem] transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80'"
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
