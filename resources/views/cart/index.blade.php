@extends('layouts.app')

@section('title', 'Giỏ hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-14 bg-page">
    <div class="page-shell">
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

        @if (session('status') || session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm font-medium text-emerald-700 dark:text-emerald-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('status') ?? session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 p-4 text-sm font-medium text-rose-700 dark:text-rose-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
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
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary hover:opacity-90 px-7 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition"
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
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    id="select-all-cart-items"
                                    class="size-4 rounded border-ui-border text-primary focus:ring-primary accent-primary cursor-pointer"
                                    {{ $selectedCount === $items->count() && $items->isNotEmpty() ? 'checked' : '' }}
                                >
                                <span class="font-semibold text-heading text-xs">
                                    Chọn tất cả (<span id="selected-summary-count">{{ $selectedCount }}</span>/{{ $items->count() }})
                                </span>
                            </label>
                            <span class="text-muted/60 hidden sm:inline">•</span>
                            <span class="hidden sm:inline">Tổng {{ $items->sum('quantity') }} sản phẩm</span>
                        </div>
                        <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ giỏ hàng?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-muted hover:text-red-500 font-semibold transition cursor-pointer">
                                Xóa tất cả
                            </button>
                        </form>
                    </div>

                    @foreach ($items as $item)
                        @php
                            $isSelected = in_array((string) $item->key, array_map('strval', $selectedKeys), true);
                        @endphp
                        <div
                            class="cart-item-row rounded-2xl border transition-all duration-200 bg-surface p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 {{ $isSelected ? 'border-primary/40 ring-1 ring-primary/20' : 'border-ui-border opacity-75' }}"
                            data-key="{{ $item->key }}"
                            data-unit-price="{{ $item->unit_price }}"
                            data-quantity="{{ $item->quantity }}"
                            data-line-total="{{ $item->line_total }}"
                        >
                            <!-- Checkbox -->
                            <div class="flex items-center self-start sm:self-center shrink-0">
                                <label class="flex items-center cursor-pointer p-1 -m-1">
                                    <input
                                        type="checkbox"
                                        name="selected_items[]"
                                        value="{{ $item->key }}"
                                        class="cart-item-checkbox size-4 sm:size-5 rounded border-ui-border text-primary focus:ring-primary accent-primary cursor-pointer"
                                        {{ $isSelected ? 'checked' : '' }}
                                        aria-label="Chọn mua {{ $item->product->name }}"
                                    >
                                </label>
                            </div>

                            <!-- Image -->
                            <div class="size-24 sm:size-28 rounded-xl overflow-hidden bg-surface-alt border border-ui-border shrink-0">
                                <a href="{{ route('products.show', $item->product->slug) }}">
                                    <img
                                        src="{{ $item->product->primary_image_url }}"
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
                                        <span class="text-amber-600 font-semibold ml-2">(Kho còn {{ $item->variant->stock }})</span>
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
                                            class="size-8 flex items-center justify-center text-body hover:text-heading disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
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
                                            class="size-8 flex items-center justify-center text-body hover:text-heading disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
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
                                            class="p-2 text-muted hover:text-red-500 rounded-lg hover:bg-red-500/10 transition cursor-pointer"
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
                <div class="lg:col-span-4 sticky top-24 space-y-5">
                    <!-- Coupon Card -->
                    <div class="rounded-3xl border border-ui-border bg-surface p-5 shadow-xs">
                        <div class="flex items-center gap-2 mb-3">
                            <svg viewBox="0 0 24 24" class="size-5 text-primary" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                            <h3 class="font-display text-sm font-bold text-heading">Mã giảm giá / Voucher</h3>
                        </div>

                        @if (!empty($coupons))
                            <div class="space-y-2 mb-3">
                                @foreach ($coupons as $appliedCoupon)
                                    <div class="p-3 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-between">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono text-xs font-bold text-primary bg-surface px-2 py-0.5 rounded border border-ui-border">
                                                    {{ $appliedCoupon['code'] }}
                                                </span>
                                                @if (($appliedCoupon['amount'] ?? 0) > 0)
                                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                        -{{ number_format($appliedCoupon['amount'], 0, ',', '.') }}₫
                                                    </span>
                                                @elseif (($appliedCoupon['type'] ?? '') === 'shipping')
                                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Miễn phí ship</span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-muted truncate mt-1">{{ $appliedCoupon['description'] ?? 'Đã áp dụng mã' }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                            @csrf
                                            <input type="hidden" name="coupon_code" value="{{ $appliedCoupon['code'] }}">
                                            <button type="submit" class="text-xs text-rose-600 dark:text-rose-400 hover:text-rose-800 font-semibold px-2 py-1 rounded hover:bg-rose-500/10 transition cursor-pointer">
                                                Gỡ bỏ
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="space-y-2.5">
                            @csrf
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    name="coupon_code"
                                    id="cart_coupon_input"
                                    placeholder="Nhập thêm mã giảm giá..."
                                    class="flex-1 rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs font-semibold uppercase tracking-wider text-heading placeholder:normal-case placeholder:font-normal placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    required
                                >
                                <button
                                    type="submit"
                                    class="rounded-xl bg-primary hover:opacity-90 text-primary-foreground font-bold text-xs px-4 py-2 transition shadow-xs cursor-pointer"
                                >
                                    Áp dụng
                                </button>
                            </div>
                        </form>

                        @if (!empty($availableCoupons))
                            <div class="pt-3 border-t border-dashed border-ui-border">
                                <p class="text-[11px] font-semibold text-muted mb-2">Có thể dùng nhiều mã cùng lúc:</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($availableCoupons as $cCode => $cData)
                                        <button
                                            type="button"
                                            onclick="document.getElementById('cart_coupon_input').value='{{ $cCode }}'"
                                            title="{{ $cData['description'] }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-primary/30 bg-primary/5 hover:bg-primary/10 px-2 py-1 text-[11px] font-bold text-primary transition cursor-pointer"
                                        >
                                            <span class="font-mono">{{ $cCode }}</span>
                                            <span class="text-[10px] opacity-80">({{ $cData['description'] }})</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Summary Card -->
                    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-7 shadow-sm space-y-5">
                        <h2 class="font-display text-lg font-semibold text-heading">Tóm tắt đơn hàng</h2>

                        <div class="space-y-3 text-sm divide-y divide-ui-border">
                            <div class="flex items-center justify-between pb-3">
                                <span class="text-muted">Tạm tính (<span id="summary-selected-count">{{ $selectedCount }}</span> sản phẩm)</span>
                                <span class="font-bold text-heading" id="summary-subtotal">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                            </div>

                            <div id="summary-discount-row" class="flex items-center justify-between py-3 text-emerald-700 font-semibold {{ $discountAmount > 0 ? '' : 'hidden' }}">
                                <span class="flex items-center gap-1">
                                    <span>Giảm giá</span>
                                    @if (!empty($coupons))
                                        <span class="font-mono text-xs bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded" id="summary-coupon-badge">({{ collect($coupons)->pluck('code')->implode(', ') }})</span>
                                    @endif
                                </span>
                                <span id="summary-discount-amount">-{{ number_format($discountAmount, 0, ',', '.') }}₫</span>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <span class="text-muted">Phí vận chuyển</span>
                                <span class="text-xs font-semibold text-accent" id="summary-shipping-fee">
                                    @if ($quote['has_shipping_coupon'] && (! $hasCalculatedShipping || $payableShipping <= 0))
                                        Miễn phí
                                    @elseif ($hasCalculatedShipping)
                                        {{ number_format($payableShipping, 0, ',', '.') }}₫
                                    @elseif ($subtotal >= 5000000)
                                        Miễn phí
                                    @else
                                        Tính ở thanh toán
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-baseline justify-between pt-3 text-base">
                                <span class="font-bold text-heading">Tổng thanh toán</span>
                                <div class="text-right">
                                    <span class="font-display text-2xl font-bold text-heading" id="summary-total">
                                        {{ number_format($total, 0, ',', '.') }}₫
                                    </span>
                                    <p class="text-[11px] text-muted">Đã bao gồm VAT</p>
                                </div>
                            </div>
                        </div>

                        <form id="checkout-selected-form" method="POST" action="{{ route('cart.checkout') }}" class="space-y-3 pt-2">
                            @csrf
                            <div id="checkout-hidden-inputs">
                                @foreach ($selectedKeys as $key)
                                    <input type="hidden" name="selected_items[]" value="{{ $key }}">
                                @endforeach
                            </div>
                            <button
                                type="submit"
                                id="btn-proceed-checkout"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary hover:opacity-90 px-6 py-3.5 text-sm font-bold text-primary-foreground shadow-sm transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                {{ $selectedCount === 0 ? 'disabled' : '' }}
                            >
                                <span id="btn-checkout-label">Tiến hành thanh toán ({{ $selectedCount }})</span>
                                <span aria-hidden="true">→</span>
                            </button>
                            <p id="checkout-empty-warning" class="text-xs text-rose-500 text-center font-medium {{ $selectedCount === 0 ? '' : 'hidden' }}">
                                Vui lòng chọn ít nhất 1 sản phẩm để thanh toán
                            </p>
                        </form>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-cart-items');
    const itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');
    const selectedSummaryCount = document.getElementById('selected-summary-count');
    const summarySelectedCount = document.getElementById('summary-selected-count');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryDiscountRow = document.getElementById('summary-discount-row');
    const summaryDiscountAmount = document.getElementById('summary-discount-amount');
    const summaryShippingFee = document.getElementById('summary-shipping-fee');
    const summaryTotal = document.getElementById('summary-total');
    const btnProceedCheckout = document.getElementById('btn-proceed-checkout');
    const btnCheckoutLabel = document.getElementById('btn-checkout-label');
    const checkoutEmptyWarning = document.getElementById('checkout-empty-warning');
    const hiddenInputsContainer = document.getElementById('checkout-hidden-inputs');

    const config = {
        selectUrl: "{{ route('cart.select') }}",
        csrfToken: "{{ csrf_token() }}",
        coupons: @json($coupons),
        shippingFee: {{ (float) $shippingFee }},
        hasCalculatedShipping: {{ ($hasCalculatedShipping ?? false) ? 'true' : 'false' }},
    };

    let syncTimeout = null;

    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN').format(Math.max(0, Math.round(amount))) + '₫';
    }

    function calculateQuote(subtotal) {
        const coupons = Array.isArray(config.coupons) ? config.coupons : [];
        const ship = config.hasCalculatedShipping ? (config.shippingFee || 0) : 0;
        let price = 0;
        let shipDiscount = 0;
        let hasShippingCoupon = false;

        coupons.forEach(c => {
            if (subtotal < (c.min_order || 0)) {
                if (c.type === 'shipping') hasShippingCoupon = true;
                return;
            }
            if (c.type === 'shipping') {
                hasShippingCoupon = true;
                const cap = parseFloat(c.max_discount || c.value || 0);
                if (config.hasCalculatedShipping) {
                    shipDiscount += Math.min(Math.max(0, ship - shipDiscount), cap);
                }
                return;
            }
            let amount = 0;
            if (c.type === 'percent') {
                amount = subtotal * (parseFloat(c.value) / 100);
                if (c.max_discount && amount > parseFloat(c.max_discount)) {
                    amount = parseFloat(c.max_discount);
                }
            } else {
                amount = parseFloat(c.value || 0);
            }
            amount = Math.min(amount, Math.max(0, subtotal - price));
            price += amount;
        });

        const payable = config.hasCalculatedShipping ? Math.max(0, ship - shipDiscount) : 0;
        const total = subtotal === 0 ? 0 : Math.max(0, subtotal - price + payable);
        return { price, payable, hasShippingCoupon, total };
    }

    function updateCartUI() {
        const checkedBoxes = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'));
        const totalCount = itemCheckboxes.length;
        const selectedCount = checkedBoxes.length;

        // Update Select All Checkbox state
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = selectedCount === totalCount && totalCount > 0;
            selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < totalCount;
        }

        // Update row visual styling
        itemCheckboxes.forEach(cb => {
            const row = cb.closest('.cart-item-row');
            if (row) {
                if (cb.checked) {
                    row.classList.add('border-primary/40', 'ring-1', 'ring-primary/20');
                    row.classList.remove('border-ui-border', 'opacity-75');
                } else {
                    row.classList.remove('border-primary/40', 'ring-1', 'ring-primary/20');
                    row.classList.add('border-ui-border', 'opacity-75');
                }
            }
        });

        // Compute Subtotal
        let subtotal = 0;
        const selectedKeys = [];
        checkedBoxes.forEach(cb => {
            const row = cb.closest('.cart-item-row');
            if (row) {
                const lineTotal = parseFloat(row.dataset.lineTotal || 0);
                subtotal += lineTotal;
                selectedKeys.push(cb.value);
            }
        });

        // Compute Discount & Shipping & Total
        const priced = calculateQuote(subtotal);
        const discount = priced.price;
        const total = priced.total;

        // Update counts
        if (selectedSummaryCount) selectedSummaryCount.textContent = selectedCount;
        if (summarySelectedCount) summarySelectedCount.textContent = selectedCount;

        // Update values
        if (summarySubtotal) summarySubtotal.textContent = formatVND(subtotal);

        if (summaryDiscountRow && summaryDiscountAmount) {
            if (discount > 0) {
                summaryDiscountRow.classList.remove('hidden');
                summaryDiscountAmount.textContent = '-' + formatVND(discount);
            } else {
                summaryDiscountRow.classList.add('hidden');
            }
        }

        if (summaryShippingFee) {
            if (priced.hasShippingCoupon && (!config.hasCalculatedShipping || priced.payable <= 0)) {
                summaryShippingFee.textContent = 'Miễn phí';
            } else if (config.hasCalculatedShipping) {
                summaryShippingFee.textContent = formatVND(priced.payable);
            } else if (subtotal >= 5000000) {
                summaryShippingFee.textContent = 'Miễn phí';
            } else {
                summaryShippingFee.textContent = 'Tính ở thanh toán';
            }
        }

        if (summaryTotal) summaryTotal.textContent = formatVND(total);

        // Update Checkout Button & Warnings
        if (btnProceedCheckout) {
            btnProceedCheckout.disabled = selectedCount === 0;
        }
        if (btnCheckoutLabel) {
            btnCheckoutLabel.textContent = `Tiến hành thanh toán (${selectedCount})`;
        }
        if (checkoutEmptyWarning) {
            if (selectedCount === 0) {
                checkoutEmptyWarning.classList.remove('hidden');
            } else {
                checkoutEmptyWarning.classList.add('hidden');
            }
        }

        // Update hidden inputs for fallback form submit
        if (hiddenInputsContainer) {
            hiddenInputsContainer.innerHTML = '';
            selectedKeys.forEach(k => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_items[]';
                input.value = k;
                hiddenInputsContainer.appendChild(input);
            });
        }

        // Sync with backend session
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(() => {
            fetch(config.selectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                },
                body: JSON.stringify({ selected_keys: selectedKeys })
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    if (summarySubtotal) summarySubtotal.textContent = data.formatted_subtotal;
                    if (summaryTotal) summaryTotal.textContent = data.formatted_total;
                    if (data.discount_amount > 0 && summaryDiscountAmount) {
                        summaryDiscountAmount.textContent = '-' + data.formatted_discount;
                    }
                }
            })
            .catch(() => {});
        }, 200);
    }

    // Attach listener to "Select All"
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            itemCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateCartUI();
        });
    }

    // Attach listener to each item checkbox
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateCartUI);
    });

    // Form submit check
    const checkoutForm = document.getElementById('checkout-selected-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            const checked = document.querySelectorAll('.cart-item-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất 1 sản phẩm trước khi thanh toán.');
            }
        });
    }
});
</script>
@endsection