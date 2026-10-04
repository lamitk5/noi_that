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
                    'color_hex' => $v->color_hex,
                    'size' => $v->size,
                    'material' => $v->material,
                    'price' => (float) $v->final_price,
                    'stock' => (int) $v->stock,
                ])) }},
                totalStock: {{ $product->totalStock() }},
                fallbackDims: {{ \Illuminate\Support\Js::from(\App\Support\FurnitureGlb::box($product->name, $product->dimensions)) }}
            })"
        >
            <!-- Left Column: Gallery -->
            <div class="lg:col-span-7">
                @php
                    $allImages = $product->images->isNotEmpty()
                        ? $product->images
                        : collect([
                            (object) [
                                'url' => $product->primary_image_url,
                                'is_primary' => true,
                                'id' => 0
                            ]
                        ]);
                    $primaryImg = $product->primary_image_url;
                @endphp
                <div
                    class="space-y-4"
                    x-data="{
                        activeImage: '{{ $primaryImg }}',
                        setActive(img) { this.activeImage = img; }
                    }"
                >
                    <!-- Main Large Image Wrapper -->
                    <div class="product-gallery">
                        <img
                            :src="activeImage"
                            src="{{ $primaryImg }}"
                            alt="{{ $product->name }}"
                            class="absolute inset-0 size-full object-cover transition-opacity duration-300"
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
                                    Sắp hết hàng
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600/90 text-white px-3.5 py-1 text-xs font-bold tracking-wide shadow-sm backdrop-blur-sm">
                                    <span class="size-1.5 rounded-full bg-white"></span>
                                    Còn hàng
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Thumbnails Carousel -->
                    @if ($allImages->count() > 1)
                        <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1" role="tablist" aria-label="Hình ảnh sản phẩm">
                            @foreach ($allImages as $img)
                                <button
                                    type="button"
                                    class="relative shrink-0 size-20 sm:size-24 rounded-xl sm:rounded-2xl overflow-hidden border-2 transition focus:outline-none"
                                    :class="activeImage === '{{ $img->url }}' ? 'border-primary ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40 opacity-70 hover:opacity-100'"
                                    @click="setActive('{{ $img->url }}')"
                                    aria-label="Xem ảnh {{ $loop->iteration }}"
                                >
                                    <img
                                        src="{{ $img->url }}"
                                        alt="{{ $product->name }} thu nhỏ {{ $loop->iteration }}"
                                        class="size-full object-cover"
                                        loading="lazy"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ route('rooms.mix', ['product' => $product->id]) }}" class="flex items-center justify-between gap-4 rounded-2xl sm:rounded-3xl border border-ui-border bg-surface px-5 py-4 shadow-sm transition hover:border-primary/50">
                        <span>
                            <span class="block text-sm font-bold text-heading">Thử đặt món này vào phòng</span>
                            <span class="block text-xs text-muted">Kích thước {{ \App\Support\FurnitureGlb::displaySize($product->name, $product->dimensions) }}, đặt đúng tỉ lệ trong phòng mẫu.</span>
                        </span>
                        <span class="shrink-0 rounded-full bg-primary px-4 py-2 text-xs font-bold text-primary-foreground">Phối phòng</span>
                    </a>

                    <div class="rounded-2xl sm:rounded-3xl border border-ui-border bg-surface p-5 shadow-sm">
                        <h2 class="font-display text-lg font-semibold text-heading">Chi tiết sản phẩm</h2>
                        @if ($product->description)
                            <div class="mt-3 text-sm leading-relaxed text-body">
                                {!! nl2br(e($product->description)) !!}
                            </div>
                        @elseif ($product->short_description)
                            <p class="mt-3 text-sm leading-relaxed text-body">{{ $product->short_description }}</p>
                        @else
                            <p class="mt-3 text-sm leading-relaxed text-body">{{ $product->name }} thuộc bộ sưu tập {{ $product->category?->name ?? 'Mộc An' }}, làm từ gỗ tự nhiên, hoàn thiện để dùng lâu trong nhà.</p>
                        @endif
                    </div>
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

                <a href="#reviews" class="mt-2 inline-flex items-center gap-2 text-xs text-muted hover:text-heading transition">
                    @include('partials.rating-stars', ['rating' => $ratingAvg, 'size' => 'size-4'])
                    @if ($ratingTotal > 0)
                        <span><strong class="text-heading">{{ number_format($ratingAvg, 1) }}</strong> ({{ $ratingTotal }} đánh giá)</span>
                    @else
                        <span>Chưa có đánh giá</span>
                    @endif
                </a>

                <!-- Price Block -->
                <div class="mt-4 flex items-baseline gap-3 pb-6 border-b border-ui-border">
                    <span class="text-2xl sm:text-3xl font-bold tracking-tight text-heading" x-text="formatCurrency(activePrice)">
                        {{ number_format((float) $product->base_price, 0, ',', '.') }}₫
                    </span>
                    <span class="text-xs text-muted">Đã bao gồm VAT</span>
                </div>

                <!-- Short Description -->
                @if ($product->short_description)
                    <div class="mt-4 text-sm text-body leading-relaxed">
                        {{ $product->short_description }}
                    </div>
                @endif

                <!-- Multi-Attribute Variant Selectors -->
                <div class="mt-6 space-y-5 rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
                    <!-- 1. Wood Color Selector -->
                    <template x-if="colors.length > 0">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-heading">
                                    Màu sắc / Vân gỗ:
                                    <span class="text-primary font-semibold ml-1" x-text="selectedColor"></span>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:gap-2.5">
                                <template x-for="c in colors" :key="c.name">
                                    <button
                                        type="button"
                                        @click="selectColor(c.name)"
                                        class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-semibold transition text-left"
                                        :class="selectedColor === c.name ? 'border-primary bg-primary/10 text-primary ring-2 ring-primary/20 shadow-xs' : 'border-ui-border bg-surface text-body hover:border-heading/40'"
                                    >
                                        <span class="size-5 rounded-full border border-black/10 shrink-0 shadow-xs" :style="'background-color: ' + c.hex"></span>
                                        <span class="truncate" x-text="c.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    @include('partials.fit-script')
                    <div class="space-y-3 rounded-2xl border border-ui-border bg-surface-alt p-4" x-show="itemDims" x-cloak>
                        <p class="text-xs font-bold uppercase tracking-wider text-heading">Kiểm tra cửa vào</p>
                        <p class="text-[11px] text-muted" x-text="itemDims ? ('Kiện hàng ' + itemDims.l + ' × ' + itemDims.w + ' × ' + itemDims.h + ' cm. Món vừa lối đi khi xoay được sao cho hai cạnh còn lại lọt miệng cửa.') : ''"></p>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-[11px] text-muted">Cửa rộng (cm)
                                <input type="number" min="1" x-model.number="doorWidth" class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm text-heading">
                            </label>
                            <label class="text-[11px] text-muted">Cửa cao (cm)
                                <input type="number" min="1" x-model.number="doorHeight" class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm text-heading">
                            </label>
                        </div>
                        <p class="text-xs font-semibold" :class="fitClass(doorFit)" x-text="fitLabel('Cửa vào', doorFit)"></p>
                    </div>
                    <p class="text-[11px] text-muted" x-show="!itemDims">Sản phẩm chưa có đủ ba số đo (dài × rộng × cao) để kiểm tra lối đi.</p>

                    <!-- 2. Size Selector -->
                    <template x-if="sizes.length > 0">
                        <div class="space-y-2.5 pt-3 border-t border-ui-border">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-heading">
                                    Kích thước:
                                    <span class="text-primary font-semibold ml-1" x-text="selectedSize"></span>
                                </span>
                                <span class="text-muted text-[11px]" x-text="stockStatusMessage"></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="s in sizes" :key="s">
                                    <button
                                        type="button"
                                        @click="selectSize(s)"
                                        class="px-3.5 py-2 rounded-xl border text-xs font-semibold transition"
                                        :class="selectedSize === s ? 'border-primary bg-primary text-primary-foreground shadow-xs font-bold' : 'border-ui-border bg-surface text-body hover:border-heading/40 hover:bg-surface-alt'"
                                    >
                                        <span x-text="s"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Active Variant Material Details -->
                    <div class="pt-2 text-[11px] text-muted flex items-center justify-between border-t border-dashed border-ui-border">
                        <span>Chất liệu: <strong class="text-heading" x-text="activeMaterial"></strong></span>
                        <span x-text="'Tồn kho: ' + activeStock + ' cái'"></span>
                    </div>
                </div>

                <!-- Quantity and Cart Action Preparation -->
                <div class="mt-6 space-y-4">
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
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    type="submit"
                                    name="buy_now"
                                    value="0"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-primary text-primary bg-primary/10 hover:bg-primary hover:text-primary-foreground font-bold px-4 py-3.5 text-sm transition shadow-xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                    :disabled="isOutOfStock || !selectedVariantId"
                                    title="Thêm sản phẩm vào giỏ hàng"
                                >
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                                    <span>Thêm vào giỏ</span>
                                </button>

                                <button
                                    type="submit"
                                    name="buy_now"
                                    value="1"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary hover:opacity-90 text-primary-foreground font-bold px-4 py-3.5 text-sm transition shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                    :disabled="isOutOfStock || !selectedVariantId"
                                    title="Mua nhanh - Không thêm vào giỏ hàng, thanh toán ngay"
                                >
                                    <svg viewBox="0 0 24 24" class="size-5 fill-current" fill="currentColor"><path d="M13 2L3 14h7v8l11-12h-8l1-8z"/></svg>
                                    <span>Mua nhanh</span>
                                </button>
                            </div>

                            @php
                                $isWishlisted = in_array($product->id, $wishlistProductIds ?? []);
                            @endphp
                            <button
                                type="button"
                                class="wishlist-detail-btn w-full inline-flex items-center justify-center gap-2 rounded-xl border {{ $isWishlisted ? 'border-rose-300 bg-rose-50/50 text-rose-600 dark:bg-rose-950/20 is-active' : 'border-ui-border bg-surface text-muted hover:text-rose-500 hover:border-rose-200' }} px-4 py-2.5 text-xs font-semibold transition cursor-pointer"
                                data-wishlist-url="{{ route('wishlist.toggle', $product) }}"
                                aria-label="{{ $isWishlisted ? 'Bỏ khỏi danh sách yêu thích' : 'Thêm vào danh sách yêu thích' }}"
                            >
                                <svg viewBox="0 0 24 24" class="wishlist-icon size-4 transition-colors {{ $isWishlisted ? 'fill-current text-rose-600' : '' }}" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                <span class="wishlist-btn-text">{{ $isWishlisted ? 'Đã lưu trong yêu thích' : 'Thêm vào danh sách yêu thích' }}</span>
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

        <!-- Reviews Section -->
        <section id="reviews" class="mt-6 scroll-mt-24 rounded-3xl border border-ui-border bg-surface p-6 shadow-xs">
            <h2 class="font-display text-2xl font-semibold text-heading mb-6">Đánh giá từ khách hàng</h2>

            @error('review')
                <div class="mb-6 p-3 bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm rounded-xl">{{ $message }}</div>
            @enderror

            <div class="grid gap-10 lg:grid-cols-12">
                <!-- Summary -->
                <div class="lg:col-span-4">
                    <div class="flex items-end gap-3">
                        <span class="text-5xl font-bold text-heading">{{ number_format($ratingAvg, 1) }}</span>
                        <span class="pb-1.5 text-sm text-muted">/ 5</span>
                    </div>
                    <div class="mt-2">
                        @include('partials.rating-stars', ['rating' => $ratingAvg, 'size' => 'size-5'])
                    </div>
                    <p class="mt-1 text-xs text-muted">{{ $ratingTotal }} đánh giá đã xác thực mua hàng</p>

                    <div class="mt-5 space-y-2">
                        @for ($star = 5; $star >= 1; $star--)
                            @php
                                $count = (int) ($ratingCounts[$star] ?? 0);
                                $percent = $ratingTotal > 0 ? round($count / $ratingTotal * 100) : 0;
                            @endphp
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-8 text-muted">{{ $star }} ★</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-alt">
                                    <div class="h-full rounded-full bg-amber-500" style="width: {{ $percent }}%"></div>
                                </div>
                                <span class="w-8 text-right text-muted">{{ $count }}</span>
                            </div>
                        @endfor
                    </div>

                    <!-- Review form -->
                    <div class="mt-8 border-t border-ui-border pt-6">
                        @guest
                            <p class="text-sm text-muted">
                                <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline">Đăng nhập</a>
                                để đánh giá sản phẩm bạn đã mua.
                            </p>
                        @else
                            @if ($canReview)
                                <form method="POST" action="{{ route('reviews.store', $product) }}" class="space-y-3" x-data="{ rating: {{ (int) old('rating', $userReview?->rating ?? 5) }}, hover: 0 }">
                                    @csrf
                                    <p class="text-sm font-semibold text-heading">{{ $userReview ? 'Cập nhật đánh giá của bạn' : 'Viết đánh giá của bạn' }}</p>
                                    <input type="hidden" name="rating" :value="rating">
                                    <div class="flex items-center gap-1" @mouseleave="hover = 0">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" class="text-amber-500 transition hover:scale-110" aria-label="{{ $i }} sao">
                                                <svg viewBox="0 0 20 20" class="size-7" stroke="currentColor" stroke-width="1.2" :class="(hover || rating) >= {{ $i }} ? 'fill-current' : 'fill-none'">
                                                    <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/>
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                    @error('rating') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                                    <textarea
                                        name="comment"
                                        rows="4"
                                        maxlength="1000"
                                        required
                                        placeholder="Chia sẻ cảm nhận về chất lượng, màu gỗ, giao hàng..."
                                        class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('comment') border-rose-500 @enderror"
                                    >{{ old('comment', $userReview?->comment) }}</textarea>
                                    @error('comment') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                                    <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-2.5 rounded-xl transition shadow-sm text-sm">
                                        {{ $userReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
                                    </button>
                                </form>
                            @else
                                <p class="text-sm text-muted">Chỉ khách đã mua và nhận hàng mới được đánh giá sản phẩm này.</p>
                            @endif
                        @endguest
                    </div>
                </div>

                <!-- Review list -->
                <div class="lg:col-span-8">
                    @forelse ($reviews as $review)
                        <article class="border-b border-ui-border py-5 first:pt-0 last:border-b-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex size-9 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                        {{ mb_strtoupper(mb_substr($review->user?->name ?? 'K', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-heading">{{ $review->user?->name ?? 'Khách hàng' }}</p>
                                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400">✓ Đã mua và nhận hàng</p>
                                    </div>
                                </div>
                                <span class="text-xs text-muted">{{ $review->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="mt-3">
                                @include('partials.rating-stars', ['rating' => $review->rating])
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-body whitespace-pre-line">{{ $review->comment }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
                    @endforelse

                    @if ($reviews->hasPages())
                        <div class="mt-6">{{ $reviews->links() }}</div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Related Products Section -->
        @if ($relatedProducts->isNotEmpty())
            <div class="mt-6">
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
                                        class="size-full object-cover transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                </a>
                                <button class="wishlist-button {{ in_array($related->id, $wishlistProductIds ?? []) ? 'is-active' : '' }}" type="button" data-wishlist-url="{{ route('wishlist.toggle', $related) }}" aria-label="Thêm {{ $related->name }} vào yêu thích">
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
                                @if ($related->rating_count > 0)
                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-muted">
                                        @include('partials.rating-stars', ['rating' => $related->rating_avg])
                                        <span>({{ $related->rating_count }})</span>
                                    </div>
                                @endif
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
            selectedColor: '',
            selectedSize: '',
            selectedVariantId: null,
            quantity: 1,
            fallbackDims: config.fallbackDims,
            doorWidth: '',
            doorHeight: '',

            get colors() {
                const list = [];
                this.variants.forEach(v => {
                    if (v.color && !list.some(c => c.name === v.color)) {
                        list.push({ name: v.color, hex: v.color_hex || '#C4A574', material: v.material });
                    }
                });
                return list;
            },

            get sizes() {
                const list = [];
                this.variants.forEach(v => {
                    if (v.size && !list.includes(v.size)) {
                        list.push(v.size);
                    }
                });
                const area = (size) => {
                    const dims = mocanParseDims(size);
                    return dims ? dims.l * dims.w * dims.h : 0;
                };
                return list.sort((a, b) => area(a) - area(b));
            },

            init() {
                const initial = this.variants.find(v => v.stock > 0) || this.variants[0];
                if (initial) {
                    this.selectedColor = initial.color || '';
                    this.selectedSize = initial.size || '';
                    this.selectedVariantId = initial.id;
                }
            },

            selectColor(color) {
                this.selectedColor = color;
                this.updateSelectedVariant();
            },

            selectSize(size) {
                this.selectedSize = size;
                this.updateSelectedVariant();
            },

            updateSelectedVariant() {
                // Find matching variant by color and size
                let match = this.variants.find(v => v.color === this.selectedColor && v.size === this.selectedSize);
                if (!match) {
                    match = this.variants.find(v => v.color === this.selectedColor);
                    if (match && match.size) {
                        this.selectedSize = match.size;
                    }
                }
                if (!match) {
                    match = this.variants[0];
                }

                if (match) {
                    this.selectedVariantId = match.id;
                    if (this.quantity > match.stock) {
                        this.quantity = Math.max(1, match.stock);
                    }
                }
            },

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

            get activeMaterial() {
                return this.selectedVariant ? (this.selectedVariant.material || 'Gỗ tự nhiên') : 'Gỗ tự nhiên';
            },

            get isOutOfStock() {
                return this.activeStock <= 0;
            },

            get stockStatusMessage() {
                if (this.isOutOfStock) return 'Hết hàng';
                if (this.activeStock <= 5) return 'Chỉ còn ' + this.activeStock + ' cái';
                return 'Còn ' + this.activeStock + ' sản phẩm';
            },

            increaseQuantity() {
                if (this.quantity < this.activeStock) this.quantity++;
            },

            decreaseQuantity() {
                if (this.quantity > 1) this.quantity--;
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
            },

            get itemDims() {
                return mocanParseDims(this.selectedSize) || this.fallbackDims;
            },

            get doorFit() {
                return mocanFits(this.itemDims, this.doorWidth, this.doorHeight);
            },

            fitLabel(name, result) {
                if (result === null) return name + ': nhập rộng và cao để kiểm tra.';
                return result ? name + ': món hàng lọt.' : name + ': kiện hàng quá khổ, cân nhắc tháo rời hoặc lối đi khác.';
            },

            fitClass(result) {
                if (result === null) return 'text-muted';
                return result ? 'text-emerald-600' : 'text-rose-600';
            }
        };
    }
</script>
@endsection