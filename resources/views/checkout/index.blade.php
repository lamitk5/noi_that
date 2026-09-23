@extends('layouts.app')

@section('title', 'Thanh toán đơn hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-6xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="mb-6 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('cart.index') }}" class="hover:text-heading transition">Giỏ hàng</a>
            <span>/</span>
            <span class="text-heading font-medium" aria-current="page">Thanh toán</span>
        </nav>

        <div class="mb-8">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Xác nhận đơn hàng</span>
            <h1 class="mt-1 font-display text-3xl font-semibold text-heading sm:text-4xl">Thanh toán</h1>
            <p class="mt-2 text-sm text-muted">Vui lòng kiểm tra thông tin nhận hàng và lựa chọn phương thức thanh toán.</p>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="grid lg:grid-cols-12 gap-8 items-start">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <!-- Left Column: Shipping & Payment -->
            <div class="lg:col-span-7 space-y-8">
                <!-- Customer & Shipping Information -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
                        <span class="grid size-8 place-items-center rounded-full bg-primary/10 text-primary font-bold text-sm">1</span>
                        <h2 class="font-display text-xl font-bold text-heading">Thông tin giao hàng</h2>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Họ và tên người nhận <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="{{ old('customer_name', $user->name) }}"
                                required
                                autocomplete="name"
                                placeholder="Ví dụ: Nguyễn Văn A"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('customer_name') border-rose-500 @enderror"
                            >
                            @error('customer_name')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="customer_phone" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Số điện thoại <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    value="{{ old('customer_phone') }}"
                                    required
                                    autocomplete="tel"
                                    placeholder="0912345678"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('customer_phone') border-rose-500 @enderror"
                                >
                                @error('customer_phone')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="customer_email" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Email thông báo
                                </label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="{{ old('customer_email', $user->email) }}"
                                    autocomplete="email"
                                    placeholder="example@domain.com"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('customer_email') border-rose-500 @enderror"
                                >
                                @error('customer_email')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- GHN Address Cascading Dropdowns -->
                        <div class="rounded-2xl border border-ui-border bg-surface-alt/50 p-4 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-ui-border">
                                <span class="text-xs font-bold uppercase tracking-wider text-heading flex items-center gap-1.5">
                                    <span>Địa chỉ nhận hàng</span>
                                    <span class="text-rose-500">*</span>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-orange-500/10 px-2.5 py-0.5 text-[11px] font-bold text-orange-600 dark:text-orange-400">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                    <span>Giao Hàng Nhanh (GHN)</span>
                                </span>
                            </div>

                            <input type="hidden" name="province_name" id="province_name" value="{{ old('province_name') }}">
                            <input type="hidden" name="district_name" id="district_name" value="{{ old('district_name') }}">
                            <input type="hidden" name="ward_name" id="ward_name" value="{{ old('ward_name') }}">

                            <div class="grid sm:grid-cols-3 gap-3">
                                <div>
                                    <label for="province_id" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">
                                        Tỉnh / Thành phố <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="province_id"
                                        name="province_id"
                                        class="w-full rounded-xl border border-ui-border bg-surface px-3 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">-- Chọn Tỉnh/Thành --</option>
                                        @foreach ($provinces ?? [] as $province)
                                            <option value="{{ $province['id'] }}" {{ old('province_id') == $province['id'] ? 'selected' : '' }}>
                                                {{ $province['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="district_id" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">
                                        Quận / Huyện <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="district_id"
                                        name="district_id"
                                        class="w-full rounded-xl border border-ui-border bg-surface px-3 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">-- Chọn Quận/Huyện --</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ward_code" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">
                                        Phường / Xã <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="ward_code"
                                        name="ward_code"
                                        class="w-full rounded-xl border border-ui-border bg-surface px-3 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">-- Chọn Phường/Xã --</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="shipping_address" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">
                                    Địa chỉ cụ thể (Số nhà, tên đường...) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="shipping_address"
                                    name="shipping_address"
                                    value="{{ old('shipping_address') }}"
                                    required
                                    autocomplete="street-address"
                                    placeholder="Ví dụ: Số 25, ngõ 123 đường Cầu Giấy"
                                    class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('shipping_address') border-rose-500 @enderror"
                                >
                                @error('shipping_address')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="note" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Ghi chú đơn hàng (tùy chọn)
                            </label>
                            <textarea
                                id="note"
                                name="note"
                                rows="2"
                                placeholder="Ghi chú về thời gian giao hàng, chỉ dẫn đường đi..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('note') border-rose-500 @enderror"
                            >{{ old('note') }}</textarea>
                            @error('note')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
                        <span class="grid size-8 place-items-center rounded-full bg-primary/10 text-primary font-bold text-sm">2</span>
                        <h2 class="font-display text-xl font-bold text-heading">Phương thức thanh toán</h2>
                    </div>

                    <div class="space-y-4">
                        <!-- COD Option -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-ui-border bg-surface-alt hover:border-primary/60 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                class="mt-1 size-4 text-primary focus:ring-primary border-ui-border"
                                {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}
                            >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-heading text-sm">Thanh toán khi nhận hàng (COD)</span>
                                    <span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Tiện lợi</span>
                                </div>
                                <p class="text-xs text-muted">Bạn sẽ thanh toán tiền mặt trực tiếp cho nhân viên vận chuyển khi kiểm tra và nhận hàng.</p>
                            </div>
                        </label>

                        <!-- Bank Transfer Option -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-ui-border bg-surface-alt hover:border-primary/60 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="bank_transfer"
                                class="mt-1 size-4 text-primary focus:ring-primary border-ui-border"
                                {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}
                            >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-heading text-sm">Chuyển khoản ngân hàng</span>
                                    <span class="rounded bg-blue-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">An toàn</span>
                                </div>
                                <p class="text-xs text-muted">Thông tin tài khoản ngân hàng chi tiết sẽ được hiển thị ngay sau khi bạn hoàn tất đặt hàng.</p>
                            </div>
                        </label>

                        <!-- VNPAY Option -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-ui-border bg-surface-alt hover:border-primary/60 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="vnpay"
                                class="mt-1 size-4 text-primary focus:ring-primary border-ui-border"
                                {{ old('payment_method') === 'vnpay' ? 'checked' : '' }}
                            >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-heading text-sm">VNPAY - ATM / Ngân hàng / QR</span>
                                    <span class="rounded bg-sky-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">Trực tuyến</span>
                                </div>
                                <p class="text-xs text-muted">Thanh toán an toàn qua cổng VNPAY bằng ứng dụng ngân hàng, thẻ ATM nội địa hoặc quét mã VNPAY-QR.</p>
                            </div>
                        </label>

                        <!-- MoMo Option -->
                        <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-ui-border bg-surface-alt hover:border-primary/60 cursor-pointer transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input
                                type="radio"
                                name="payment_method"
                                value="momo"
                                class="mt-1 size-4 text-primary focus:ring-primary border-ui-border"
                                {{ old('payment_method') === 'momo' ? 'checked' : '' }}
                            >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-heading text-sm">Ví MoMo</span>
                                    <span class="rounded bg-pink-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-pink-600 dark:text-pink-400">Ví điện tử</span>
                                </div>
                                <p class="text-xs text-muted">Thanh toán nhanh chóng bằng ứng dụng Ví điện tử MoMo.</p>
                            </div>
                        </label>

                        @error('payment_method')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="lg:col-span-5 space-y-6">
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm sticky top-28">
                    <h2 class="font-display text-xl font-bold text-heading border-b border-ui-border pb-4 mb-4">
                        Đơn hàng của bạn ({{ $items->count() }} sản phẩm)
                    </h2>

                    <!-- Items List -->
                    <div class="divide-y divide-ui-border max-h-80 overflow-y-auto pr-1 space-y-3">
                        @foreach ($items as $item)
                            <div class="flex items-center gap-4 pt-3 first:pt-0">
                                <div class="size-16 rounded-xl border border-ui-border bg-surface-alt overflow-hidden shrink-0">
                                    @if ($item->product->primaryImage)
                                        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=400&q=80'">
                                    @else
                                        <div class="size-full flex items-center justify-center text-muted">
                                            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-medium text-heading text-sm truncate">{{ $item->product->name }}</h3>
                                    <p class="text-xs text-muted">
                                        {{ $item->variant->color }} - {{ $item->variant->size }}
                                    </p>
                                    <p class="text-xs text-muted mt-0.5">
                                        {{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }}₫
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-display text-sm font-bold text-heading">
                                        {{ number_format($item->line_total, 0, ',', '.') }}₫
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Price Calculations -->
                    <div class="space-y-3 pt-6 border-t border-ui-border mt-4 text-sm">
                        <div class="flex justify-between text-muted">
                            <span>Tạm tính</span>
                            <span class="font-semibold text-heading">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                        </div>
                        @if (!empty($appliedCoupon) && $discountAmount > 0)
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400 font-medium">
                                <span>Mã giảm giá ({{ $appliedCoupon['code'] }})</span>
                                <span>-{{ number_format($discountAmount, 0, ',', '.') }}₫</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-muted">
                            <span class="flex items-center gap-1.5">
                                <span>Phí vận chuyển (GHN)</span>
                                <span id="shipping-loading" class="hidden text-xs text-primary animate-spin">⟳</span>
                            </span>
                            <span id="shipping-fee-display" class="font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $shippingFee > 0 ? number_format($shippingFee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-baseline pt-4 border-t border-ui-border">
                            <div>
                                <span class="font-bold text-heading block">Tổng thanh toán</span>
                                <span class="text-[11px] text-muted">(Đã bao gồm phí vận chuyển)</span>
                            </div>
                            <span id="total-price-display" class="font-display text-2xl font-bold text-primary">
                                {{ number_format($totalPrice, 0, ',', '.') }}₫
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-4 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition hover:opacity-95 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                        >
                            <span>Xác nhận đặt hàng</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>

                    <p class="text-center text-[11px] text-muted mt-3">
                        Bằng việc bấm đặt hàng, bạn đồng ý với Điều khoản mua hàng & Bảo mật của Mộc An.
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province_id');
    const districtSelect = document.getElementById('district_id');
    const wardSelect = document.getElementById('ward_code');
    const provinceNameInput = document.getElementById('province_name');
    const districtNameInput = document.getElementById('district_name');
    const wardNameInput = document.getElementById('ward_name');
    const shippingFeeDisplay = document.getElementById('shipping-fee-display');
    const totalPriceDisplay = document.getElementById('total-price-display');
    const shippingLoading = document.getElementById('shipping-loading');

    provinceSelect?.addEventListener('change', function () {
        const provinceId = this.value;
        const selectedText = this.options[this.selectedIndex]?.text || '';
        if (provinceNameInput) provinceNameInput.value = provinceId ? selectedText : '';

        districtSelect.innerHTML = '<option value="">-- Đang tải Quận/Huyện... --</option>';
        wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        if (districtNameInput) districtNameInput.value = '';
        if (wardNameInput) wardNameInput.value = '';

        if (!provinceId) {
            districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            return;
        }

        fetch(`/api/shipping/ghn/districts/${provinceId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
                    res.data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        districtSelect.appendChild(opt);
                    });
                } else {
                    districtSelect.innerHTML = '<option value="">-- Không tìm thấy quận/huyện --</option>';
                }
            })
            .catch(() => {
                districtSelect.innerHTML = '<option value="">-- Lỗi tải quận/huyện --</option>';
            });
    });

    districtSelect?.addEventListener('change', function () {
        const districtId = this.value;
        const selectedText = this.options[this.selectedIndex]?.text || '';
        if (districtNameInput) districtNameInput.value = districtId ? selectedText : '';

        wardSelect.innerHTML = '<option value="">-- Đang tải Phường/Xã... --</option>';
        if (wardNameInput) wardNameInput.value = '';

        if (!districtId) {
            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            return;
        }

        fetch(`/api/shipping/ghn/wards/${districtId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
                    res.data.forEach(w => {
                        const opt = document.createElement('option');
                        opt.value = w.code;
                        opt.textContent = w.name;
                        wardSelect.appendChild(opt);
                    });
                } else {
                    wardSelect.innerHTML = '<option value="">-- Không tìm thấy phường/xã --</option>';
                }
            })
            .catch(() => {
                wardSelect.innerHTML = '<option value="">-- Lỗi tải phường/xã --</option>';
            });
    });

    wardSelect?.addEventListener('change', function () {
        const wardCode = this.value;
        const selectedText = this.options[this.selectedIndex]?.text || '';
        if (wardNameInput) wardNameInput.value = wardCode ? selectedText : '';

        const districtId = districtSelect.value;
        if (!districtId || !wardCode) return;

        if (shippingLoading) shippingLoading.classList.remove('hidden');

        fetch('/api/shipping/ghn/calculate-fee', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                district_id: districtId,
                ward_code: wardCode,
            }),
        })
            .then(res => res.json())
            .then(data => {
                if (shippingLoading) shippingLoading.classList.add('hidden');
                if (data.formatted_fee && shippingFeeDisplay) {
                    shippingFeeDisplay.textContent = data.formatted_fee;
                }
                if (data.formatted_total && totalPriceDisplay) {
                    totalPriceDisplay.textContent = data.formatted_total;
                }
            })
            .catch(() => {
                if (shippingLoading) shippingLoading.classList.add('hidden');
            });
    });
});
</script>
@endsection
