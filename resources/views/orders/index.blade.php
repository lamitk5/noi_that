@extends('layouts.app')

@section('title', 'Lịch sử đơn hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Lịch sử mua hàng</span>
                <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Đơn hàng của bạn</h1>
                <p class="mt-2 text-sm text-muted">Xem và theo dõi tiến trình các đơn hàng nội thất đã đặt.</p>
            </div>
            <a href="{{ route('account.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-heading transition">
                <span>← Về tài khoản</span>
            </a>
        </div>

        @if ($orders->count() > 0)
            <div class="space-y-4">
                @foreach ($orders as $order)
                    <div class="bg-surface rounded-3xl p-6 sm:p-8 shadow-xl shadow-black/5 border border-ui-border transition-all hover:border-primary/50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-ui-border pb-5">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="font-display text-lg font-bold text-heading">#{{ $order->order_code }}</span>
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'packed' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'shipping' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'canceled' => 'bg-rose-50 text-rose-700 border-rose-200',
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $order->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($order->payment_status === 'failed' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                        {{ $order->payment_status === 'paid' ? 'Đã thanh toán' : ($order->payment_status === 'failed' ? 'Thanh toán lỗi' : 'Chưa thanh toán') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Đặt ngày {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}
                                </p>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-xs text-muted block">Tổng tiền</span>
                                <span class="font-display text-xl font-bold text-primary">
                                    {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                                </span>
                            </div>
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs text-muted">
                            <div class="grid sm:grid-cols-2 gap-4 flex-1">
                                <div>
                                    <span class="font-semibold text-heading">Người nhận:</span> {{ $order->customer_name }} ({{ $order->customer_phone }})
                                </div>
                                <div>
                                    <span class="font-semibold text-heading">Địa chỉ:</span> {{ $order->shipping_address }}
                                </div>
                            </div>
                            <div class="sm:text-right shrink-0">
                                <a href="{{ route('orders.show', $order->order_code) }}" class="inline-flex items-center gap-1 font-semibold text-primary hover:underline">
                                    <span>Xem chi tiết</span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @else
            <div class="bg-surface rounded-3xl p-12 text-center shadow-xl shadow-black/5 border border-ui-border max-w-md mx-auto">
                <div class="size-16 rounded-full bg-surface-alt text-muted grid place-items-center mx-auto mb-4">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                </div>
                <h2 class="font-display text-xl font-semibold text-heading mb-2">Bạn chưa có đơn hàng nào.</h2>
                <p class="text-sm text-muted mb-6">Hãy khám phá bộ sưu tập nội thất hiện đại và ấm áp cho không gian sống của bạn.</p>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5">
                    Tiếp tục mua sắm →
                </a>
            </div>
        @endif
    </div>
</div>
@endsection