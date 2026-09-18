@extends('layouts.admin')

@section('title', 'Chi Tiết Đơn Hàng ' . $order->order_number)
@section('page_title', 'Chi Tiết Đơn Hàng: ' . $order->order_number)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Order Items & Customer Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Products in Order -->
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-4 border-b pb-3">Sản Phẩm Đã Đặt</h3>
            <div class="divide-y text-xs">
                @foreach($order->items as $item)
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm">{{ $item->product_name }}</h4>
                            <span class="text-gray-400 font-mono">SKU: {{ $item->product_sku ?? '---' }}</span>
                            <div class="text-gray-500 mt-0.5">Số lượng: <span class="font-semibold text-gray-800">{{ $item->quantity }}</span> x {{ number_format($item->price, 0, ',', '.') }} đ</div>
                        </div>
                        <span class="font-extrabold text-amber-950 text-sm">{{ number_format($item->total, 0, ',', '.') }} đ</span>
                    </div>
                @endforeach
            </div>

            <div class="border-t pt-4 space-y-2 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Tạm tính:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($order->subtotal, 0, ',', '.') }} đ</span>
                </div>
                <div class="flex justify-between">
                    <span>Phí vận chuyển:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($order->shipping_fee, 0, ',', '.') }} đ</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-amber-900 border-t pt-2">
                    <span>Tổng tiền đơn hàng:</span>
                    <span>{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>
            </div>
        </div>

        <!-- Customer & Delivery Address -->
        <div class="bg-white rounded-xl border shadow-sm p-6 text-xs space-y-3">
            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider border-b pb-3">Thông Tin Người Nhận</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-400 block">Họ và tên:</span>
                    <span class="font-bold text-gray-800 text-sm">{{ $order->customer_name }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block">Số điện thoại:</span>
                    <span class="font-bold text-gray-800 text-sm">{{ $order->customer_phone }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block">Email:</span>
                    <span class="font-medium text-gray-700">{{ $order->customer_email }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block">Phương thức thanh toán:</span>
                    <span class="font-bold text-gray-800 uppercase">{{ $order->payment_method }}</span>
                </div>
            </div>
            <div class="pt-2">
                <span class="text-gray-400 block">Địa chỉ giao hàng:</span>
                <p class="font-medium text-gray-800 bg-gray-50 p-2.5 rounded-lg border mt-1">{{ $order->shipping_address }}</p>
            </div>
            @if($order->notes)
                <div class="pt-2">
                    <span class="text-gray-400 block">Ghi chú của khách hàng:</span>
                    <p class="text-gray-600 italic bg-amber-50/50 p-2 rounded border border-amber-100 mt-1">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Status Update Form Card -->
    <div class="bg-white rounded-xl border shadow-sm p-6 h-fit space-y-6">
        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider border-b pb-3">Cập Nhật Tiến Trình</h3>

        <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Trạng thái đơn hàng</label>
                <select name="order_status" class="w-full text-xs border rounded-lg p-2.5 outline-none font-semibold">
                    <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="confirmed" {{ $order->order_status == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="shipping" {{ $order->order_status == 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                    <option value="completed" {{ $order->order_status == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>Đã hủy (Tự động hoàn kho)</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-gray-700 uppercase mb-1">Trạng thái thanh toán</label>
                <select name="payment_status" class="w-full text-xs border rounded-lg p-2.5 outline-none font-semibold">
                    <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>Thanh toán thất bại</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-bold py-2.5 rounded-lg transition shadow">
                Cập Nhật Trạng Thái
            </button>
        </form>

        <div class="pt-4 border-t">
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-amber-800 hover:underline font-semibold block text-center">
                ← Quay lại danh sách đơn hàng
            </a>
        </div>
    </div>
</div>
@endsection
