@extends('layouts.admin')

@section('title', 'Admin Dashboard - Thống Kê Tổng Quan')
@section('page_title', 'Bảng Điều Khiển Tổng Quan')

@section('content')
<div class="space-y-8">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-5 border shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Tổng Doanh Thu</p>
                <h3 class="text-2xl font-extrabold text-amber-900 mt-1">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h3>
            </div>
            <div class="w-12 h-12 bg-amber-100 text-amber-800 rounded-full flex items-center justify-center text-xl">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Tổng Đơn Hàng</p>
                <h3 class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalOrders }}</h3>
                <span class="text-xs text-amber-600 font-medium">{{ $pendingOrders }} đơn chờ xử lý</span>
            </div>
            <div class="w-12 h-12 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Sản Phẩm Nội Thất</p>
                <h3 class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalProducts }}</h3>
                @if($lowStockCount > 0)
                    <span class="text-xs text-rose-600 font-medium">{{ $lowStockCount }} sắp hết kho!</span>
                @else
                    <span class="text-xs text-emerald-600 font-medium">Kho ổn định</span>
                @endif
            </div>
            <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-full flex items-center justify-center text-xl">
                <i class="fa-solid fa-couch"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase">Khách Hàng</p>
                <h3 class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalCustomers }}</h3>
                <span class="text-xs text-gray-400 font-medium">Tài khoản thành viên</span>
            </div>
            <div class="w-12 h-12 bg-purple-100 text-purple-800 rounded-full flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Low Stock Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-900 text-base">Đơn Hàng Gần Đây</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-amber-800 font-bold hover:underline">Xem tất cả →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 uppercase text-gray-500 font-semibold border-b">
                        <tr>
                            <th class="py-3 px-3">Mã đơn</th>
                            <th class="py-3 px-3">Khách hàng</th>
                            <th class="py-3 px-3">Tổng tiền</th>
                            <th class="py-3 px-3">Trạng thái</th>
                            <th class="py-3 px-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-gray-700">
                        @foreach($recentOrders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-3 font-mono font-bold text-amber-900">{{ $order->order_number }}</td>
                                <td class="py-3 px-3 font-medium">{{ $order->customer_name }}</td>
                                <td class="py-3 px-3 font-bold">{{ number_format($order->total_amount, 0, ',', '.') }} đ</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                        {{ $order->order_status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-amber-800 hover:underline font-semibold">Xem</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Warning -->
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h3 class="font-bold text-gray-900 text-base mb-4">Cảnh Báo Tồn Kho</h3>
            <div class="space-y-3 text-xs">
                @forelse($lowStockProducts as $p)
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border">
                        <div class="truncate mr-2">
                            <h4 class="font-bold text-gray-800 truncate">{{ $p->name }}</h4>
                            <span class="text-gray-400 font-mono">{{ $p->sku }}</span>
                        </div>
                        <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded font-bold whitespace-nowrap">
                            Còn: {{ $p->stock_quantity }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-6">Tất cả sản phẩm đều đủ số lượng tồn kho.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
