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

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300" role="alert">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Shipping & Payment Form -->
            <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" class="lg:col-span-7 space-y-8">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

                <!-- Customer & Shipping Information -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
                        <span class="grid size-8 place-items-center rounded-full bg-primary/10 text-primary font-bold text-sm">1</span>
                        <h2 class="font-display text-xl font-bold text-heading">Thông tin giao hàng</h2>
                    </div>

                    <div class="space-y-5" x-data="{
                        savedAddresses: {{ \Illuminate\Support\Js::from($addresses) }},
                        selectedAddressId: '{{ $defaultAddress?->id ?? '' }}',
                        name: @js(old('customer_name', $defaultAddress?->recipient_name ?? $user->name)),
                        phone: @js(old('customer_phone', $defaultAddress?->phone ?? '')),
                        address: @js(old('shipping_address', $defaultAddress?->address_line ?? '')),
                        applyAddress(id) {
                            if (!id) return;
                            const found = this.savedAddresses.find(a => a.id == id);
                            if (found) {
                                this.name = found.recipient_name;
                                this.phone = found.phone;
                                this.address = found.address_line;
                            }
                        }
                    }">
                        @if($addresses->isNotEmpty())
                            <div class="p-3.5 rounded-2xl bg-surface-alt border border-ui-border">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-heading">Địa chỉ đã lưu</span>
                                    <a href="{{ route('account.addresses.index') }}" target="_blank" class="text-xs text-primary hover:underline">Quản lý sổ địa chỉ</a>
                                </div>
                                <div class="grid sm:grid-cols-2 gap-2">
                                    @foreach($addresses as $addr)
                                        <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-ui-border cursor-pointer transition text-xs hover:border-primary/60 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                            <input type="radio" name="selected_saved_address" value="{{ $addr->id }}" x-model="selectedAddressId" @change="applyAddress($event.target.value)" class="mt-0.5 text-primary focus:ring-primary">
                                            <div class="min-w-0">
                                                <div class="font-semibold text-heading flex items-center gap-1.5">
                                                    <span>{{ $addr->recipient_name }}</span>
                                                    @if($addr->is_default)
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-primary/10 text-primary font-medium">Mặc định</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted text-[11px]">{{ $addr->phone }}</div>
                                                <div class="text-muted text-[11px] truncate">{{ $addr->address_line }}</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Họ và tên người nhận <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="{{ old('customer_name', $defaultAddress?->recipient_name ?? $user->name) }}"
                                x-model="name"
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
                                    value="{{ old('customer_phone', $defaultAddress?->phone ?? '') }}"
                                    x-model="phone"
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

                        <div>
                            <label for="shipping_address" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Địa chỉ nhận hàng <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="shipping_address"
                                name="shipping_address"
                                x-model="address"
                                rows="3"
                                required
                                autocomplete="street-address"
                                placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('shipping_address') border-rose-500 @enderror"
                            >{{ old('shipping_address', $defaultAddress?->address_line ?? '') }}</textarea>
                            @error('shipping_address')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="save_address" name="save_address" value="1" class="rounded border-ui-border text-primary focus:ring-primary">
                            <label for="save_address" class="text-xs text-muted cursor-pointer select-none">
                                Lưu thông tin này vào sổ địa chỉ để sử dụng cho các lần mua sau
                            </label>
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
            </form>

            <!-- Right Column: Order Summary & Promotions -->
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
                                        <img src="{{ $item->product->primaryImage->image_path }}" alt="{{ $item->product->name }}" class="size-full object-cover">
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

                    <!-- Voucher Box -->
                    <div class="pt-5 mt-4 border-t border-ui-border">
                        <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-2">Mã ưu đãi / Khuyến mãi</label>
                        @if ($appliedVoucher)
                            <div class="flex items-center justify-between p-3 rounded-xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 text-xs">
                                <div>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ $appliedVoucher->code }}</span>
                                    <span class="text-muted block text-[11px]">{{ $appliedVoucher->name }} (-{{ number_format($voucherDiscount, 0, ',', '.') }}₫)</span>
                                </div>
                                <form method="POST" action="{{ route('checkout.remove-voucher') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-700 font-semibold cursor-pointer">Bỏ mã</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('checkout.apply-voucher') }}" class="flex gap-2">
                                @csrf
                                <input
                                    type="text"
                                    name="code"
                                    placeholder="Nhập mã giảm giá..."
                                    required
                                    class="flex-1 uppercase rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                                >
                                <button
                                    type="submit"
                                    class="shrink-0 rounded-xl bg-surface-alt border border-ui-border px-4 py-2 text-xs font-bold text-heading hover:border-primary hover:text-primary transition cursor-pointer"
                                >
                                    Áp dụng
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Loyalty Points Redemption Box -->
                    @if ($user && $user->loyalty_points > 0)
                        <div class="pt-4 mt-4 border-t border-ui-border">
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="font-bold uppercase tracking-wider text-heading">Điểm tích lũy Mộc An</span>
                                <span class="text-muted">Bạn có <strong class="text-accent">{{ number_format($user->loyalty_points) }}</strong> điểm</span>
                            </div>
                            @if ($appliedPoints > 0)
                                <div class="flex items-center justify-between p-3 rounded-xl border border-blue-500/30 bg-blue-50/50 dark:bg-blue-950/20 text-xs">
                                    <div>
                                        <span class="font-bold text-blue-700 dark:text-blue-300">Đã dùng {{ number_format($appliedPoints) }} điểm</span>
                                        <span class="text-muted block text-[11px]">Giảm {{ number_format($pointsDiscount, 0, ',', '.') }}₫</span>
                                    </div>
                                    <form method="POST" action="{{ route('checkout.remove-points') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-700 font-semibold cursor-pointer">Hủy dùng</button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('checkout.apply-points') }}" class="flex gap-2">
                                    @csrf
                                    <input
                                        type="number"
                                        name="points"
                                        min="1"
                                        max="{{ $user->loyalty_points }}"
                                        placeholder="Số điểm muốn dùng..."
                                        required
                                        class="flex-1 rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                                    >
                                    <button
                                        type="submit"
                                        class="shrink-0 rounded-xl bg-surface-alt border border-ui-border px-4 py-2 text-xs font-bold text-heading hover:border-primary hover:text-primary transition cursor-pointer"
                                    >
                                        Đổi điểm
                                    </button>
                                </form>
                                <p class="text-[11px] text-muted mt-1.5">Tỷ lệ quy đổi: 1 điểm = 1.000₫ giảm giá trực tiếp.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Price Calculations -->
                    <div class="space-y-3 pt-5 border-t border-ui-border mt-4 text-sm">
                        <div class="flex justify-between text-muted">
                            <span>Tạm tính</span>
                            <span class="font-semibold text-heading">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                        </div>
                        <div class="flex justify-between text-muted">
                            <span>Phí vận chuyển</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $shippingFee > 0 ? number_format($shippingFee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                            </span>
                        </div>
                        @if ($voucherDiscount > 0)
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                <span>Giảm giá voucher ({{ $appliedVoucher?->code }})</span>
                                <span class="font-semibold">-{{ number_format($voucherDiscount, 0, ',', '.') }}₫</span>
                            </div>
                        @endif
                        @if ($pointsDiscount > 0)
                            <div class="flex justify-between text-blue-600 dark:text-blue-400">
                                <span>Giảm giá điểm thưởng ({{ number_format($appliedPoints) }} điểm)</span>
                                <span class="font-semibold">-{{ number_format($pointsDiscount, 0, ',', '.') }}₫</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-baseline pt-4 border-t border-ui-border">
                            <div>
                                <span class="font-bold text-heading block">Tổng thanh toán</span>
                                <span class="text-[11px] text-muted">(Đã bao gồm thuế VAT nếu có)</span>
                            </div>
                            <span class="font-display text-2xl font-bold text-primary">
                                {{ number_format($totalPrice, 0, ',', '.') }}₫
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button
                            type="submit"
                            form="checkout-form"
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
        </div>
    </div>
</div>
@endsection
