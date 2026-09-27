@extends('layouts.admin')

@section('title', 'Quản Lý Đơn Hàng Nội Thất')
@section('page_title', 'Danh Sách Đơn Đặt Hàng')

@section('content')
<div class="bg-white rounded-xl border shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã đơn, tên, sđt khách..." class="text-xs border rounded-lg p-2 outline-none w-56">
            <select name="status" class="text-xs border rounded-lg p-2 outline-none">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao hàng</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
            <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-2 rounded-lg hover:bg-gray-900">
                <i class="fa-solid fa-magnifying-glass"></i> Lọc
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 uppercase text-gray-500 font-semibold border-b">
                <tr>
                    <th class="py-3 px-3">Mã đơn</th>
                    <th class="py-3 px-3">Khách hàng</th>
                    <th class="py-3 px-3">Số điện thoại</th>
                    <th class="py-3 px-3">Tổng tiền</th>
                    <th class="py-3 px-3">Thanh toán</th>
                    <th class="py-3 px-3">Trạng thái đơn</th>
                    <th class="py-3 px-3">Ngày đặt</th>
                    <th class="py-3 px-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y text-gray-700">
                @foreach($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-3 font-mono font-bold text-amber-900">{{ $order->order_number }}</td>
                        <td class="py-3 px-3 font-semibold">{{ $order->customer_name }}</td>
                        <td class="py-3 px-3">{{ $order->customer_phone }}</td>
                        <td class="py-3 px-3 font-extrabold text-amber-950">{{ number_format($order->total_amount, 0, ',', '.') }} đ</td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded text-2xs font-bold uppercase {{ $order->payment_status == 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                {{ $order->order_status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-gray-400">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-amber-800 font-bold hover:underline">Chi tiết</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection
