@extends('layouts.app')

@section('title', 'Kết quả thanh toán ' . $provider . ' | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-2xl mx-auto">
        <div class="rounded-3xl border border-ui-border bg-surface p-8 sm:p-12 shadow-xl shadow-black/5 text-center">
            @php
                $isPaid = $order && $order->payment_status === 'paid';
                $isFailed = ($transaction && $transaction->isFailed()) || ($responseCode && $responseCode !== '00' && $responseCode !== '0');
            @endphp

            @if ($isPaid)
                <div class="size-20 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 grid place-items-center mx-auto mb-6">
                    <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">Giao dịch thành công</span>
                <h1 class="mt-2 font-display text-3xl font-bold text-heading">Thanh toán hoàn tất!</h1>
                <p class="mt-3 text-sm text-muted">
                    Hệ thống đã ghi nhận thanh toán qua cổng <strong class="text-heading">{{ $provider }}</strong> cho đơn hàng của bạn.
                </p>
            @elseif ($isFailed)
                <div class="size-20 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 grid place-items-center mx-auto mb-6">
                    <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-rose-600 dark:text-rose-400">Giao dịch chưa hoàn tất</span>
                <h1 class="mt-2 font-display text-3xl font-bold text-heading">Thanh toán không thành công</h1>
                <p class="mt-3 text-sm text-muted">
                    Giao dịch qua cổng <strong class="text-heading">{{ $provider }}</strong> đã bị hủy hoặc gặp sự cố. Bạn có thể thử thanh toán lại hoặc chọn phương thức khác.
                </p>
            @else
                <div class="size-20 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center mx-auto mb-6">
                    <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">Đang xử lý</span>
                <h1 class="mt-2 font-display text-3xl font-bold text-heading">Đang chờ xác nhận giao dịch</h1>
                <p class="mt-3 text-sm text-muted">
                    Hệ thống đang đối soát kết quả với cổng thanh toán <strong class="text-heading">{{ $provider }}</strong>. Trạng thái sẽ được cập nhật tự động trong giây lát.
                </p>
            @endif

            @if ($order)
                <div class="mt-8 rounded-2xl border border-ui-border bg-surface-alt p-6 text-left space-y-3 text-xs">
                    <div class="flex justify-between items-center pb-3 border-b border-ui-border">
                        <span class="text-muted">Mã đơn hàng:</span>
                        <span class="font-bold text-heading font-display text-sm">#{{ $order->order_code }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Tổng số tiền:</span>
                        <span class="font-bold text-primary font-display text-sm">{{ number_format((float) $order->total_price, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted">Cổng thanh toán:</span>
                        <span class="font-semibold text-heading">{{ $provider }}</span>
                    </div>
                    @if ($transaction)
                        <div class="flex justify-between items-center">
                            <span class="text-muted">Mã tham chiếu:</span>
                            <span class="font-mono text-[11px] text-muted">{{ $transaction->provider_reference }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center pt-2 border-t border-ui-border">
                        <span class="text-muted">Trạng thái thanh toán:</span>
                        @if ($order->payment_status === 'paid')
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">Đã thanh toán</span>
                        @elseif ($order->payment_status === 'failed')
                            <span class="font-semibold text-rose-600 dark:text-rose-400">Thất bại</span>
                        @else
                            <span class="font-semibold text-amber-600 dark:text-amber-400">Chờ xác nhận</span>
                        @endif
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="{{ route('orders.show', $order->order_code) }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5"
                    >
                        Xem chi tiết đơn hàng
                    </a>

                    @if (! $isPaid && $order->order_status !== 'canceled')
                        @if ($order->payment_method === 'vnpay')
                            <form method="POST" action="{{ route('payments.vnpay.create', $order->order_code) }}" class="w-full sm:w-auto">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-full border border-ui-border bg-surface px-7 py-3 text-xs font-bold uppercase tracking-wider text-heading transition hover:bg-surface-alt cursor-pointer"
                                >
                                    Thanh toán lại qua VNPAY
                                </button>
                            </form>
                        @elseif ($order->payment_method === 'momo')
                            <form method="POST" action="{{ route('payments.momo.create', $order->order_code) }}" class="w-full sm:w-auto">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-full border border-ui-border bg-surface px-7 py-3 text-xs font-bold uppercase tracking-wider text-heading transition hover:bg-surface-alt cursor-pointer"
                                >
                                    Thanh toán lại qua MoMo
                                </button>
                            </form>
                        @endif
                    @endif

                    <a
                        href="{{ route('products.index') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center rounded-full border border-ui-border bg-surface px-7 py-3 text-xs font-bold uppercase tracking-wider text-heading transition hover:bg-surface-alt"
                    >
                        Tiếp tục mua sắm
                    </a>
                </div>
            @else
                <div class="mt-8 flex justify-center">
                    <a
                        href="{{ route('orders.index') }}"
                        class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground"
                    >
                        Xem danh sách đơn hàng
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
