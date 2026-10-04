@extends('layouts.app')

@section('title', 'Tra Cứu Tiến Độ Đơn Hàng')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 sm:p-8 mb-8">
        <h1 class="text-2xl font-bold text-heading mb-2 text-center">Tra Cứu Đơn Hàng</h1>
        <p class="text-sm text-muted text-center mb-6">Nhập mã đơn hàng và số điện thoại để kiểm tra tiến trình giao hàng</p>

        <form action="{{ route('orders.track') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-heading mb-1">Mã đơn hàng</label>
                <input type="text" name="order_number" value="{{ request('order_number') }}" required placeholder="Ví dụ: ORD-20260910-AA1001" class="w-full text-sm border border-ui-border rounded-xl p-2.5 outline-none bg-surface-alt text-heading placeholder:text-muted focus:ring-1 focus:ring-primary">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-heading mb-1">Số điện thoại người nhận</label>
                <input type="text" name="customer_phone" value="{{ request('customer_phone') }}" required placeholder="Ví dụ: 0987654321" class="w-full text-sm border border-ui-border rounded-xl p-2.5 outline-none bg-surface-alt text-heading placeholder:text-muted focus:ring-1 focus:ring-primary">
            </div>

            <div class="sm:col-span-2 pt-2">
                <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">
                    Tra Cứu Ngay
                </button>
            </div>
        </form>
    </div>

    @if($searched)
        @if($order)
            <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-ui-border pb-4 gap-2">
                    <div>
                        <span class="text-xs text-muted">Mã đơn hàng</span>
                        <h2 class="text-lg font-bold text-heading font-mono">{{ $order->order_number }}</h2>
                        <span class="text-xs text-muted">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full bg-primary/10 text-primary border border-primary/20">
                            {{ $order->order_status_label }}
                        </span>
                        <span class="block text-xs text-muted mt-1">Thanh toán: {{ $order->payment_status_label }}</span>
                    </div>
                </div>

                <!-- Item list -->
                <div class="divide-y divide-ui-border border-b border-ui-border pb-4">
                    @foreach($order->items as $item)
                        <div class="py-3 flex justify-between items-center text-sm">
                            <div>
                                <h4 class="font-semibold text-heading">{{ $item->product_name }}</h4>
                                @if($item->variant_size || $item->variant_color)
                                    <p class="text-xs text-muted">
                                        {{ trim(($item->variant_size ? $item->variant_size . ' · ' : '') . ($item->variant_color ?? '')) }}
                                    </p>
                                @endif
                                <span class="text-xs text-muted">Số lượng: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ</span>
                            </div>
                            <span class="font-bold text-heading">{{ number_format($item->total, 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                </div>

                <!-- GHN tracking -->
                @if ($order->ghn_order_code)
                    <div class="mb-4 rounded-xl border border-blue-500/20 bg-blue-500/10 p-4 text-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Vận đơn GHN Express</span>
                            <span class="font-mono font-bold text-heading">{{ $order->ghn_order_code }}</span>
                        </div>
                        <p class="text-heading">
                            Trạng thái: <strong>{{ $order->ghn_status_label }}</strong>
                        </p>
                    </div>
                @endif

                <!-- Total -->
                <div class="flex justify-between items-baseline pt-2">
                    <span class="font-bold text-muted">Tổng thanh toán:</span>
                    <span class="text-2xl font-extrabold text-heading">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>
            </div>
        @else
            <div class="bg-surface rounded-2xl border border-ui-border p-8 text-center text-muted">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-amber-500 mb-3"></i>
                <p>Không tìm thấy đơn hàng với thông tin bạn cung cấp. Vui lòng kiểm tra lại mã đơn và số điện thoại.</p>
            </div>
        @endif
    @endif
</div>
@endsection
