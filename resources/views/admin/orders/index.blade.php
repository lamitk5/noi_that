@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng | Admin Mộc An')
@section('header-title', 'Danh sách đơn hàng')

@section('content')
<div class="space-y-6">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold font-display text-heading">Quản lý Đơn hàng</h1>
            <p class="text-xs text-muted mt-1">Theo dõi, xác nhận và cập nhật tiến trình đơn hàng.</p>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 items-end">
            <!-- Search Keyword -->
            <div class="lg:col-span-4">
                <label for="q" class="block text-xs font-semibold text-heading mb-1">Tìm kiếm</label>
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        id="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Mã đơn, tên, sđt, email..."
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 pl-9 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    <svg viewBox="0 0 24 24" class="size-4 text-muted absolute left-3 top-2.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
            </div>

            <!-- Order Status Filter -->
            <div class="lg:col-span-2">
                <label for="status" class="block text-xs font-semibold text-heading mb-1">Trạng thái đơn</label>
                <select
                    name="status"
                    id="status"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Đang xử lý</option>
                    <option value="confirmed" {{ $filters['status'] === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="packed" {{ $filters['status'] === 'packed' ? 'selected' : '' }}>Đã đóng gói</option>
                    <option value="shipping" {{ $filters['status'] === 'shipping' ? 'selected' : '' }}>Đang vận chuyển</option>
                    <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Đã giao</option>
                    <option value="canceled" {{ $filters['status'] === 'canceled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div class="lg:col-span-2">
                <label for="payment_status" class="block text-xs font-semibold text-heading mb-1">Thanh toán</label>
                <select
                    name="payment_status"
                    id="payment_status"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                    <option value="">Tất cả</option>
                    <option value="pending" {{ $filters['payment_status'] === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                    <option value="paid" {{ $filters['payment_status'] === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="failed" {{ $filters['payment_status'] === 'failed' ? 'selected' : '' }}>Thất bại</option>
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div class="lg:col-span-2">
                <label for="payment_method" class="block text-xs font-semibold text-heading mb-1">Phương thức</label>
                <select
                    name="payment_method"
                    id="payment_method"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                    <option value="">Tất cả</option>
                    <option value="cod" {{ $filters['payment_method'] === 'cod' ? 'selected' : '' }}>COD</option>
                    <option value="bank_transfer" {{ $filters['payment_method'] === 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                    <option value="vnpay" {{ $filters['payment_method'] === 'vnpay' ? 'selected' : '' }}>VNPAY</option>
                    <option value="momo" {{ $filters['payment_method'] === 'momo' ? 'selected' : '' }}>MoMo</option>
                </select>
            </div>

            <!-- Filter Action Buttons -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition hover:opacity-95 cursor-pointer text-center"
                >
                    Lọc
                </button>
                @if (array_filter($filters))
                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs font-semibold text-muted hover:text-heading transition"
                        title="Xóa bộ lọc"
                    >
                        Xóa
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden">
        @if ($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-ui-border bg-surface-alt text-muted font-bold tracking-wider uppercase text-[10px]">
                            <th class="px-4 py-3.5">Mã đơn</th>
                            <th class="px-4 py-3.5">Khách hàng</th>
                            <th class="px-4 py-3.5">Ngày đặt</th>
                            <th class="px-4 py-3.5">Tổng tiền</th>
                            <th class="px-4 py-3.5">Phương thức</th>
                            <th class="px-4 py-3.5">Thanh toán</th>
                            <th class="px-4 py-3.5">Trạng thái đơn</th>
                            <th class="px-4 py-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ui-border">
                        @php
                            $statusClasses = [
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40',
                                'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/40',
                                'packed' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/40',
                                'shipping' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-900/40',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40',
                                'canceled' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40',
                            ];
                            $methodNames = [
                                'cod' => 'COD',
                                'bank_transfer' => 'Chuyển khoản',
                                'vnpay' => 'VNPAY',
                                'momo' => 'MoMo',
                            ];
                        @endphp

                        @foreach ($orders as $order)
                            <tr class="hover:bg-surface-alt/50 transition">
                                <td class="px-4 py-3.5 font-bold font-display text-heading whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order->order_code) }}" class="hover:text-primary transition">
                                        #{{ $order->order_code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-heading">{{ $order->customer_name }}</div>
                                    <div class="text-[11px] text-muted">{{ $order->customer_phone }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-muted whitespace-nowrap">
                                    {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 font-bold font-display text-primary whitespace-nowrap">
                                    {{ number_format((float) $order->total_price, 0, ',', '.') }}₫
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-surface-alt text-heading border border-ui-border">
                                        {{ $methodNames[$order->payment_method] ?? strtoupper($order->payment_method) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($order->payment_status === 'paid')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40">
                                            Đã thanh toán
                                        </span>
                                    @elseif ($order->payment_status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/40">
                                            Lỗi thanh toán
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40">
                                            Chờ thanh toán
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusClasses[$order->order_status] ?? 'bg-surface-alt text-body border-ui-border' }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <a
                                        href="{{ route('admin.orders.show', $order->order_code) }}"
                                        class="inline-flex items-center gap-1 font-semibold text-primary hover:underline"
                                    >
                                        <span>Chi tiết</span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-ui-border">
                {{ $orders->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="size-12 rounded-full bg-surface-alt text-muted grid place-items-center mx-auto mb-3">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3 class="text-sm font-bold text-heading">Không tìm thấy đơn hàng nào</h3>
                <p class="text-xs text-muted mt-1">Hãy thử thay đổi điều kiện lọc hoặc từ khóa tìm kiếm.</p>
            </div>
        @endif
    </div>
</div>
@endsection
