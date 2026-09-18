@extends('layouts.app')

@section('title', 'Thanh Toán Đơn Hàng Nội Thất')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Thông Tin Thanh Toán</h1>

    <form action="{{ route('checkout.process') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Customer Shipping Info Form -->
            <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm p-6 space-y-6">
                <h2 class="text-lg font-bold text-gray-900 border-b pb-3">1. Thông Tin Người Nhận</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Họ và tên *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $user->name ?? '') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                        @error('customer_name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Số điện thoại *</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $user->phone ?? '') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                        @error('customer_phone') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Địa chỉ Email *</label>
                    <input type="email" name="customer_email" value="{{ old('customer_email', $user->email ?? '') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                    @error('customer_email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Địa chỉ giao hàng chi tiết *</label>
                    <textarea name="shipping_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..." class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('shipping_address', $user->address ?? '') }}</textarea>
                    @error('shipping_address') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú giao hàng (Tùy chọn)</label>
                    <textarea name="notes" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..." class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('notes') }}</textarea>
                </div>

                <h2 class="text-lg font-bold text-gray-900 border-b pb-3 pt-4">2. Phương Thức Thanh Toán</h2>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-amber-50/40">
                        <input type="radio" name="payment_method" value="cod" checked class="text-amber-800">
                        <span class="ml-3 font-semibold text-sm text-gray-800">Thanh toán khi nhận hàng (COD)</span>
                    </label>

                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-amber-50/40">
                        <input type="radio" name="payment_method" value="banking" class="text-amber-800">
                        <span class="ml-3 font-semibold text-sm text-gray-800">Chuyển khoản qua Ngân hàng (Banking)</span>
                    </label>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="bg-white rounded-xl border shadow-sm p-6 h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-900 pb-3 border-b">Đơn Hàng Của Bạn</h2>

                <div class="divide-y max-h-72 overflow-y-auto pr-1">
                    @foreach($cart as $item)
                        <div class="py-3 flex justify-between items-center text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-xs bg-gray-100 text-gray-700 w-5 h-5 rounded-full flex items-center justify-center">{{ $item['quantity'] }}</span>
                                <span class="text-gray-800 font-medium truncate max-w-xs">{{ $item['name'] }}</span>
                            </div>
                            <span class="font-semibold text-gray-900">{{ number_format($item['subtotal'], 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Tạm tính:</span>
                        <span class="font-semibold text-gray-900">{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Phí vận chuyển:</span>
                        <span class="font-semibold {{ $shippingFee == 0 ? 'text-emerald-600' : 'text-gray-900' }}">
                            {{ $shippingFee == 0 ? 'Miễn phí' : number_format($shippingFee, 0, ',', '.') . ' đ' }}
                        </span>
                    </div>
                </div>

                <div class="border-t pt-4 flex justify-between items-baseline">
                    <span class="font-bold text-base text-gray-900">Tổng thanh toán:</span>
                    <span class="font-extrabold text-2xl text-amber-900">{{ number_format($total, 0, ',', '.') }} đ</span>
                </div>

                <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-bold text-center py-3.5 rounded-lg shadow-md transition mt-6">
                    Xác Nhận Đặt Hàng
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
