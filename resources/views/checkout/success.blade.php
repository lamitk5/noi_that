@extends('layouts.app')

@section('title', 'Đặt hàng thành công | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-3xl mx-auto">
        <!-- Success Card -->
        <div class="rounded-3xl border border-ui-border bg-surface p-8 sm:p-12 shadow-xl shadow-black/5 text-center">
            <div class="size-20 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 grid place-items-center mx-auto mb-6">
                <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>

            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Xác nhận hoàn tất</span>
            <h1 class="mt-2 font-display text-3xl font-bold text-heading sm:text-4xl">Đặt hàng thành công!</h1>
            <p class="mt-3 text-sm text-muted max-w-lg mx-auto">
                Cảm ơn bạn đã lựa chọn nội thất Mộc An. Mã đơn hàng của bạn là <strong class="text-heading font-semibold">#{{ $order->order_code }}</strong>.
            </p>

            <!-- Order Summary Box -->
            <div class="mt-8 rounded-2xl border border-ui-border bg-surface-alt p-6 text-left space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-ui-border pb-4">
                    <div>
                        <span class="text-xs text-muted block">Mã đơn hàng</span>
                        <span class="font-display text-lg font-bold text-heading">#{{ $order->order_code }}</span>
                    </div>
                    <div class="sm:text-right">
                        <span class="text-xs text-muted block">Tổng thanh toán</span>
                        <span class="font-display text-xl font-bold text-primary">
                            {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                        </span>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 text-xs text-muted">
                    <div>
                        <span class="font-semibold text-heading">Người nhận:</span> {{ $order->customer_name }} ({{ $order->customer_phone }})
                    </div>
                    <div>
                        <span class="font-semibold text-heading">Địa chỉ giao:</span> {{ $order->shipping_address }}
                    </div>
                    <div>
                        <span class="font-semibold text-heading">Hình thức:</span>
                        {{ $order->payment_method === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng' }}
                    </div>
                    <div>
                        <span class="font-semibold text-heading">Trạng thái:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            Chờ xác nhận
                        </span>
                    </div>
                </div>

                @if ($order->payment_method === 'bank_transfer')
                    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 text-xs text-blue-900 dark:border-blue-900/40 dark:bg-blue-950/30 dark:text-blue-300 mt-4 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-blue-700 dark:text-blue-400">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>Thông tin chuyển khoản ngân hàng:</span>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-2 pt-1 font-mono text-[13px]">
                            <div><strong>Ngân hàng:</strong> {{ config('shop.bank_transfer.bank_name') }}</div>
                            <div><strong>Số tài khoản:</strong> {{ config('shop.bank_transfer.account_number') }}</div>
                            <div><strong>Chủ tài khoản:</strong> {{ config('shop.bank_transfer.account_holder') }}</div>
                            <div><strong>Nội dung CK:</strong> <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $order->order_code }}</span></div>
                        </div>
                        <p class="text-[11px] text-muted pt-1">
                            * Vui lòng chuyển khoản đúng nội dung là Mã đơn hàng để Mộc An xác nhận tự động nhanh nhất.
                        </p>
                    </div>
                @else
                    <div class="rounded-xl border border-ui-border bg-surface p-3.5 text-xs text-muted">
                        💡 Bạn sẽ thanh toán số tiền <strong class="text-heading font-semibold">{{ number_format((float) $order->total_price, 0, ',', '.') }}₫</strong> bằng tiền mặt cho nhân viên giao hàng khi nhận và kiểm tra kiện hàng.
                    </div>
                @endif
            </div>

            <!-- CTAs -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a
                    href="{{ route('orders.index') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                >
                    Xem lịch sử đơn hàng
                </a>
                <a
                    href="{{ route('products.index') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-full border border-ui-border bg-surface px-7 py-3 text-xs font-bold uppercase tracking-wider text-heading transition hover:bg-surface-alt"
                >
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
