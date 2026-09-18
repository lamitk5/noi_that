@extends('layouts.admin')

@section('title', 'Quản trị Mộc An | Bảng điều khiển')
@section('header-title', 'Bảng điều khiển tổng quan')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <!-- Welcome Banner -->
    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Hệ thống Quản trị Mộc An</span>
            <h1 class="mt-2 font-display text-2xl sm:text-3xl font-semibold text-heading">
                Xin chào, {{ $user->name }}!
            </h1>
            <p class="mt-2 text-sm text-muted leading-relaxed">
                Chào mừng bạn đến với trung tâm quản trị thương mại điện tử Mộc An. Theo dõi hiệu suất kinh doanh, quản lý kho hàng và xử lý đơn hàng theo thời gian thực.
            </p>
        </div>
    </div>

    <!-- Live KPI Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Doanh thu thực nhận</div>
            <div class="text-base sm:text-lg font-bold font-display text-primary mt-1 truncate">
                {{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}đ
            </div>
            <div class="text-[10px] text-muted mt-1">Đơn hoàn thành</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Đơn hoàn tất</div>
            <div class="text-base sm:text-lg font-bold font-display text-heading mt-1">
                {{ number_format($stats['completed_orders'] ?? 0) }}
            </div>
            <div class="text-[10px] text-emerald-600 mt-1">Thành công</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Đơn chờ xử lý</div>
            <div class="text-base sm:text-lg font-bold font-display text-heading mt-1">
                {{ number_format($stats['pending_orders'] ?? 0) }}
            </div>
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-[10px] text-primary hover:underline mt-1 block">Xử lý ngay →</a>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Sản phẩm đang bán</div>
            <div class="text-base sm:text-lg font-bold font-display text-heading mt-1">
                {{ number_format($stats['active_products'] ?? 0) }}
            </div>
            <div class="text-[10px] text-muted mt-1">Hiển thị storefront</div>
        </div>

        <div class="p-4 rounded-2xl bg-surface border {{ ($stats['low_stock_variants'] ?? 0) > 0 ? 'border-amber-500/30 bg-amber-500/5' : 'border-ui-border' }}">
            <div class="text-[10px] font-bold uppercase tracking-wider {{ ($stats['low_stock_variants'] ?? 0) > 0 ? 'text-amber-500' : 'text-muted' }}">Sắp hết hàng</div>
            <div class="text-base sm:text-lg font-bold font-display {{ ($stats['low_stock_variants'] ?? 0) > 0 ? 'text-amber-500' : 'text-heading' }} mt-1">
                {{ number_format($stats['low_stock_variants'] ?? 0) }}
            </div>
            <a href="{{ route('admin.inventory.index', ['filter' => 'low_stock']) }}" class="text-[10px] text-amber-600 hover:underline mt-1 block">Xem tồn kho →</a>
        </div>

        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Khách hàng</div>
            <div class="text-base sm:text-lg font-bold font-display text-heading mt-1">
                {{ number_format($stats['total_customers'] ?? 0) }}
            </div>
            <div class="text-[10px] text-muted mt-1">Đã đăng ký</div>
        </div>
    </div>

    <!-- Active Admin Modules -->
    <div>
        <div class="mb-4">
            <h2 class="text-base font-bold text-heading">Phân hệ Quản trị Chức năng</h2>
            <p class="text-xs text-muted">Truy cập nhanh vào các tính năng quản lý cửa hàng và kinh doanh.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Products Module -->
            <a href="{{ route('admin.products.index') }}" class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50 group">
                <div>
                    <div class="size-10 rounded-xl bg-primary/10 text-primary grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading group-hover:text-primary transition">Quản lý Sản phẩm</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Danh sách sản phẩm, quản lý biến thể, màu sắc, kích thước và hình ảnh.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Đang hoạt động</span>
                    <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition">Mở quản lý →</span>
                </div>
            </a>

            <!-- Categories Module -->
            <a href="{{ route('admin.categories.index') }}" class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50 group">
                <div>
                    <div class="size-10 rounded-xl bg-accent/15 text-accent grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading group-hover:text-primary transition">Quản lý Danh mục</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Tổ chức phân loại danh mục, cập nhật mô tả và kiểm soát trạng thái hiển thị.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Đang hoạt động</span>
                    <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition">Mở quản lý →</span>
                </div>
            </a>

            <!-- Inventory Module -->
            <a href="{{ route('admin.inventory.index') }}" class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50 group">
                <div>
                    <div class="size-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading group-hover:text-primary transition">Quản lý Kho hàng</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Theo dõi tồn kho theo từng SKU, nhận diện sắp hết hàng và cập nhật nhanh số lượng.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Đang hoạt động</span>
                    <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition">Kiểm kho →</span>
                </div>
            </a>

            <!-- Orders Module -->
            <a href="{{ route('admin.orders.index') }}" class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50 group">
                <div>
                    <div class="size-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading group-hover:text-primary transition">Quản lý Đơn hàng</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Xác nhận đơn, chuyển trạng thái giao hàng và xử lý thanh toán thực tế.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Đang hoạt động</span>
                    <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition">Quản lý đơn →</span>
                </div>
            </a>

            <!-- Sales Reports Module -->
            <a href="{{ route('admin.reports.index') }}" class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50 group">
                <div>
                    <div class="size-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading group-hover:text-primary transition">Báo cáo & Phân tích</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Phân tích doanh thu, tỷ trọng phương thức thanh toán và top sản phẩm bán chạy.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Đang hoạt động</span>
                    <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition">Xem báo cáo →</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-heading">Đơn hàng mới nhận gần đây</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-primary hover:underline">
                Xem tất cả đơn hàng &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="text-muted font-semibold uppercase tracking-wider text-[11px] border-b border-ui-border">
                        <th class="py-2.5 px-3">Mã đơn</th>
                        <th class="py-2.5 px-3">Khách hàng</th>
                        <th class="py-2.5 px-3">Tổng tiền</th>
                        <th class="py-2.5 px-3">Thanh toán</th>
                        <th class="py-2.5 px-3">Trạng thái đơn</th>
                        <th class="py-2.5 px-3 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-2.5 px-3 font-mono font-bold text-heading">#{{ $order->order_code }}</td>
                            <td class="py-2.5 px-3">{{ $order->customer_name }}</td>
                            <td class="py-2.5 px-3 font-medium text-heading">{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $order->payment_status === 'paid' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-surface-alt text-muted' }}">
                                    {{ strtoupper($order->payment_method) }} - {{ $order->payment_status_text }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $order->order_status === 'completed' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-primary/10 text-primary' }}">
                                    {{ $order->order_status_text }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-primary hover:underline font-semibold text-[11px]">
                                    Xem &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-muted">Chưa có đơn hàng nào trong hệ thống.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
