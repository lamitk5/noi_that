@extends('layouts.admin')

@section('title', 'Báo cáo & Phân tích | Mộc An Admin')
@section('page_title', 'Báo cáo & Phân tích')

@section('content')
<div class="space-y-6">
    <!-- Top Filter Bar & Export Actions -->
    <div class="rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Time Period Selector -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-muted uppercase tracking-wider mr-1">Khoảng thời gian:</span>
            <div class="inline-flex rounded-xl border border-ui-border bg-surface-alt p-1 text-xs font-semibold">
                <a
                    href="{{ route('admin.analytics.index', ['period' => 'day']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $period === 'day' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading' }}"
                >
                    Hôm nay (Ngày)
                </a>
                <a
                    href="{{ route('admin.analytics.index', ['period' => 'week']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $period === 'week' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading' }}"
                >
                    Tuần này
                </a>
                <a
                    href="{{ route('admin.analytics.index', ['period' => 'month']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $period === 'month' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading' }}"
                >
                    Tháng này
                </a>
                <a
                    href="{{ route('admin.analytics.index', ['period' => 'year']) }}"
                    class="px-3 py-1.5 rounded-lg transition {{ $period === 'year' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading' }}"
                >
                    Năm nay
                </a>
            </div>
        </div>

        <!-- Export & Print Action Buttons -->
        <div class="flex items-center gap-2.5">
            <a
                href="{{ route('admin.analytics.export', ['period' => $period]) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition"
                title="Tải file Excel / CSV danh sách đơn hàng"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Xuất Báo Cáo (Excel/CSV)</span>
            </a>
            <button
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs font-semibold text-body hover:text-heading transition"
                title="In trang báo cáo"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>In Báo Cáo</span>
            </button>
        </div>
    </div>

    <!-- Active Period Badge -->
    <div class="flex items-center justify-between text-xs text-muted">
        <div>
            Đang xem số liệu: <strong class="text-heading font-semibold">{{ $periodLabel }}</strong>
        </div>
        <div>
            So sánh chuẩn với: <span class="italic">{{ $prevPeriodLabel }}</span>
        </div>
    </div>

    <!-- KPI Cards (4 Items: Doanh thu, Tổng đơn, Chờ xử lý, Hoàn thành / Hủy) -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Doanh thu -->
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Doanh thu</span>
                <div class="mt-2 text-2xl font-bold font-display text-heading">{{ number_format($totalRevenue, 0, ',', '.') }}₫</div>
            </div>
            <p class="mt-3 text-xs font-semibold {{ $revenueDelta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $revenueDelta >= 0 ? '▲ +' : '▼ -' }}{{ abs($revenueDelta) }}% so với {{ $prevPeriodLabel }}
            </p>
        </div>

        <!-- Tổng đơn hàng -->
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Tổng đơn hàng</span>
                <div class="mt-2 text-2xl font-bold font-display text-primary">{{ $totalOrders }}</div>
            </div>
            <p class="mt-3 text-xs font-semibold {{ $ordersDelta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ $ordersDelta >= 0 ? '▲ +' : '▼ -' }}{{ abs($ordersDelta) }}% so với {{ $prevPeriodLabel }}
            </p>
        </div>

        <!-- Đơn chờ xử lý -->
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Đơn chờ xử lý</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-display text-amber-600">{{ $pendingOrders }}</span>
                    @if($shippingOrders > 0)
                        <span class="text-xs font-semibold text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded-full">+{{ $shippingOrders }} đang giao</span>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-xs text-muted">
                {{ $pendingOrders > 0 ? 'Cần xác nhận và chuẩn bị kho' : 'Không có đơn chờ duyệt' }}
            </p>
        </div>

        <!-- Hoàn thành / Đã hủy -->
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Hoàn thành / Đã hủy</span>
                <div class="mt-2 text-2xl font-bold font-display flex items-baseline gap-1.5">
                    <span class="text-emerald-600">{{ $completedOrders }}</span>
                    <span class="text-base font-normal text-muted">/</span>
                    <span class="text-rose-600">{{ $canceledOrders }}</span>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted">
                Tỷ lệ hoàn thành: <strong class="text-heading font-semibold">{{ $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 100 }}%</strong>
            </p>
        </div>
    </div>

    <!-- Charts Section (2 Biểu đồ: Cột & Tròn) -->
    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Biểu đồ Cột: Doanh thu theo thời gian -->
        <div class="lg:col-span-7 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs flex flex-col justify-between">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-heading flex items-center gap-2">
                        <span class="size-2 rounded-full bg-amber-500"></span>
                        Biểu đồ Doanh thu (Cột)
                    </h3>
                    <p class="text-xs text-muted mt-0.5">Theo {{ $period === 'day' ? 'khung giờ hôm nay' : ($period === 'week' ? 'ngày trong tuần' : ($period === 'year' ? 'tháng trong năm' : 'tuần trong tháng')) }}</p>
                </div>
                <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full">
                    {{ $periodLabel }}
                </span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="revenueBarChart"></canvas>
            </div>
        </div>

        <!-- Biểu đồ Tròn: Phân bổ Trạng thái Đơn hàng -->
        <div class="lg:col-span-5 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs flex flex-col justify-between">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-heading flex items-center gap-2">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        Cơ cấu Trạng thái Đơn (Tròn)
                    </h3>
                    <p class="text-xs text-muted mt-0.5">Phân bổ tỷ lệ các trạng thái đơn hàng</p>
                </div>
                <span class="text-xs font-bold text-muted bg-surface-alt px-2.5 py-1 rounded-lg border border-ui-border">
                    {{ $totalOrders }} đơn
                </span>
            </div>
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="statusDonutChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Products & Order Breakdown Grid -->
    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Top Selling Products Table -->
        <div class="lg:col-span-1 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
            <h3 class="text-sm font-bold text-heading mb-1">Top sản phẩm bán chạy</h3>
            <p class="text-xs text-muted mb-4">Theo số lượng bán trong kỳ</p>
            <div class="divide-y divide-ui-border text-xs">
                @foreach ($topProducts as $item)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="font-semibold text-heading truncate">{{ $item->name }}</h4>
                            <span class="text-muted text-[11px]">Doanh thu: {{ number_format($item->total_revenue, 0, ',', '.') }}₫</span>
                        </div>
                        <span class="font-bold text-primary shrink-0 bg-primary/10 px-2 py-0.5 rounded-lg text-xs">
                            {{ $item->total_sold }} cái
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Orders in this Period Table -->
        <div class="lg:col-span-2 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-heading">Danh sách đơn hàng trong kỳ</h3>
                    <p class="text-xs text-muted">Hiển thị các đơn mới nhất</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary font-semibold hover:underline">
                    Xem toàn bộ đơn hàng →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-ui-border text-muted uppercase text-[10px] tracking-wider">
                            <th class="py-2.5">Mã đơn</th>
                            <th class="py-2.5">Khách hàng</th>
                            <th class="py-2.5">Thời gian</th>
                            <th class="py-2.5">Trạng thái</th>
                            <th class="py-2.5 text-right">Tổng tiền</th>
                            <th class="py-2.5 text-right">Hóa đơn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ui-border">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-surface-alt/50 transition">
                                <td class="py-3 font-mono font-bold text-heading">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-primary hover:underline">
                                        #{{ $order->order_code }}
                                    </a>
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-heading">{{ $order->customer_name }}</div>
                                    <div class="text-[11px] text-muted">{{ $order->customer_phone }}</div>
                                </td>
                                <td class="py-3 text-muted">
                                    {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '---' }}
                                </td>
                                <td class="py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $order->order_status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($order->order_status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-surface-alt text-muted border-ui-border') }}">
                                        {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                                    </span>
                                </td>
                                <td class="py-3 text-right font-bold text-heading">
                                    {{ number_format($order->total_price, 0, ',', '.') }}₫
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('orders.invoice', $order->order_code) }}" target="_blank" class="text-primary hover:underline font-semibold" title="Xem hóa đơn PDF">
                                        In PDF ↗
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-muted">
                                    Không có đơn hàng nào trong khoảng thời gian này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Biểu đồ Cột - Doanh thu theo thời gian
        const revCanvas = document.getElementById('revenueBarChart');
        if (revCanvas) {
            const revLabels = {!! json_encode($chartData->pluck('label')) !!};
            const revValues = {!! json_encode($chartData->pluck('value')) !!};

            new Chart(revCanvas, {
                type: 'bar',
                data: {
                    labels: revLabels,
                    datasets: [{
                        label: 'Doanh thu (₫)',
                        data: revValues,
                        backgroundColor: 'rgba(217, 119, 6, 0.85)',
                        hoverBackgroundColor: '#b45309',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#181513',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    return ' Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + ' ₫';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                font: { size: 11 },
                                callback: function (val) {
                                    if (val >= 1000000) return (val / 1000000) + ' tr';
                                    if (val >= 1000) return (val / 1000) + ' k';
                                    return val;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 2. Biểu đồ Tròn / Donut - Phân bổ Trạng thái Đơn hàng
        const donutCanvas = document.getElementById('statusDonutChart');
        if (donutCanvas) {
            const completed = {{ (int) $completedOrders }};
            const pending = {{ (int) $pendingOrders }};
            const shipping = {{ (int) $shippingOrders }};
            const canceled = {{ (int) $canceledOrders }};
            const total = completed + pending + shipping + canceled;

            let dLabels = ['Hoàn thành', 'Chờ xử lý', 'Đang giao', 'Đã hủy'];
            let dData = [completed, pending, shipping, canceled];
            let dColors = ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'];

            if (total === 0) {
                dLabels = ['Chưa có đơn hàng'];
                dData = [1];
                dColors = ['#e5e7eb'];
            }

            new Chart(donutCanvas, {
                type: 'doughnut',
                data: {
                    labels: dLabels,
                    datasets: [{
                        data: dData,
                        backgroundColor: dColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 14,
                                font: { size: 11, weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#181513',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    if (total === 0) return ' 0 đơn hàng';
                                    const val = ctx.raw;
                                    const pct = Math.round((val / total) * 100);
                                    return ' ' + ctx.label + ': ' + val + ' đơn (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
