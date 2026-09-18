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
                Chào mừng bạn đến với trung tâm quản trị thương mại điện tử Mộc An. Tại đây bạn có thể theo dõi và quản trị toàn bộ hoạt động kinh doanh, sản phẩm, đơn hàng và khách hàng.
            </p>
        </div>
    </div>

    <!-- Quick Metrics Overview -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Doanh thu thực nhận</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-display text-heading">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }}₫</span>
            </div>
            <p class="mt-1 text-xs text-muted">Từ các đơn hoàn thành & đã thanh toán</p>
        </div>

        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Tổng đơn hàng</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-display text-heading">{{ $totalOrders ?? 0 }}</span>
                @if(($pendingOrders ?? 0) > 0)
                    <span class="text-xs font-semibold text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded-full">{{ $pendingOrders }} chờ xử lý</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-muted">Cập nhật theo thời gian thực</p>
        </div>

        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Sản phẩm trong kho</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-display text-heading">{{ $totalProducts ?? 0 }}</span>
                @if(($lowStockCount ?? 0) > 0)
                    <span class="text-xs font-semibold text-red-600 bg-red-500/10 px-2 py-0.5 rounded-full">{{ $lowStockCount }} sắp hết hàng</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-muted">Đang phân phối trên website</p>
        </div>

        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Khách hàng đăng ký</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-display text-heading">{{ $totalCustomers ?? 0 }}</span>
            </div>
            <p class="mt-1 text-xs text-muted">Tài khoản khách hàng Mộc An</p>
        </div>
    </div>

    <!-- Modular Sections / Functional Capabilities -->
    <div>
        <div class="mb-4">
            <h2 class="text-base font-bold text-heading">Phân hệ Quản trị</h2>
            <p class="text-xs text-muted">Các phân hệ quản lý sản phẩm, danh mục và đơn hàng đang hoạt động trực tiếp.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Products Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-primary/10 text-primary grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Quản lý Sản phẩm</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Thêm mới, cập nhật giá, hình ảnh, biến thể và thuộc tính sản phẩm nội thất.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">Đang hoạt động</span>
                    <a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-primary hover:underline">Quản lý &rarr;</a>
                </div>
            </div>

            <!-- Categories Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-accent/15 text-accent grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Quản lý Danh mục</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Tổ chức phân loại không gian phòng khách, phòng ngủ, phòng ăn.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">Đang hoạt động</span>
                    <a href="{{ route('admin.categories.index') }}" class="text-xs font-bold text-primary hover:underline">Quản lý &rarr;</a>
                </div>
            </div>

            <!-- Inventory Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Quản lý Kho hàng</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Theo dõi biến động số lượng tồn kho theo từng biến thể sản phẩm.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">Đang hoạt động</span>
                    <a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-primary hover:underline">Quản lý kho &rarr;</a>
                </div>
            </div>

            <!-- Orders Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Quản lý Đơn hàng</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Xác nhận, cập nhật trạng thái giao hàng, thanh toán và hoàn trả tồn kho.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">Đang hoạt động</span>
                    <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-primary hover:underline">Quản lý &rarr;</a>
                </div>
            </div>

            <!-- Customers Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Khách hàng</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Danh sách tài khoản khách hàng, lịch sử mua hàng và thông tin liên hệ.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Giai đoạn tiếp theo</span>
                    <span class="text-xs font-semibold text-muted/60">Chưa kích hoạt</span>
                </div>
            </div>

            <!-- Reviews Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Đánh giá & Phản hồi</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Kiểm duyệt nhận xét và trải nghiệm của khách hàng về sản phẩm.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Giai đoạn tiếp theo</span>
                    <span class="text-xs font-semibold text-muted/60">Chưa kích hoạt</span>
                </div>
            </div>

            <!-- Vouchers Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-pink-500/10 text-pink-600 dark:text-pink-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Khuyến mãi & Voucher</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Tạo mã giảm giá và các chương trình ưu đãi tri ân khách hàng.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Giai đoạn tiếp theo</span>
                    <span class="text-xs font-semibold text-muted/60">Chưa kích hoạt</span>
                </div>
            </div>

            <!-- CMS Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Nội dung & Trang tĩnh</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Biên tập các bài viết chia sẻ không gian đẹp và chính sách bán hàng.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Giai đoạn tiếp theo</span>
                    <span class="text-xs font-semibold text-muted/60">Chưa kích hoạt</span>
                </div>
            </div>

            <!-- Analytics Module -->
            <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between transition hover:border-primary/50">
                <div>
                    <div class="size-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 grid place-items-center mb-3">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-heading">Báo cáo & Phân tích</h3>
                    <p class="mt-1 text-xs text-muted leading-relaxed">Báo cáo doanh thu, sản phẩm bán chạy và hiệu suất kinh doanh tổng thể.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-ui-border flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Giai đoạn tiếp theo</span>
                    <span class="text-xs font-semibold text-muted/60">Chưa kích hoạt</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Inventory Alerts -->
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Recent Orders Table -->
        <div class="lg:col-span-2 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-heading">Đơn hàng mới nhất</h3>
                    <p class="text-xs text-muted">Các đơn hàng vừa được đặt trên hệ thống.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-primary hover:underline">Xem tất cả &rarr;</a>
            </div>

            @if(isset($recentOrders) && $recentOrders->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-ui-border text-muted uppercase font-bold">
                                <th class="pb-2">Mã đơn</th>
                                <th class="pb-2">Khách hàng</th>
                                <th class="pb-2">Tổng tiền</th>
                                <th class="pb-2">Trạng thái</th>
                                <th class="pb-2 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ui-border">
                            @foreach($recentOrders as $order)
                                <tr class="hover:bg-surface-alt/50">
                                    <td class="py-2.5 font-mono font-bold text-heading">{{ $order->order_code }}</td>
                                    <td class="py-2.5">
                                        <div class="font-semibold text-heading">{{ $order->customer_name }}</div>
                                        <div class="text-[10px] text-muted">{{ $order->customer_phone }}</div>
                                    </td>
                                    <td class="py-2.5 font-bold text-heading">{{ number_format($order->total_price, 0, ',', '.') }}₫</td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $order->order_status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : ($order->order_status === 'canceled' ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600') }}">
                                            {{ $order->order_status_label }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-primary hover:underline font-semibold">Chi tiết</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-muted py-6 text-center">Chưa có đơn hàng nào phát sinh.</p>
            @endif
        </div>

        <!-- Low Stock Products -->
        <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-heading">Cảnh báo tồn kho</h3>
                    <p class="text-xs text-muted">Sản phẩm sắp hết hàng (<= 5 sp).</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-primary hover:underline">Quản lý kho</a>
            </div>

            @if(isset($lowStockProducts) && $lowStockProducts->count() > 0)
                <div class="space-y-3">
                    @foreach($lowStockProducts as $p)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border border-ui-border/60 bg-surface-alt/40">
                            <div>
                                <div class="text-xs font-bold text-heading truncate max-w-[150px]">{{ $p->name }}</div>
                                <div class="text-[10px] text-muted">{{ $p->category?->name ?? 'Nội thất' }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-500/10 text-red-600">
                                Còn {{ $p->totalStock() }} sp
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-muted py-6 text-center">Tồn kho các mặt hàng đang ở mức an toàn.</p>
            @endif
        </div>
    </div>
</div>
@endsection
