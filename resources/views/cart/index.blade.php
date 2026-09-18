@extends('layouts.app')

@section('title', 'Giỏ Hàng Nội Thất')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Giỏ Hàng Của Bạn</h1>

    @if(empty($cart))
        <div class="bg-white rounded-xl p-12 text-center border shadow-sm">
            <i class="fa-solid fa-cart-arrow-down text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-6">Giỏ hàng của bạn đang trống.</p>
            <a href="{{ route('products.index') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold px-6 py-2.5 rounded-lg transition inline-block">
                Tiếp Tục Mua Sắm
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items List -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl border shadow-sm divide-y">
                    @foreach($cart as $id => $item)
                        <div class="p-4 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center space-x-4 w-full sm:w-auto">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-20 h-20 object-cover rounded-lg border bg-gray-50 flex-shrink-0">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm sm:text-base hover:text-amber-800">
                                        <a href="{{ route('products.show', $item['slug']) }}">{{ $item['name'] }}</a>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">SKU: {{ $item['sku'] }}</p>
                                    <p class="text-sm font-bold text-amber-900 mt-1">{{ number_format($item['price'], 0, ',', '.') }} đ</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 w-full sm:w-auto justify-between sm:justify-end">
                                <!-- Update quantity form -->
                                <form action="{{ route('cart.update', $item['id']) }}" method="POST" class="flex items-center border rounded-lg">
                                    @csrf
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="w-16 text-center text-sm py-1.5 outline-none font-semibold">
                                    <button type="submit" class="px-2 text-xs text-gray-600 hover:text-amber-800 border-l py-1.5 bg-gray-50" title="Cập nhật">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </button>
                                </form>

                                <span class="font-extrabold text-gray-900 text-sm w-28 text-right">
                                    {{ number_format($item['subtotal'], 0, ',', '.') }} đ
                                </span>

                                <!-- Remove form -->
                                <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-rose-600 p-1" title="Xóa">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between items-center">
                    <a href="{{ route('products.index') }}" class="text-sm text-amber-800 font-semibold hover:underline">
                        ← Tiếp tục mua sắm
                    </a>
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-rose-600" onclick="return confirm('Bạn có chắc muốn dọn sạch giỏ hàng?')">
                            Dọn sạch giỏ hàng
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="bg-white rounded-xl border shadow-sm p-6 h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-900 pb-3 border-b">Tóm Tắt Đơn Hàng</h2>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Tạm tính:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Phí vận chuyển:</span>
                    <span class="font-semibold {{ $shippingFee == 0 ? 'text-emerald-600' : 'text-gray-900' }}">
                        {{ $shippingFee == 0 ? 'Miễn phí' : number_format($shippingFee, 0, ',', '.') . ' đ' }}
                    </span>
                </div>
                @if($shippingFee > 0)
                    <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded">
                        <i class="fa-solid fa-circle-info mr-1"></i> Miễn phí vận chuyển cho đơn hàng từ 5.000.000 đ!
                    </p>
                @endif
                <div class="border-t pt-4 flex justify-between items-baseline">
                    <span class="font-bold text-base text-gray-900">Tổng cộng:</span>
                    <span class="font-extrabold text-2xl text-amber-900">{{ number_format($total, 0, ',', '.') }} đ</span>
                </div>

                <a href="{{ route('checkout.index') }}" class="block w-full bg-amber-800 hover:bg-amber-900 text-white font-bold text-center py-3.5 rounded-lg shadow transition mt-6">
                    Tiến Hành Đặt Hàng
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
