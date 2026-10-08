@extends('layouts.app')

@section('title', 'Thanh toán đơn hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 bg-page">
    <div class="page-shell">
        <!-- Breadcrumbs -->
        <nav class="mb-6 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
            <span>/</span>
            @if ($isBuyNow ?? false)
                <a href="{{ route('products.index') }}" class="hover:text-heading transition">Sản phẩm</a>
            @else
                <a href="{{ route('cart.index') }}" class="hover:text-heading transition">Giỏ hàng</a>
            @endif
            <span>/</span>
            <span class="text-heading font-medium" aria-current="page">Thanh toán</span>
        </nav>

        <div class="mb-8">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Xác nhận đơn hàng</span>
                @if ($isBuyNow ?? false)
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 border border-primary/20 px-2.5 py-0.5 text-[11px] font-bold text-primary">
                        <svg viewBox="0 0 24 24" class="size-3 fill-current" fill="currentColor"><path d="M13 2L3 14h7v8l11-12h-8l1-8z"/></svg>
                        Mua nhanh
                    </span>
                @endif
            </div>
            <h1 class="mt-1 font-display text-3xl font-semibold text-heading sm:text-4xl">Thanh toán</h1>
            <p class="mt-2 text-sm text-muted">
                @if ($isBuyNow ?? false)
                    Đơn hàng mua nhanh trực tiếp — không lưu vào giỏ hàng, giỏ hàng hiện tại của bạn vẫn được giữ nguyên.
                @else
                    Vui lòng kiểm tra thông tin nhận hàng và lựa chọn phương thức thanh toán.
                @endif
            </p>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300" role="alert">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="grid lg:grid-cols-12 gap-8 items-start">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
            <input type="hidden" name="is_buy_now" value="{{ ($isBuyNow ?? false) ? 1 : 0 }}">

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
                                value="{{ old('customer_name', $user?->name) }}"
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
                                    value="{{ old('customer_phone', $user?->phone) }}"
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
                                    Email thông báo <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="{{ old('customer_email', $user?->email) }}"
                                    required
                                    autocomplete="email"
                                    placeholder="example@domain.com"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('customer_email') border-rose-500 @enderror"
                                >
                                @error('customer_email')
                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if (!empty($savedAddresses) && count($savedAddresses) > 0)
                            <div class="p-3.5 rounded-2xl border border-ui-border bg-surface-alt/70">
                                <div class="flex items-center gap-2 mb-2 text-xs font-bold text-heading uppercase tracking-wider">
                                    <svg viewBox="0 0 24 24" class="size-4 text-primary" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                                    </svg>
                                    <span>Chọn từ địa chỉ đã lưu ({{ count($savedAddresses) }})</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($savedAddresses as $savedAddr)
                                        <button
                                            type="button"
                                            class="saved-address-chip text-left text-xs px-3 py-1.5 rounded-xl border border-ui-border bg-surface hover:border-primary hover:text-primary transition line-clamp-1 max-w-full cursor-pointer"
                                            data-address="{{ $savedAddr }}"
                                            title="{{ $savedAddr }}"
                                        >
                                            📍 {{ Str::limit($savedAddr, 50) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="shipping_address" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Địa chỉ nhận hàng <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="shipping_address"
                                name="shipping_address"
                                rows="3"
                                required
                                autocomplete="street-address"
                                placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('shipping_address') border-rose-500 @enderror"
                            >{{ old('shipping_address', $user?->address) }}</textarea>
                            @error('shipping_address')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
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
                        <!-- GHN Shipping Destination -->
                        <div class="grid sm:grid-cols-3 gap-4 mt-2" id="ghn-address-section">
                            <div>
                                <label for="province_id" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Tỉnh / Thành phố <span class="text-xs text-muted font-normal">(Tính phí ship)</span>
                                </label>
                                <select id="province_id" name="province_id"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option value="">-- Chọn tỉnh/TP --</option>
                                </select>
                                <input type="hidden" name="province_name" id="province_name" value="{{ old('province_name') }}">
                            </div>
                            <div>
                                <label for="district_id" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Quận / Huyện
                                </label>
                                <select id="district_id" name="district_id" disabled
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50">
                                    <option value="">-- Chọn quận/huyện --</option>
                                </select>
                                <input type="hidden" name="district_name" id="district_name" value="{{ old('district_name') }}">
                            </div>
                            <div>
                                <label for="ward_code" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Phường / Xã
                                </label>
                                <select id="ward_code" name="ward_code" disabled
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50">
                                    <option value="">-- Chọn phường/xã --</option>
                                </select>
                                <input type="hidden" name="ward_name" id="ward_name" value="{{ old('ward_name') }}">
                            </div>
                        </div>
                        <!-- Shipping fee display -->
                        <div id="shipping-fee-msg" class="mt-2 text-xs text-muted hidden"></div>
                    </div>
                </div>

                @php
                    $fitItems = $items->map(fn ($item) => [
                        'name' => $item->product->name,
                        'dims' => \App\Support\DimensionFit::parse($item->variant->size)
                            ?: \App\Support\FurnitureGlb::box($item->product->name, $item->product->dimensions),
                    ])->values();
                @endphp
                @include('partials.fit-script')
                <div
                    class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm"
                    x-data="{
                        doorWidth: '',
                        doorHeight: '',
                        items: {{ \Illuminate\Support\Js::from($fitItems) }},
                        warnings() {
                            const asked = this.doorWidth || this.doorHeight;
                            if (!asked) return [];
                            const lines = [];
                            this.items.forEach((item) => {
                                if (!item.dims) {
                                    lines.push(item.name + ': chưa có đủ số đo để kiểm tra.');
                                    return;
                                }
                                const door = mocanFits(item.dims, this.doorWidth, this.doorHeight);
                                if (door === false) lines.push(item.name + ' không lọt cửa vào.');
                            });
                            return lines;
                        }
                    }"
                >
                    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
                        <span class="grid size-8 place-items-center rounded-full bg-primary/10 text-primary font-bold text-sm">2</span>
                        <h2 class="font-display text-xl font-bold text-heading">Kiểm tra lối đi</h2>
                    </div>
                    <p class="text-xs text-muted mb-4">Nhập kích thước miệng cửa vào nhà. Cảnh báo không chặn đặt hàng — bạn vẫn có thể nhận hàng tháo kiện hoặc giao qua lối khác.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-[11px] text-muted">Cửa rộng (cm)<input type="number" min="1" x-model.number="doorWidth" class="mt-1 w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-sm text-heading"></label>
                        <label class="text-[11px] text-muted">Cửa cao (cm)<input type="number" min="1" x-model.number="doorHeight" class="mt-1 w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-sm text-heading"></label>
                    </div>
                    <ul class="mt-4 space-y-1 text-xs text-rose-600" x-show="warnings().length">
                        <template x-for="line in warnings()" :key="line">
                            <li x-text="line"></li>
                        </template>
                    </ul>
                </div>

                <!-- Payment Method Selection -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-ui-border pb-4 mb-6">
                        <span class="grid size-8 place-items-center rounded-full bg-primary/10 text-primary font-bold text-sm">3</span>
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
                                    <span class="font-semibold text-heading text-sm">MoMo - Thẻ ATM & Tài khoản (Sandbox)</span>
                                    <span class="rounded bg-pink-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-pink-600 dark:text-pink-400">Sandbox</span>
                                </div>
                                <p class="text-xs text-muted">Thanh toán thử bằng thẻ ATM trên cổng MoMo sandbox.</p>
                                <div class="mt-2 rounded-lg border border-pink-200/50 bg-pink-500/5 p-2.5 text-[11px] text-muted space-y-0.5">
                                    <p class="font-semibold text-pink-600 dark:text-pink-400">Thẻ ATM thử nghiệm (giao dịch thành công):</p>
                                    <p>• Số thẻ: <code class="font-mono font-bold text-heading">9704 0000 0000 0018</code></p>
                                    <p>• Tên chủ thẻ: <code class="font-mono font-bold text-heading">NGUYEN VAN A</code></p>
                                    <p>• Ngày phát hành: <code class="font-mono font-bold text-heading">03/07</code> · OTP: <code class="font-mono font-bold text-heading">OTP</code></p>
                                    <p>Hình thẻ hồng trên trang MoMo chỉ là ảnh mẫu. Nhập đúng số thẻ ở trên, không nhập số in trên hình.</p>
                                </div>
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
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm sticky top-28 space-y-6">
                    <h2 class="font-display text-xl font-bold text-heading border-b border-ui-border pb-4 flex items-center justify-between">
                        <span>Đơn hàng của bạn ({{ $items->count() }} sản phẩm)</span>
                        @if ($isBuyNow ?? false)
                            <span class="text-xs font-semibold text-primary bg-primary/10 border border-primary/20 px-2 py-0.5 rounded-lg">⚡ Mua nhanh</span>
                        @endif
                    </h2>

                    <!-- Items List -->
                    <div class="divide-y divide-ui-border max-h-72 overflow-y-auto pr-1 space-y-3">
                        @foreach ($items as $item)
                            <div class="flex items-center gap-4 pt-3 first:pt-0">
                                <div class="size-16 rounded-xl border border-ui-border bg-surface-alt overflow-hidden shrink-0">
                                    @if ($item->product->primaryImage)
                                        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" class="size-full object-cover">
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

                    <!-- Coupon Input Section in Checkout -->
                    <div class="p-4 rounded-2xl border border-ui-border bg-surface-alt/70 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg viewBox="0 0 24 24" class="size-4 text-primary" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                            <span class="text-xs font-bold text-heading">Mã ưu đãi / Voucher</span>
                        </div>

                        <div id="applied-coupons" class="space-y-2 {{ empty($coupons) ? 'hidden' : '' }}">
                            @foreach ($coupons as $appliedCoupon)
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-primary/10 border border-primary/20 text-xs">
                                    <div>
                                        <span class="font-mono font-bold text-primary bg-surface px-1.5 py-0.5 rounded border border-ui-border">{{ $appliedCoupon['code'] }}</span>
                                        @if (($appliedCoupon['amount'] ?? 0) > 0)
                                            <span class="text-emerald-700 dark:text-emerald-400 font-semibold ml-1">-{{ number_format($appliedCoupon['amount'], 0, ',', '.') }}₫</span>
                                        @elseif (($appliedCoupon['type'] ?? '') === 'shipping')
                                            <span class="text-emerald-700 dark:text-emerald-400 font-semibold ml-1">Giảm phí ship</span>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        data-remove-coupon="{{ $appliedCoupon['code'] }}"
                                        class="text-rose-600 hover:text-rose-800 font-semibold text-xs cursor-pointer"
                                    >
                                        Gỡ bỏ
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                id="checkout_coupon_input"
                                placeholder="Nhập thêm mã giảm giá..."
                                class="flex-1 rounded-xl border border-ui-border bg-surface px-3 py-2 text-xs font-semibold uppercase tracking-wider text-heading placeholder:normal-case placeholder:font-normal placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                            <button
                                type="button"
                                id="btn_apply_coupon"
                                onclick="applyCouponAjax()"
                                class="rounded-xl bg-primary hover:opacity-90 text-primary-foreground font-bold text-xs px-3.5 py-2 transition shadow-xs cursor-pointer shrink-0"
                            >
                                Áp dụng
                            </button>
                        </div>
                        <div id="coupon_msg" class="text-xs hidden"></div>

                        <div class="pt-2 border-t border-dashed border-ui-border/60">
                            <div class="flex flex-wrap gap-1.5 items-center">
                                <span class="text-[10px] text-muted font-medium">Có thể dùng nhiều mã:</span>
                                @foreach (['MOCAN10' => '10%', 'FREESHIP' => 'FreeShip', 'VIP500' => '500K'] as $cCode => $cLabel)
                                    <button
                                        type="button"
                                        onclick="document.getElementById('checkout_coupon_input').value='{{ $cCode }}'; applyCouponAjax();"
                                        class="rounded-lg border border-primary/30 bg-primary/5 hover:bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary transition cursor-pointer"
                                    >
                                        {{ $cCode }} ({{ $cLabel }})
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Price Calculations -->
                    <div class="space-y-3 pt-4 border-t border-ui-border text-sm">
                        <div class="flex justify-between text-muted">
                            <span>Tạm tính</span>
                            <span class="font-semibold text-heading">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                        </div>

                        <div id="checkout-discount-row" class="flex justify-between text-emerald-700 dark:text-emerald-400 font-semibold {{ $discountAmount > 0 ? '' : 'hidden' }}">
                            <span class="flex items-center gap-1">
                                <span>Giảm giá</span>
                                <span id="checkout-coupon-badge" class="font-mono text-xs bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 px-1.5 py-0.5 rounded {{ empty($coupons) ? 'hidden' : '' }}">{{ empty($coupons) ? '' : '('.collect($coupons)->pluck('code')->implode(', ').')' }}</span>
                            </span>
                            <span id="checkout-discount-amount">-{{ number_format($discountAmount, 0, ',', '.') }}₫</span>
                        </div>

                        <div class="flex justify-between text-muted">
                            <span>Phí vận chuyển</span>
                            <span class="font-semibold" data-id="shipping-fee-display">
                                @if ($hasCalculatedShipping)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                                        {{ number_format($payableShipping, 0, ',', '.') }}₫
                                    </span>
                                @else
                                    <span class="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">
                                        Tính phí vận chuyển
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between items-baseline pt-4 border-t border-ui-border">
                            <div>
                                <span class="font-bold text-heading block">Tổng thanh toán</span>
                                <span class="text-[11px] text-muted" id="total-note">
                                    @if ($hasCalculatedShipping)
                                        (Đã bao gồm phí ship và VAT)
                                    @else
                                        (Tạm tính, chưa bao gồm phí ship)
                                    @endif
                                </span>
                            </div>
                            <span class="font-display text-2xl font-bold text-heading" data-id="total-price-display">
                                {{ number_format($totalPrice, 0, ',', '.') }}₫
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary hover:opacity-90 px-6 py-4 text-sm font-bold text-primary-foreground shadow-lg transition hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
                        >
                            <span>Xác nhận đặt hàng</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>

                    <p class="text-center text-[11px] text-muted">
                        Bằng việc bấm đặt hàng, bạn đồng ý với Điều khoản mua hàng & Bảo mật của Mộc An.
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function formatCheckoutVnd(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.max(0, Math.round(Number(amount) || 0))) + '₫';
}

function escapeCouponText(value) {
    return String(value).replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));
}

function showCouponMessage(text, ok) {
    const msg = document.getElementById('coupon_msg');
    if (!msg) return;
    msg.className = 'text-xs block mt-1 ' + (ok ? 'text-emerald-600' : 'text-rose-600');
    msg.textContent = text;
}

function paintCheckoutQuote(body) {
    const coupons = Array.isArray(body.coupons) ? body.coupons : [];
    const list = document.getElementById('applied-coupons');
    if (list) {
        if (!coupons.length) {
            list.classList.add('hidden');
            list.innerHTML = '';
        } else {
            list.classList.remove('hidden');
            list.innerHTML = coupons.map((coupon) => {
                const amount = Number(coupon.amount || 0);
                const extra = amount > 0
                    ? `<span class="text-emerald-700 dark:text-emerald-400 font-semibold ml-1">-${formatCheckoutVnd(amount)}</span>`
                    : (coupon.type === 'shipping' ? '<span class="text-emerald-700 dark:text-emerald-400 font-semibold ml-1">Giảm phí ship</span>' : '');
                return `<div class="flex items-center justify-between p-2.5 rounded-xl bg-primary/10 border border-primary/20 text-xs">
                    <div>
                        <span class="font-mono font-bold text-primary bg-surface px-1.5 py-0.5 rounded border border-ui-border">${escapeCouponText(coupon.code)}</span>
                        ${extra}
                    </div>
                    <button type="button" data-remove-coupon="${escapeCouponText(coupon.code)}" class="text-rose-600 hover:text-rose-800 font-semibold text-xs cursor-pointer">Gỡ bỏ</button>
                </div>`;
            }).join('');
        }
    }

    const discountRow = document.getElementById('checkout-discount-row');
    const discountAmount = document.getElementById('checkout-discount-amount');
    const badge = document.getElementById('checkout-coupon-badge');
    const discount = Number(body.discount_amount || 0);
    if (discountRow) discountRow.classList.toggle('hidden', discount <= 0);
    if (discountAmount) discountAmount.textContent = '-' + formatCheckoutVnd(discount);
    if (badge) {
        if (coupons.length) {
            badge.textContent = '(' + coupons.map(c => c.code).join(', ') + ')';
            badge.classList.remove('hidden');
        } else {
            badge.textContent = '';
            badge.classList.add('hidden');
        }
    }

    const feeEl = document.querySelector('[data-id="shipping-fee-display"]');
    if (feeEl) {
        if (body.has_calculated_shipping) {
            feeEl.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold">' + formatCheckoutVnd(body.payable_shipping) + '</span>';
        } else {
            feeEl.innerHTML = '<span class="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">Tính phí vận chuyển</span>';
        }
    }

    const totalEl = document.querySelector('[data-id="total-price-display"]');
    if (totalEl && body.formatted_total) totalEl.textContent = body.formatted_total;

    const noteEl = document.getElementById('total-note');
    if (noteEl) {
        noteEl.textContent = body.has_calculated_shipping
            ? '(Đã bao gồm phí ship và VAT)'
            : '(Tạm tính, chưa bao gồm phí ship)';
    }
}

function applyCouponAjax() {
    const input = document.getElementById('checkout_coupon_input');
    const btn = document.getElementById('btn_apply_coupon');
    const code = input ? input.value.trim() : '';

    if (!code) {
        showCouponMessage('Vui lòng nhập mã giảm giá.', false);
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.textContent = '...';
    }

    fetch('{{ route('cart.coupon.apply') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ coupon_code: code })
    })
    .then(res => res.json().then(data => ({ status: res.status, body: data })))
    .then(res => {
        if (res.status === 200 && res.body.success) {
            if (input) input.value = '';
            paintCheckoutQuote(res.body);
            showCouponMessage(res.body.message || 'Đã áp dụng mã giảm giá.', true);
        } else {
            showCouponMessage(res.body.message || 'Mã giảm giá không hợp lệ.', false);
        }
    })
    .catch(() => showCouponMessage('Không áp dụng được mã giảm giá.', false))
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Áp dụng';
        }
    });
}

function removeCouponAjax(code) {
    fetch('{{ route('cart.coupon.remove') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ coupon_code: code || '' })
    })
    .then(res => res.json())
    .then(body => {
        if (!body.success) {
            showCouponMessage(body.message || 'Không gỡ được mã giảm giá.', false);
            return;
        }
        paintCheckoutQuote(body);
        showCouponMessage(body.message || 'Đã gỡ mã giảm giá.', true);
    })
    .catch(() => showCouponMessage('Không gỡ được mã giảm giá.', false));
}

document.getElementById('applied-coupons')?.addEventListener('click', function (event) {
    const button = event.target.closest('[data-remove-coupon]');
    if (!button) return;
    removeCouponAjax(button.dataset.removeCoupon || '');
});


// ===== GHN Shipping Fee Calculator =====
(function () {
    const provinceSelect  = document.getElementById('province_id');
    const provinceName    = document.getElementById('province_name');
    const districtSelect  = document.getElementById('district_id');
    const districtName    = document.getElementById('district_name');
    const wardSelect      = document.getElementById('ward_code');
    const wardName        = document.getElementById('ward_name');
    const shippingFeeMsg  = document.getElementById('shipping-fee-msg');

    // Shipping fee display elements (in order summary)
    const shippingFeeEl   = document.querySelector('[data-shipping-fee]');

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showMsg(msg, isError = false) {
        if (!shippingFeeMsg) return;
        shippingFeeMsg.textContent = msg;
        shippingFeeMsg.classList.remove('hidden', 'text-emerald-600', 'text-rose-500');
        shippingFeeMsg.classList.add(isError ? 'text-rose-500' : 'text-emerald-600');
    }

    // Load provinces on page load
    fetch('/api/shipping/ghn/provinces')
        .then(r => r.json())
        .then(({ data }) => {
            if (!data || !provinceSelect) return;
            data.forEach(p => {
                const opt = new Option(p.name, p.id);
                opt.dataset.name = p.name;
                provinceSelect.appendChild(opt);
            });
            // Restore old value
            const oldProvince = '{{ old("province_id") }}';
            if (oldProvince) {
                provinceSelect.value = oldProvince;
                provinceSelect.dispatchEvent(new Event('change'));
            }
        })
        .catch(() => showMsg('Không thể tải danh sách tỉnh/thành.', true));

    provinceSelect?.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        provinceName.value = selectedOpt?.dataset.name || selectedOpt?.text || '';

        // Reset district & ward
        districtSelect.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
        wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
        districtSelect.disabled = true;
        wardSelect.disabled = true;

        const feeEl = document.querySelector('[data-id="shipping-fee-display"]');
        if (feeEl) {
            feeEl.innerHTML = '<span class="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">Tính phí vận chuyển</span>';
        }
        const noteEl = document.getElementById('total-note');
        if (noteEl) {
            noteEl.textContent = '(Tạm tính, chưa bao gồm phí ship)';
        }

        const provinceId = this.value;
        if (!provinceId) return;

        fetch(`/api/shipping/ghn/districts/${provinceId}`)
            .then(r => r.json())
            .then(({ data }) => {
                if (!data) return;
                data.forEach(d => {
                    const opt = new Option(d.name, d.id);
                    opt.dataset.name = d.name;
                    districtSelect.appendChild(opt);
                });
                districtSelect.disabled = false;
                const oldDistrict = '{{ old("district_id") }}';
                if (oldDistrict) {
                    districtSelect.value = oldDistrict;
                    districtSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(() => showMsg('Không thể tải danh sách quận/huyện.', true));
    });

    districtSelect?.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        districtName.value = selectedOpt?.dataset.name || selectedOpt?.text || '';

        // Reset fee display while selecting
        const feeEl = document.querySelector('[data-id="shipping-fee-display"]');
        if (feeEl) {
            feeEl.innerHTML = '<span class="text-xs text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20">Đang chọn địa chỉ...</span>';
        }
        const noteEl = document.getElementById('total-note');
        if (noteEl) {
            noteEl.textContent = '(Tạm tính, chưa bao gồm phí ship)';
        }

        const districtId = this.value;
        if (!districtId) return;

        fetch(`/api/shipping/ghn/wards/${districtId}`)
            .then(r => r.json())
            .then(({ data }) => {
                if (!data) return;
                data.forEach(w => {
                    const opt = new Option(w.name, w.code);
                    opt.dataset.name = w.name;
                    wardSelect.appendChild(opt);
                });
                wardSelect.disabled = false;
                const oldWard = '{{ old("ward_code") }}';
                if (oldWard) {
                    wardSelect.value = oldWard;
                    wardSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(() => showMsg('Không thể tải danh sách phường/xã.', true));
    });

    wardSelect?.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        wardName.value = selectedOpt?.dataset.name || selectedOpt?.text || '';

        const districtId = districtSelect.value;
        const wardCode   = this.value;
        if (!districtId || !wardCode) return;

        showMsg('Đang tính phí vận chuyển GHN...');

        fetch('/api/shipping/ghn/calculate-fee', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ district_id: districtId, ward_code: wardCode }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMsg('Phí vận chuyển GHN: ' + data.formatted_fee);
                const feeEl = document.querySelector('[data-id="shipping-fee-display"]');
                if (feeEl) {
                    feeEl.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold">' + data.formatted_fee + '</span>';
                }
                const totalEl = document.querySelector('[data-id="total-price-display"]');
                if (totalEl) {
                    totalEl.textContent = data.formatted_total;
                }
                const noteEl = document.getElementById('total-note');
                if (noteEl) {
                    noteEl.textContent = '(Đã bao gồm phí ship và VAT)';
                }
            } else {
                showMsg(data.message || 'Không thể tính phí vận chuyển.', true);
            }
        })
        .catch(() => showMsg('Không thể tính phí vận chuyển, sẽ cập nhật khi đặt hàng.', true));
    });

    // Quick fill from saved addresses
    document.querySelectorAll('.saved-address-chip').forEach(btn => {
        btn.addEventListener('click', function () {
            const address = this.dataset.address;
            const textarea = document.getElementById('shipping_address');
            if (textarea && address) {
                textarea.value = address;
                textarea.focus();

                document.querySelectorAll('.saved-address-chip').forEach(b => {
                    b.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
                });
                this.classList.add('border-primary', 'bg-primary/10', 'text-primary');

                if (provinceSelect) {
                    for (let i = 0; i < provinceSelect.options.length; i++) {
                        const opt = provinceSelect.options[i];
                        if (opt.value && opt.dataset.name && address.toLowerCase().includes(opt.dataset.name.toLowerCase())) {
                            if (provinceSelect.value !== opt.value) {
                                provinceSelect.value = opt.value;
                                provinceSelect.dispatchEvent(new Event('change'));
                            }
                            break;
                        }
                    }
                }
            }
        });
    });
})();
</script>
@endsection