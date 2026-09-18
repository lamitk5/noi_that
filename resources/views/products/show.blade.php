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

                <!-- Rating Summary Inline -->
                <div class="mt-2.5 flex items-center gap-2 text-xs">
                    @if ($reviewsCount > 0)
                        <div class="flex items-center text-amber-500">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="size-4 {{ $i <= round($reviewsAvg) ? 'fill-current' : 'text-stone-300 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="font-bold text-heading">{{ number_format($reviewsAvg, 1) }}/5</span>
                        <span class="text-muted">·</span>
                        <a href="#reviews" class="text-primary hover:underline font-medium">
                            {{ $reviewsCount }} đánh giá
                        </a>
                    @else
                        <span class="text-muted">Chưa có đánh giá</span>
                        <span class="text-muted">·</span>
                        <a href="#reviews" class="text-primary hover:underline font-medium">
                            Đánh giá từ khách mua hàng
                        </a>
                    @endif
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

        <!-- Customer Reviews Section -->
        <div id="reviews" class="mt-16 sm:mt-24 rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs scroll-mt-20">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-8 border-b border-ui-border">
                <div>
                    <p class="eyebrow">Nhận xét từ khách hàng</p>
                    <h2 class="mt-1 font-display text-2xl sm:text-3xl font-semibold text-heading">Đánh giá sản phẩm</h2>
                </div>

                <!-- Rating Summary Box -->
                <div class="flex items-center gap-4 bg-surface-alt/70 px-5 py-3 rounded-2xl border border-ui-border shrink-0">
                    <div class="text-center">
                        @if ($reviewsCount > 0)
                            <span class="font-display text-2xl sm:text-3xl font-bold text-heading">{{ number_format($reviewsAvg, 1) }}</span>
                            <span class="text-xs text-muted">/ 5</span>
                        @else
                            <span class="font-display text-sm font-semibold text-muted">Chưa có đánh giá</span>
                        @endif
                    </div>
                    <div class="border-l border-ui-border pl-4">
                        <div class="flex items-center text-amber-500">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="size-4 {{ $reviewsCount > 0 && $i <= round($reviewsAvg) ? 'fill-current' : 'text-stone-300 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-xs text-muted mt-0.5">Dựa trên {{ $reviewsCount }} lượt đánh giá</p>
                    </div>
                </div>
            </div>

            <!-- Review Action / Form Area -->
            <div class="my-8">
                @guest
                    <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="size-9 rounded-xl bg-surface border border-ui-border flex items-center justify-center text-muted shrink-0">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <span class="text-muted">Đăng nhập bằng tài khoản đã mua hàng để gửi nhận xét và đánh giá sản phẩm.</span>
                        </div>
                        <a href="{{ route('login') }}" class="shrink-0 font-bold uppercase tracking-wider text-[11px] text-primary hover:underline">
                            Đăng nhập ngay →
                        </a>
                    </div>
                @else
                    @if ($userReview)
                        <!-- User's Existing Review Card -->
                        <div x-data="{ editing: false }" class="rounded-2xl border border-primary/30 bg-surface-alt/40 p-5 sm:p-6 shadow-xs">
                            <div class="flex items-center justify-between gap-4 pb-4 border-b border-ui-border">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-heading">Đánh giá của bạn</span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Đã mua hàng
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    <button
                                        type="button"
                                        @click="editing = !editing"
                                        class="font-semibold text-primary hover:underline cursor-pointer"
                                    >
                                        <span x-text="editing ? 'Đóng' : 'Chỉnh sửa'"></span>
                                    </button>
                                    <span class="text-muted">·</span>
                                    <form
                                        method="POST"
                                        action="{{ route('reviews.destroy', $userReview->id) }}"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-rose-600 hover:underline cursor-pointer">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- View Mode -->
                            <div x-show="!editing" class="pt-4">
                                <div class="flex items-center gap-2 text-xs text-amber-500 mb-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="size-4 {{ $i <= $userReview->rating ? 'fill-current' : 'text-stone-300 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="text-muted ml-1 text-[11px]">{{ $userReview->updated_at->format('d/m/Y') }}</span>
                                </div>
                                @if ($userReview->comment)
                                    <p class="text-xs sm:text-sm text-body leading-relaxed whitespace-pre-line">{{ $userReview->comment }}</p>
                                @endif
                            </div>

                            <!-- Edit Form -->
                            <div x-show="editing" x-cloak class="pt-4">
                                <form method="POST" action="{{ route('reviews.update', $userReview->id) }}" class="space-y-4">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-xs font-semibold text-heading mb-1.5">Số sao đánh giá</label>
                                        <div class="flex items-center gap-4 text-xs">
                                            @for ($star = 5; $star >= 1; $star--)
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        name="rating"
                                                        value="{{ $star }}"
                                                        {{ (int) old('rating', $userReview->rating) === $star ? 'checked' : '' }}
                                                        class="text-primary focus:ring-primary size-4"
                                                    >
                                                    <span class="text-heading font-medium">{{ $star }} ★</span>
                                                </label>
                                            @endfor
                                        </div>
                                    </div>
                                    <div>
                                        <label for="comment_edit" class="block text-xs font-semibold text-heading mb-1.5">Nội dung nhận xét</label>
                                        <textarea
                                            id="comment_edit"
                                            name="comment"
                                            rows="3"
                                            maxlength="2000"
                                            class="w-full rounded-xl border border-ui-border bg-surface px-3.5 py-2.5 text-xs sm:text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        >{{ old('comment', $userReview->comment) }}</textarea>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-sm hover:opacity-95 cursor-pointer"
                                        >
                                            Cập nhật
                                        </button>
                                        <button
                                            type="button"
                                            @click="editing = false"
                                            class="text-xs text-muted hover:text-heading cursor-pointer"
                                        >
                                            Hủy
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @elseif ($canReview)
                        <!-- New Review Form -->
                        <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-5 sm:p-6 shadow-xs">
                            <h3 class="font-display text-sm font-bold text-heading mb-1">Chia sẻ cảm nhận của bạn</h3>
                            <p class="text-xs text-muted mb-4">Đơn hàng của bạn đã hoàn tất. Hãy giúp cộng đồng Mộc An hiểu rõ hơn về chất lượng sản phẩm.</p>

                            <form method="POST" action="{{ route('reviews.store', $product->slug) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold text-heading mb-1.5">Số sao đánh giá <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-4 text-xs">
                                        @for ($star = 5; $star >= 1; $star--)
                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="rating"
                                                    value="{{ $star }}"
                                                    {{ (int) old('rating', 5) === $star ? 'checked' : '' }}
                                                    class="text-primary focus:ring-primary size-4"
                                                    required
                                                >
                                                <span class="text-heading font-medium">{{ $star }} ★</span>
                                            </label>
                                        @endfor
                                    </div>
                                    @error('rating')
                                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="comment_new" class="block text-xs font-semibold text-heading mb-1.5">Nội dung nhận xét</label>
                                    <textarea
                                        id="comment_new"
                                        name="comment"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Màu sắc thực tế, chất lượng vật liệu gỗ, trải nghiệm sử dụng..."
                                        class="w-full rounded-xl border border-ui-border bg-surface px-3.5 py-2.5 text-xs sm:text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-md shadow-primary/20 hover:opacity-95 transition cursor-pointer"
                                >
                                    Gửi đánh giá
                                </button>
                            </form>
                        </div>
                    @elseif (! $hasCompletedPurchase)
                        <!-- Not Eligible Notice -->
                        <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-4 text-xs text-muted flex items-center gap-2.5">
                            <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Bạn có thể đánh giá sau khi đơn hàng được giao thành công.</span>
                        </div>
                    @endif
                @endguest
            </div>

            <!-- Review Items List -->
            <div class="divide-y divide-ui-border">
                @forelse ($reviews as $review)
                    <div class="py-5 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between gap-4 mb-2">
                            <div class="flex items-center gap-2.5">
                                <span class="font-semibold text-xs text-heading">{{ $review->user->name }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Đã mua hàng
                                </span>
                            </div>
                            <span class="text-[11px] text-muted">{{ $review->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center text-amber-500 mb-2">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="size-3.5 {{ $i <= $review->rating ? 'fill-current' : 'text-stone-300 dark:text-stone-700' }}" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        @if ($review->comment)
                            <p class="text-xs sm:text-sm text-body leading-relaxed whitespace-pre-line">{{ $review->comment }}</p>
                        @endif
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-muted">
                        Chưa có đánh giá cho sản phẩm này. Hãy là người đầu tiên trải nghiệm và chia sẻ nhận xét!
                    </div>
                @endforelse
            </div>

            <!-- Review Pagination -->
            @if ($reviews->hasPages())
                <div class="mt-6 pt-4 border-t border-ui-border">
                    {{ $reviews->fragment('reviews')->links() }}
                </div>
            @endif
        </div>

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
