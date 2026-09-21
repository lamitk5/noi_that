@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code . ' | Admin Mộc An')
@section('header-title', 'Chi tiết đơn hàng #' . $order->order_code)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs text-muted mb-1" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-heading transition">Bảng điều khiển</a>
                <span>/</span>
                <a href="{{ route('admin.orders.index') }}" class="hover:text-heading transition">Đơn hàng</a>
                <span>/</span>
                <span class="text-heading font-medium" aria-current="page">#{{ $order->order_code }}</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold font-display text-heading">
                Đơn hàng #{{ $order->order_code }}
            </h1>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-heading transition">
            <span>← Về danh sách đơn</span>
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300" role="alert">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300" role="alert">
            <div class="flex items-center gap-2">
                <svg viewBox="0 0 24 24" class="size-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @php
        $statusClasses = [
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40',
            'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/40',
            'packed' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/40',
            'shipping' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900/40',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40',
            'canceled' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40',
        ];
        $methodNames = [
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'vnpay' => 'Cổng VNPAY Sandbox',
            'momo' => 'Ví MoMo Sandbox',
        ];
    @endphp

    <!-- Top Workflow Actions Card -->
    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-muted block mb-1">Trạng thái hiện tại</span>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold border {{ $statusClasses[$order->order_status] ?? 'bg-surface-alt text-body border-ui-border' }}">
                        {{ $order->statusLabel() }}
                    </span>
                    <span class="text-xs text-muted">
                        Cập nhật lúc {{ $order->updated_at ? $order->updated_at->format('d/m/Y H:i') : '' }}
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                {{-- Bank Transfer Manual Confirmation Action --}}
                @if ($order->payment_method === 'bank_transfer' && $order->payment_status !== 'paid' && $order->order_status !== 'canceled')
                    <form method="POST" action="{{ route('admin.orders.mark-paid', $order->order_code) }}">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700 shadow-sm cursor-pointer"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Xác nhận đã nhận tiền CK</span>
                        </button>
                    </form>
                @endif

                {{-- Forward Progression Actions --}}
                @if (in_array('confirmed', $allowedStatuses, true))
                    <form method="POST" action="{{ route('admin.orders.update-status', $order->order_code) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-foreground transition hover:opacity-95 shadow-sm cursor-pointer"
                        >
                            <span>Xác nhận đơn hàng</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                @endif

                @if (in_array('packed', $allowedStatuses, true))
                    <form method="POST" action="{{ route('admin.orders.update-status', $order->order_code) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="packed">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-foreground transition hover:opacity-95 shadow-sm cursor-pointer"
                        >
                            <span>Xác nhận đã đóng gói</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                @endif

                @if (in_array('shipping', $allowedStatuses, true))
                    <form method="POST" action="{{ route('admin.orders.update-status', $order->order_code) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="shipping">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-foreground transition hover:opacity-95 shadow-sm cursor-pointer"
                        >
                            <span>Bắt đầu vận chuyển</span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                @endif

                @if (in_array('completed', $allowedStatuses, true))
                    <form method="POST" action="{{ route('admin.orders.update-status', $order->order_code) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700 shadow-sm cursor-pointer"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Xác nhận đã giao hàng</span>
                        </button>
                    </form>
                @endif

                {{-- Cancellation Action --}}
                @if (in_array('canceled', $allowedStatuses, true))
                    <form
                        method="POST"
                        action="{{ route('admin.orders.update-status', $order->order_code) }}"
                        onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Thao tác này sẽ hoàn lại toàn bộ số lượng tồn kho cho các sản phẩm trong đơn.')"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="canceled">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 transition cursor-pointer"
                        >
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span>Hủy đơn hàng</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Items & Pricing -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Items Table -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs">
                <h2 class="font-display text-base font-bold text-heading pb-3 border-b border-ui-border mb-4">
                    Sản phẩm trong đơn ({{ $order->items->count() }})
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
                                    <h3 class="font-semibold text-heading text-xs truncate">{{ $item->product_name }}</h3>
                                    <p class="text-[11px] text-muted">{{ $item->variant_info }}</p>
                                    <p class="text-[11px] text-muted mt-0.5">
                                        {{ $item->quantity }} × {{ number_format((float) $item->price, 0, ',', '.') }}₫
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-bold font-display text-heading text-xs">
                                    {{ number_format((float) $item->price * $item->quantity, 0, ',', '.') }}₫
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Totals -->
                <div class="mt-6 pt-4 border-t border-ui-border space-y-2 text-xs">
                    <div class="flex justify-between text-muted">
                        <span>Tiền hàng:</span>
                        <span class="font-semibold text-heading">
                            {{ number_format((float) $order->total_price - (float) $order->shipping_fee, 0, ',', '.') }}₫
                        </span>
                    </div>
                    <div class="flex justify-between text-muted">
                        <span>Phí vận chuyển:</span>
                        <span class="font-semibold text-heading">
                            {{ (float) $order->shipping_fee > 0 ? number_format((float) $order->shipping_fee, 0, ',', '.') . '₫' : 'Miễn phí' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-baseline pt-3 border-t border-ui-border text-sm">
                        <span class="font-bold text-heading">Tổng thanh toán:</span>
                        <span class="font-display text-xl font-bold text-primary">
                            {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Transactions History -->
            @if ($order->paymentTransactions->count() > 0)
                <div class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs">
                    <h2 class="font-display text-base font-bold text-heading pb-3 border-b border-ui-border mb-4">
                        Lịch sử cổng thanh toán ({{ $order->paymentTransactions->count() }} giao dịch)
                    </h2>

                    <div class="divide-y divide-ui-border text-xs">
                        @foreach ($order->paymentTransactions as $txn)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-bold text-heading uppercase">{{ $txn->provider }}</div>
                                    <div class="text-[11px] font-mono text-muted">Ref: {{ $txn->provider_reference }}</div>
                                    @if ($txn->provider_transaction_id)
                                        <div class="text-[11px] font-mono text-muted">Mã GD cổng: {{ $txn->provider_transaction_id }}</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold block {{ $txn->status === 'success' ? 'text-emerald-600 dark:text-emerald-400' : ($txn->status === 'failed' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400') }}">
                                        {{ strtoupper($txn->status) }}
                                    </span>
                                    <span class="text-[11px] text-muted block mt-0.5">
                                        {{ $txn->created_at ? $txn->created_at->format('d/m/Y H:i') : '' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right: Customer & Delivery Info -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Customer Information Card -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs text-xs space-y-4">
                <h2 class="font-display text-base font-bold text-heading pb-3 border-b border-ui-border">
                    Thông tin người nhận
                </h2>

                <div class="space-y-3">
                    <div>
                        <span class="text-muted block text-[11px]">Họ tên:</span>
                        <span class="font-semibold text-heading">{{ $order->customer_name }}</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[11px]">Số điện thoại:</span>
                        <span class="font-semibold text-heading">{{ $order->customer_phone }}</span>
                    </div>
                    @if ($order->customer_email)
                        <div>
                            <span class="text-muted block text-[11px]">Email:</span>
                            <span class="font-semibold text-heading">{{ $order->customer_email }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="text-muted block text-[11px]">Địa chỉ giao hàng:</span>
                        <span class="font-semibold text-heading leading-relaxed">{{ $order->shipping_address }}</span>
                    </div>
                    @if ($order->note)
                        <div>
                            <span class="text-muted block text-[11px]">Ghi chú của khách:</span>
                            <p class="font-medium text-heading mt-0.5 p-2.5 rounded-xl bg-surface-alt border border-ui-border leading-relaxed">
                                {{ $order->note }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payment Details Card -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs text-xs space-y-3">
                <h2 class="font-display text-base font-bold text-heading pb-3 border-b border-ui-border">
                    Thông tin thanh toán
                </h2>

                <div class="space-y-3">
                    <div>
                        <span class="text-muted block text-[11px]">Phương thức:</span>
                        <span class="font-semibold text-heading">{{ $methodNames[$order->payment_method] ?? strtoupper($order->payment_method) }}</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[11px]">Trạng thái thanh toán:</span>
                        @if ($order->payment_status === 'paid')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40">
                                Đã thanh toán
                            </span>
                        @elseif ($order->payment_status === 'failed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40">
                                Thất bại
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40">
                                Chờ thanh toán
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shipment & Tracking Card -->
            @if ($order->tracking_code || $order->order_status === 'shipping' || $order->order_status === 'completed')
                <div class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs text-xs space-y-3">
                    <h2 class="font-display text-base font-bold text-heading pb-3 border-b border-ui-border">
                        Thông tin vận chuyển
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <span class="text-muted block text-[11px]">Đơn vị vận chuyển:</span>
                            <span class="font-bold text-heading uppercase">{{ $order->shipping_carrier ?? 'GHN Express' }}</span>
                        </div>
                        @if ($order->tracking_code)
                            <div>
                                <span class="text-muted block text-[11px]">Mã vận đơn:</span>
                                <code class="px-2 py-0.5 rounded bg-surface-alt border border-ui-border font-mono text-xs text-primary font-bold inline-block mt-0.5">
                                    {{ $order->tracking_code }}
                                </code>
                            </div>
                        @endif
                        @if ($order->shipped_at)
                            <div>
                                <span class="text-muted block text-[11px]">Thời gian gửi hàng:</span>
                                <span class="text-heading font-medium">{{ $order->shipped_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
