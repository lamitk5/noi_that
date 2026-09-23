@extends('layouts.app')

@section('title', 'Tra Cứu Tiến Độ Đơn Hàng')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8 mb-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2 text-center">Tra Cứu Đơn Hàng</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Nhập mã đơn hàng và số điện thoại để kiểm tra tiến trình giao hàng</p>

        <form action="{{ route('orders.track') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mã đơn hàng</label>
                <input type="text" name="order_number" value="{{ request('order_number') }}" required placeholder="Ví dụ: ORD-20260910-AA1001" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Số điện thoại người nhận</label>
                <input type="text" name="customer_phone" value="{{ request('customer_phone') }}" required placeholder="Ví dụ: 0987654321" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div class="sm:col-span-2 pt-2">
                <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-bold py-2.5 rounded-lg transition">
                    Tra Cứu Ngay
                </button>
            </div>
        </form>
    </div>

    @if($searched)
        @if($order)
            <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 gap-2">
                    <div>
                        <span class="text-xs text-gray-400">Mã đơn hàng</span>
                        <h2 class="text-lg font-bold text-amber-950 font-mono">{{ $order->order_number }}</h2>
                        <span class="text-xs text-gray-500">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-900">
                            {{ $order->order_status_label }}
                        </span>
                        <span class="block text-xs text-gray-500 mt-1">Thanh toán: {{ $order->payment_status_label }}</span>
                    </div>
                </div>

                @if ($order->ghn_order_code)
                    <div class="p-4 rounded-xl border border-orange-200 bg-orange-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-orange-200 px-2 py-0.5 text-[10px] font-bold text-orange-800 uppercase tracking-wider">Vận đơn GHN Express</span>
                                <span class="font-mono font-bold text-orange-950">#{{ $order->ghn_order_code }}</span>
                            </div>
                            <div class="text-xs text-gray-600">
                                Trạng thái GHN: <strong>{{ $order->ghn_status_label }}</strong>
                                @if ($order->ghn_expected_delivery_at)
                                    • Giao dự kiến: {{ $order->ghn_expected_delivery_at->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ $order->ghn_tracking_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 transition shadow-sm">
                            <span>Tra cứu GHN</span>
                            <span>↗</span>
                        </a>
                    </div>
                @endif

                <!-- Item list -->
                <div class="divide-y border-b pb-4">
                    @foreach($order->items as $item)
                        <div class="py-3 flex justify-between items-center text-sm">
                            <div>
                                <h4 class="font-semibold text-gray-800">{{ $item->product_name }}</h4>
                                <span class="text-xs text-gray-400">Số lượng: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ</span>
                            </div>
                            <span class="font-bold text-gray-900">{{ number_format($item->total, 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="flex justify-between items-baseline pt-2">
                    <span class="font-bold text-gray-700">Tổng thanh toán:</span>
                    <span class="text-2xl font-extrabold text-amber-900">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>

                <div class="pt-4 border-t flex justify-end">
                    <a href="{{ route('orders.invoice', $order->order_code ?? $order->order_number) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-800 hover:bg-amber-900 transition shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>In / Xuất Hóa Đơn PDF</span>
                    </a>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border p-8 text-center text-gray-500">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-amber-600 mb-3"></i>
                <p>Không tìm thấy đơn hàng với thông tin bạn cung cấp. Vui lòng kiểm tra lại mã đơn và số điện thoại.</p>
            </div>
        @endif
    @endif
</div>
@endsection
