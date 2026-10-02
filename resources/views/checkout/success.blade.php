@extends('layouts.app')

@section('title', 'Đặt Hàng Thành Công - ' . ($order->order_code ?? $order->order_number))

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-8 text-center">
        <div class="w-16 h-16 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1 class="text-2xl font-bold text-heading mb-2">Đặt Hàng Thành Công!</h1>
        <p class="text-sm text-muted mb-6">Cảm ơn bạn đã tin tưởng mua sắm nội thất tại Mộc An.</p>

        <div class="bg-surface-alt rounded-xl p-5 text-left text-sm space-y-3 mb-6 border border-ui-border">
            <div class="flex justify-between border-b border-ui-border pb-2">
                <span class="text-muted">Mã đơn hàng:</span>
                <span class="font-mono font-bold text-primary">{{ $order->order_code ?? $order->order_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Khách hàng:</span>
                <span class="font-semibold text-heading">{{ $order->customer_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Số điện thoại:</span>
                <span class="font-semibold text-heading">{{ $order->customer_phone }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">Địa chỉ giao hàng:</span>
                <span class="font-semibold text-heading text-right">{{ $order->shipping_address }}</span>
            </div>
            <div class="flex justify-between border-t border-ui-border pt-2">
                <span class="text-muted">Phương thức thanh toán:</span>
                <span class="font-semibold text-heading uppercase">{{ $order->payment_method }}</span>
            </div>
            @if ($order->discount_amount > 0)
                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                    <span>Giảm giá @if($order->coupon_code)({{ $order->coupon_code }})@endif:</span>
                    <span class="font-semibold">-{{ number_format($order->discount_amount, 0, ',', '.') }}₫</span>
                </div>
            @endif
            <div class="flex justify-between border-t border-ui-border pt-2 items-baseline">
                <span class="text-muted font-bold">Tổng thanh toán:</span>
                <span class="font-extrabold text-heading text-lg">{{ number_format($order->total_price ?? $order->total_amount, 0, ',', '.') }}₫</span>
            </div>
        </div>

        <div class="flex justify-center space-x-4">
            <a href="{{ route('orders.index') }}" class="bg-surface border border-ui-border text-heading hover:bg-surface-alt font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                Xem Lịch Sử Đơn Hàng
            </a>
            <a href="{{ route('home') }}" class="bg-primary hover:opacity-90 text-primary-foreground font-semibold px-5 py-2.5 rounded-xl text-sm transition shadow-sm">
                Về Trang Chủ
            </a>
        </div>
    </div>
</div>
@endsection
