@extends('layouts.app')

@section('title', 'Giỏ hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-14 bg-page">
    <div class="page-shell max-w-6xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Giỏ hàng</span>
        </nav>

        <div class="mb-8">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Mua sắm</span>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Giỏ hàng của bạn</h1>
            <p class="mt-2 text-sm text-muted">Kiểm tra các sản phẩm nội thất được tuyển chọn trước khi thanh toán.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 p-4 text-sm font-medium text-red-600 dark:text-red-400">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($items->isEmpty())
            <!-- Empty Cart State -->
            <div class="rounded-3xl border border-ui-border bg-surface p-10 sm:p-16 text-center shadow-xs">
                <div class="mx-auto size-20 rounded-full bg-surface-alt grid place-items-center text-muted mb-5">
                    <svg viewBox="0 0 24 24" class="size-10 text-muted" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                </div>
                <h2 class="font-display text-2xl font-semibold text-heading">Giỏ hàng của bạn đang trống</h2>
                <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                    Hãy khám phá bộ sưu tập đồ gỗ và sofa thanh lịch của Mộc An để tìm món đồ phù hợp cho tổ ấm của bạn.
                </p>
                <div class="mt-8">
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-90"
                    >
                        Tiếp tục mua sắm
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        @else
            <!-- Main Cart Layout: 2 Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
                <!-- Cart Items List (8 cols) -->
                <div class="lg:col-span-8 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-ui-border text-xs text-muted">
                        <span>{{ $items->count() }} mặt hàng (Tổng {{ $items->sum('quantity') }} sản phẩm)</span>
                        <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-muted hover:text-red-500 font-semibold transition">
                                Xóa tất cả
                            </button>
                        </form>
                    </div>

                    @foreach ($items as $item)
                        <div class="rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6">
                            <!-- Image -->
                            <div class="size-24 sm:size-28 rounded-xl overflow-hidden bg-surface-alt border border-ui-border shrink-0">
                                <a href="{{ route('products.show', $item->product->slug) }}">
                                    <img
                                        src="{{ $item->product->primary_image_url }}"
                                        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=400&q=80'"
                                        alt="{{ $item->product->name }}"
                                        class="size-full object-cover transition hover:scale-105"
                                    >
                                </a>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-muted">
                                    {{ $item->product->category?->name ?? 'Nội thất Mộc An' }}
                                </p>
                                <h3 class="mt-1 font-display text-base sm:text-lg font-semibold text-heading leading-snug">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="hover:text-accent transition-colors">
                                        {{ $item->product->name }}
                                    </a>
                                </h3>

                                <!-- Variant Info -->
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-muted">
                                    @if ($item->variant->color)
                                        <span class="inline-flex items-center gap-1 rounded bg-surface-alt px-2 py-0.5 border border-ui-border">
                                            Màu: {{ $item->variant->color }}
                                        </span>
                                    @endif
                                    @if ($item->variant->size)
                                        <span class="inline-flex items-center gap-1 rounded bg-surface-alt px-2 py-0.5 border border-ui-border">
                                            Size: {{ $item->variant->size }}
                                        </span>
                                    @endif
                                    @if ($item->variant->material)
                                        <span class="inline-flex items-center gap-1 rounded bg-surface-alt px-2 py-0.5 border border-ui-border">
                                            Chất liệu: {{ $item->variant->material }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-2 text-xs text-muted font-mono">
                                    Đơn giá: <span class="font-bold text-heading">{{ number_format($item->unit_price, 0, ',', '.') }}₫</span>
                                    @if ($item->variant->stock <= 5)
                                        <span class="text-amber-500 font-semibold ml-2">(Kho còn {{ $item->variant->stock }})</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Quantity Controls & Line Total -->
                            <div class="w-full sm:w-auto flex sm:flex-col items-center sm:items-end justify-between gap-3 pt-3 sm:pt-0 border-t sm:border-t-0 border-ui-border shrink-0">
                                <span class="font-display text-base font-bold text-heading">
                                    {{ number_format($item->line_total, 0, ',', '.') }}₫
                                </span>

                                <div class="flex items-center gap-2">
                                    <!-- Quantity Update Form -->
                                    <form method="POST" action="{{ route('cart.update', $item->variant->id) }}" class="flex items-center rounded-xl border border-ui-border bg-surface-alt">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="button"
                                            class="size-8 flex items-center justify-center text-body hover:text-heading disabled:opacity-30 disabled:cursor-not-allowed"
                                            onclick="const input = this.nextElementSibling; if(parseInt(input.value) > 1) { input.value = parseInt(input.value) - 1; this.form.submit(); }"
                                            @disabled($item->quantity <= 1)
                                            aria-label="Giảm 1 số lượng"
                                        >
                                            -
                                        </button>
                                        <input
                                            type="number"
                                            name="quantity"
                                            value="{{ $item->quantity }}"
                                            min="1"
                                            max="{{ $item->variant->stock }}"
                                            onchange="this.form.submit()"
                                            class="w-12 text-center text-xs font-semibold text-heading bg-transparent border-none focus:outline-none focus:ring-0 py-1.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            aria-label="Số lượng sản phẩm"
                                        >
                                        <button
                                            type="button"
                                            class="size-8 flex items-center justify-center text-body hover:text-heading disabled:opacity-30 disabled:cursor-not-allowed"
                                            onclick="const input = this.previousElementSibling; if(parseInt(input.value) < {{ $item->variant->stock }}) { input.value = parseInt(input.value) + 1; this.form.submit(); }"
                                            @disabled($item->quantity >= $item->variant->stock)
                                            aria-label="Tăng 1 số lượng"
                                        >
                                            +
                                        </button>
                                    </form>

                                    <!-- Remove Form -->
                                    <form method="POST" action="{{ route('cart.destroy', $item->variant->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="p-2 text-muted hover:text-red-500 rounded-lg hover:bg-red-500/10 transition"
                                            title="Xóa khỏi giỏ"
                                            aria-label="Xóa {{ $item->product->name }} khỏi giỏ hàng"
                                        >
                                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Order Summary (4 cols) -->
                <div class="lg:col-span-4 sticky top-24">
                    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm space-y-6">
                        <h2 class="font-display text-lg font-semibold text-heading">Tóm tắt đơn hàng</h2>

                        <!-- Coupon Form / Applied Coupon Badge -->
                        <div class="rounded-2xl border border-ui-border bg-surface-alt p-4 space-y-2.5">
                            <span class="text-xs font-bold text-heading block">Mã giảm giá / Voucher</span>

                            @if (session('coupon_success'))
                                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ session('coupon_success') }}</p>
                            @endif

                            @if (session('coupon_error'))
                                <p class="text-xs font-semibold text-red-500">{{ session('coupon_error') }}</p>
                            @endif

                            @if (!empty($appliedCoupon))
                                <div class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-mono font-bold text-xs text-emerald-700 dark:text-emerald-300 uppercase">{{ $appliedCoupon['code'] }}</span>
                                        <span class="text-[11px] text-muted truncate">(-{{ number_format($discountAmount, 0, ',', '.') }}₫)</span>
                                    </div>
                                    <form method="POST" action="{{ route('coupon.remove') }}" class="shrink-0">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-semibold text-red-500 hover:underline">Hủy</button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('coupon.apply') }}" class="flex items-center gap-2">
                                    @csrf
                                    <input
                                        type="text"
                                        name="code"
                                        required
                                        placeholder="Nhập mã (VD: MOCAN10)"
                                        class="uppercase font-mono flex-1 rounded-xl border border-ui-border bg-surface px-3 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                                    >
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-90 transition shrink-0"
                                    >
                                        Áp dụng
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="space-y-3 text-sm divide-y divide-ui-border">
                            <div class="flex items-center justify-between pb-3">
                                <span class="text-muted">Tạm tính</span>
                                <span class="font-bold text-heading">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                            </div>
                            @if (!empty($appliedCoupon) && $discountAmount > 0)
                                <div class="flex items-center justify-between py-3 text-emerald-600 dark:text-emerald-400 font-medium">
                                    <span>Giảm giá ({{ $appliedCoupon['code'] }})</span>
                                    <span>-{{ number_format($discountAmount, 0, ',', '.') }}₫</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between py-3">
                                <span class="text-muted">Phí vận chuyển</span>
                                <span class="text-xs font-semibold text-accent">Tính ở bước thanh toán</span>
                            </div>
                            <div class="flex items-baseline justify-between pt-3 text-base">
                                <span class="font-bold text-heading">Tổng tiền tạm tính</span>
                                <span class="font-display text-2xl font-bold text-heading">
                                    {{ number_format($totalPrice, 0, ',', '.') }}₫
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 pt-2">
                            <a
                                href="{{ route('checkout.index') }}"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-95"
                            >
                                <span>Tiến hành thanh toán</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>

                        <div class="pt-4 border-t border-ui-border space-y-2.5 text-xs text-muted">
                            <div class="flex items-center gap-2">
                                <svg viewBox="0 0 24 24" class="size-4 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                                <span>Cam kết gỗ tự nhiên 100% đạt chuẩn</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg viewBox="0 0 24 24" class="size-4 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                                <span>Bảo hành kết cấu 24 tháng chính hãng</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg viewBox="0 0 24 24" class="size-4 text-accent shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                                <span>Hỗ trợ đổi trả miễn phí trong 7 ngày</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
