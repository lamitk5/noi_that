@extends('layouts.app')

@section('title', 'Đặt Hàng Thành Công - ' . $order->order_number)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-2xl border shadow-sm p-8 text-center">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Đặt Hàng Thành Công!</h1>
        <p class="text-sm text-gray-500 mb-6">Cảm ơn bạn đã tin tưởng mua sắm nội thất tại LUXURY HOME.</p>

        <div class="bg-gray-50 rounded-xl p-5 text-left text-sm space-y-3 mb-6 border">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Mã đơn hàng:</span>
                <span class="font-mono font-bold text-amber-900">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Khách hàng:</span>
                <span class="font-semibold text-gray-800">{{ $order->customer_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Số điện thoại:</span>
                <span class="font-semibold text-gray-800">{{ $order->customer_phone }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Địa chỉ giao hàng:</span>
                <span class="font-semibold text-gray-800 text-right">{{ $order->shipping_address }}</span>
            </div>
            <div class="flex justify-between border-t pt-2">
                <span class="text-gray-500">Phương thức thanh toán:</span>
                <span class="font-semibold text-gray-800 uppercase">{{ $order->payment_method }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tổng tiền:</span>
                <span class="font-extrabold text-amber-900 text-lg">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
            </div>
        </div>

        <div class="flex justify-center space-x-4">
            <a href="{{ route('orders.track', ['order_number' => $order->order_number, 'customer_phone' => $order->customer_phone]) }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold px-5 py-2.5 rounded-lg text-sm transition">
                Theo Dõi Đơn Hàng
            </a>
            <a href="{{ route('home') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition">
                Về Trang Chủ
            </a>
        </div>
    </div>
</div>
@endsection
