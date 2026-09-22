@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code . ' | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs / Top navigation -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <nav class="mb-2 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
                    <span>/</span>
                    <a href="{{ route('orders.index') }}" class="hover:text-heading transition">Đơn hàng</a>
                    <span>/</span>
                    <span class="text-heading font-medium" aria-current="page">#{{ $order->order_code }}</span>
                </nav>
                <h1 class="font-display text-2xl sm:text-3xl font-semibold text-heading">
                    Chi tiết đơn hàng #{{ $order->order_code }}
                </h1>
                <p class="mt-1 text-xs text-muted">
                    Đặt lúc {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-heading transition">
                    <span>← Trở về danh sách</span>
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 p-4 text-sm font-medium text-rose-600 dark:text-rose-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Quick Actions Bar -->
        <div class="mb-6 rounded-2xl border border-ui-border bg-surface p-4 shadow-xs flex flex-wrap items-center justify-between gap-3" x-data="{ copied: false }">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $order->order_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-ui-border bg-surface-alt hover:bg-surface text-xs font-semibold text-heading transition"
                >
                    <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <span x-text="copied ? 'Đã sao chép!' : 'Sao chép mã đơn'"></span>
                </button>

                <a
                    href="{{ route('orders.print', $order->order_code) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-ui-border bg-surface-alt hover:bg-surface text-xs font-semibold text-heading transition"
                >
                    <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    <span>In hóa đơn</span>
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (in_array($order->payment_method, ['vnpay', 'momo']) && $order->payment_status !== 'paid' && $order->order_status === 'pending')
                    <form action="{{ route('orders.retry-payment', $order->order_code) }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-primary text-xs font-bold text-primary-foreground shadow-xs hover:opacity-95 transition"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            <span>Thanh toán lại</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('orders.buy-again', $order->order_code) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-primary/30 bg-primary/10 hover:bg-primary/20 text-xs font-bold text-primary transition"
                    >
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                        <span>Mua lại</span>
                    </button>
                </form>

                @if ($order->order_status === 'pending' && $order->payment_status !== 'paid')
                    <form action="{{ route('orders.cancel', $order->order_code) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không? Tồn kho sản phẩm sẽ được hoàn trả.');">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-xs font-bold text-rose-600 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-400 transition"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                            <span>Hủy đơn</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <!-- Order Timeline Card -->
            @if ($order->order_status === 'canceled')
                <div class="rounded-3xl border border-rose-200 bg-rose-50/70 dark:bg-rose-950/20 dark:border-rose-900/40 p-6 sm:p-8">
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-2xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-display text-base font-bold text-rose-900 dark:text-rose-200">Đơn hàng đã bị hủy</h2>
                            <p class="text-xs text-rose-700 dark:text-rose-400 mt-1">Đơn hàng #{{ $order->order_code }} đã kết thúc ở trạng thái hủy. Tồn kho sản phẩm đã được hoàn lại đầy đủ.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                    <h2 class="font-display text-sm font-bold uppercase tracking-wider text-muted mb-6">Tiến trình đơn hàng</h2>
                    @php
                        $timelineSteps = [
                            'pending' => 'Chờ xử lý',
                            'confirmed' => 'Đã xác nhận',
                            'packed' => 'Đã đóng gói',
                            'shipping' => 'Đang giao hàng',
                            'completed' => 'Hoàn tất',
                        ];
                        $stepKeys = array_keys($timelineSteps);
                        $currentIndex = array_search($order->order_status, $stepKeys);
                        if ($currentIndex === false) {
                            $currentIndex = 0;
                        }
                    @endphp

                    <div class="relative">
                        <!-- Progress line for desktop -->
                        <div class="hidden sm:block absolute top-5 left-12 right-12 h-1 bg-surface-alt rounded-full -z-0">
                            <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: {{ count($stepKeys) > 1 ? ($currentIndex / (count($stepKeys) - 1)) * 100 : 0 }}%;"></div>
                        </div>

                        <!-- Steps -->
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 relative z-10">
                            @foreach ($timelineSteps as $stepKey => $stepLabel)
                                @php
                                    $idx = array_search($stepKey, $stepKeys);
                                    $isPassed = $idx <= $currentIndex;
                                    $isCurrent = $idx === $currentIndex;
                                @endphp
                                <div class="flex sm:flex-col items-center gap-3 sm:text-center">
                                    <div class="size-10 rounded-full flex items-center justify-center font-bold text-xs transition-colors shrink-0 {{ $isCurrent ? 'bg-primary text-primary-foreground ring-4 ring-primary/20 shadow-md' : ($isPassed ? 'bg-primary/90 text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border') }}">
                                        @if ($isPassed && !$isCurrent)
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <span>{{ $idx + 1 }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold {{ $isCurrent ? 'text-primary font-bold' : ($isPassed ? 'text-heading' : 'text-muted') }}">
                                            {{ $stepLabel }}
                                        </p>
                                        @if ($isCurrent)
                                            <span class="inline-block sm:hidden text-[10px] text-primary font-medium">(Hiện tại)</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                    </div>

                    @if ($order->tracking_code)
                        <div class="mt-6 pt-6 border-t border-ui-border flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-surface-alt p-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Đơn vị vận chuyển & Mã vận đơn</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="font-bold text-heading text-sm">{{ strtoupper($order->shipping_carrier ?? 'GHN') }} Express:</span>
                                    <code class="px-2 py-0.5 rounded bg-surface border border-ui-border font-mono text-xs text-primary font-bold">{{ $order->tracking_code }}</code>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-xs text-muted">Đang trên đường giao đến bạn</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Order Status & Summary Card -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 text-xs">
                    <div>
                        <span class="text-muted block mb-1">Mã đơn hàng</span>
                        <span class="font-display text-base font-bold text-heading">#{{ $order->order_code }}</span>
                    </div>
                    <div>
                        <span class="text-muted block mb-1">Trạng thái đơn</span>
                        @php
                            $statusClasses = [
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40',
                                'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/40',
                                'packed' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900/40',
                                'shipping' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/40',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40',
                                'canceled' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40',
                            ];
                            $statusLabels = [
                                'pending' => 'Chờ xử lý',
                                'confirmed' => 'Đã xác nhận',
                                'packed' => 'Đã đóng gói',
                                'shipping' => 'Đang giao',
                                'completed' => 'Hoàn tất',
                                'canceled' => 'Đã hủy',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusClasses[$order->order_status] ?? 'bg-surface-alt text-body border-ui-border' }}">
                            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </div>
                    <div>
                        <span class="text-muted block mb-1">Thanh toán</span>
                        <span class="font-medium text-heading">
                            @php
                                $methodNames = [
                                    'cod' => 'COD',
                                    'bank_transfer' => 'Chuyển khoản',
                                    'vnpay' => 'VNPAY',
                                    'momo' => 'MoMo',
                                ];
                                $paymentStatusLabels = [
                                    'paid' => 'Đã thanh toán',
                                    'pending' => 'Chờ thanh toán',
                                    'failed' => 'Thanh toán thất bại',
                                ];
                            @endphp
                            {{ $methodNames[$order->payment_method] ?? strtoupper($order->payment_method) }}
                        </span>
                        <span class="text-[11px] block mt-0.5 {{ $order->payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : ($order->payment_status === 'failed' ? 'text-rose-500 font-semibold' : 'text-amber-600 dark:text-amber-400') }}">
                            ({{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }})
                        </span>
                    </div>
                    <div>
                        <span class="text-muted block mb-1">Tổng tiền</span>
                        <span class="font-display text-base font-bold text-primary">
                            {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>

                @if (in_array($order->payment_method, ['vnpay', 'momo']) && $order->payment_status !== 'paid' && $order->order_status !== 'canceled')
                    <div class="mt-6 pt-5 border-t border-ui-border flex flex-col sm:flex-row items-center justify-between gap-4 bg-surface-alt/50 -mx-6 -mb-6 p-6 rounded-b-3xl">
                        <div class="text-xs text-muted">
                            <span class="font-bold text-heading block text-sm mb-0.5">Đơn hàng chưa được thanh toán</span>
                            <span>Bạn có thể hoàn tất thanh toán ngay bằng cổng {{ strtoupper($order->payment_method) }}.</span>
                        </div>
                        <form method="POST" action="{{ route('payments.' . $order->payment_method . '.create', $order->order_code) }}" class="shrink-0 w-full sm:w-auto">
                            @csrf
                            <button
                                type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-md shadow-primary/20 transition hover:opacity-95 hover:-translate-y-0.5 cursor-pointer"
                            >
                                <span>Thanh toán lại qua {{ strtoupper($order->payment_method) }}</span>
                                <span aria-hidden="true">→</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                <h2 class="font-display text-lg font-bold text-heading mb-4 pb-3 border-b border-ui-border">
                    Danh sách sản phẩm
                </h2>
                <div class="divide-y divide-ui-border">
                    @foreach ($order->items as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="size-14 rounded-xl border border-ui-border bg-surface-alt overflow-hidden shrink-0">
                                    @if ($item->variant && $item->variant->product && $item->variant->product->primaryImage)
                                        <img src="{{ $item->variant->product->primaryImage->image_path }}" alt="{{ $item->product_name }}" class="size-full object-cover">
                                    @else
                                        <div class="size-full flex items-center justify-center text-muted">
                                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-medium text-heading text-sm truncate">{{ $item->product_name }}</h3>
                                    <p class="text-xs text-muted">{{ $item->variant_info }}</p>
                                    @if ($order->order_status === 'completed' && $order->payment_status === 'paid' && $item->variant && $item->variant->product)
                                        <div class="mt-1.5">
                                            <a
                                                href="{{ route('products.show', $item->variant->product->slug) }}#reviews"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline"
                                            >
                                                <svg class="size-3 text-amber-500 fill-amber-400" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                <span>Đánh giá sản phẩm</span>
                                            </a>
                                        </div>
                                    @endif
                                    <p class="text-xs text-muted mt-0.5 sm:hidden">
                                        {{ $item->quantity }} × {{ number_format((float) $item->price, 0, ',', '.') }}₫
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="hidden sm:block text-xs text-muted mb-0.5">
                                    {{ $item->quantity }} × {{ number_format((float) $item->price, 0, ',', '.') }}₫
                                </span>
                                <span class="font-display text-sm font-bold text-heading">
                                    {{ number_format((float) $item->price * $item->quantity, 0, ',', '.') }}₫
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Price Totals -->
                <div class="mt-6 pt-4 border-t border-ui-border space-y-2 text-xs">
                    <div class="flex justify-between text-muted">
                        <span>Tiền hàng</span>
                        <span class="font-semibold text-heading">
                            {{ number_format((float) $order->total_price - (float) $order->shipping_fee, 0, ',', '.') }}₫
                        </span>
                    </div>
                    <div class="flex justify-between text-muted">
                        <span>Phí vận chuyển</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ (float) $order->shipping_fee > 0 ? number_format((float) $order->shipping_fee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-baseline pt-3 border-t border-ui-border text-sm">
                        <span class="font-bold text-heading">Tổng cộng</span>
                        <span class="font-display text-xl font-bold text-primary">
                            {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipping & Customer Info Card -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm">
                <h2 class="font-display text-lg font-bold text-heading mb-4 pb-3 border-b border-ui-border">
                    Thông tin giao hàng
                </h2>
                <div class="grid sm:grid-cols-2 gap-4 text-xs text-muted">
                    <div>
                        <span class="font-semibold text-heading">Người nhận:</span> {{ $order->customer_name }}
                    </div>
                    <div>
                        <span class="font-semibold text-heading">Số điện thoại:</span> {{ $order->customer_phone }}
                    </div>
                    @if ($order->customer_email)
                        <div>
                            <span class="font-semibold text-heading">Email:</span> {{ $order->customer_email }}
                        </div>
                    @endif
                    <div>
                        <span class="font-semibold text-heading">Địa chỉ nhận hàng:</span> {{ $order->shipping_address }}
                    </div>
                    @if ($order->note)
                        <div class="sm:col-span-2">
                            <span class="font-semibold text-heading">Ghi chú:</span> {{ $order->note }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
