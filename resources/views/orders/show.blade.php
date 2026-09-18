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
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-heading transition">
                <span>← Trở về danh sách</span>
            </a>
        </div>

        <div class="space-y-6">
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
                                'shipping' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/40',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40',
                                'canceled' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40',
                            ];
                            $statusLabels = [
                                'pending' => 'Chờ xử lý',
                                'confirmed' => 'Đã xác nhận',
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
                            {{ $order->payment_method === 'cod' ? 'COD' : 'Chuyển khoản' }}
                        </span>
                        <span class="text-[11px] text-muted">
                            ({{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }})
                        </span>
                    </div>
                    <div>
                        <span class="text-muted block mb-1">Tổng tiền</span>
                        <span class="font-display text-base font-bold text-primary">
                            {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>
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
