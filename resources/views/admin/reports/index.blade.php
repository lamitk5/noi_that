@extends('layouts.admin')

@section('title', 'Báo cáo Doanh thu & Bán chạy - Quản trị Mộc An')

@section('content')
<div class="space-y-6">
    <!-- Header & Date Range Filter -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Báo cáo & Phân tích Kinh doanh</h1>
            <p class="text-xs text-muted mt-1">Dữ liệu doanh thu thực nhận dựa trên các đơn hàng đã hoàn tất và thanh toán thành công.</p>
        </div>

        <!-- Presets and Date Picker -->
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('admin.reports.index', ['preset' => '7days']) }}"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === '7days' ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:bg-surface-alt' }}"
            >
                7 ngày qua
            </a>
            <a
                href="{{ route('admin.reports.index', ['preset' => '30days']) }}"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === '30days' ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:bg-surface-alt' }}"
            >
                30 ngày qua
            </a>
            <a
                href="{{ route('admin.reports.index', ['preset' => 'this_month']) }}"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:bg-surface-alt' }}"
            >
                Tháng này
            </a>
            <a
                href="{{ route('admin.reports.index', ['preset' => 'all_time']) }}"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'all_time' ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:bg-surface-alt' }}"
            >
                Tất cả thời gian
            </a>

            <!-- Custom Form -->
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-1.5 ml-2">
                <input
                    type="date"
                    name="from_date"
                    value="{{ $startDate }}"
                    class="px-2.5 py-1 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                <span class="text-xs text-muted">-</span>
                <input
                    type="date"
                    name="to_date"
                    value="{{ $endDate }}"
                    class="px-2.5 py-1 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                <button
                    type="submit"
                    class="px-3 py-1 rounded-lg bg-surface-alt border border-ui-border text-xs font-semibold text-heading hover:bg-surface transition"
                >
                    Lọc
                </button>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Doanh thu thực tế</div>
            <div class="text-2xl font-bold font-display text-primary mt-1">
                {{ number_format($metrics['total_revenue'], 0, ',', '.') }}đ
            </div>
            <div class="text-[11px] text-muted mt-1">Đơn hoàn tất & thanh toán</div>
        </div>
        <div class="p-5 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Số đơn hoàn thành</div>
            <div class="text-2xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['total_orders']) }}
            </div>
            <div class="text-[11px] text-muted mt-1">Giao hàng thành công</div>
        </div>
        <div class="p-5 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Giá trị trung bình đơn (AOV)</div>
            <div class="text-2xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['aov'], 0, ',', '.') }}đ
            </div>
            <div class="text-[11px] text-muted mt-1">Doanh thu / Đơn hàng</div>
        </div>
        <div class="p-5 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Sản phẩm đã xuất bán</div>
            <div class="text-2xl font-bold font-display text-heading mt-1">
                {{ number_format($metrics['items_sold']) }}
            </div>
            <div class="text-[11px] text-muted mt-1">Tổng sản phẩm đã giao</div>
        </div>
    </div>

    <!-- Behavioral & Conversion Funnel Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Lượt xem sản phẩm</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($behavioralSummary['total_views'] ?? 0) }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Hành vi người dùng</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Lượt thêm vào giỏ</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($behavioralSummary['total_cart_adds'] ?? 0) }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Sự quan tâm mua hàng</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Bắt đầu thanh toán</div>
            <div class="text-xl font-bold font-display text-heading mt-1">
                {{ number_format($behavioralSummary['total_checkouts'] ?? 0) }}
            </div>
            <div class="text-[10px] text-muted mt-0.5">Vào trang Checkout</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Tỷ lệ bỏ quên giỏ hàng</div>
            <div class="text-xl font-bold font-display text-amber-600 dark:text-amber-400 mt-1">
                {{ $behavioralSummary['cart_abandonment_rate'] ?? 0 }}%
            </div>
            <div class="text-[10px] text-muted mt-0.5">(Thêm giỏ - Checkout) / Thêm giỏ</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Methods Breakdown -->
        <div class="lg:col-span-1 p-5 rounded-2xl bg-surface border border-ui-border space-y-4">
            <h2 class="text-sm font-bold text-heading">Phương thức Thanh toán</h2>
            <div class="space-y-3">
                @forelse($paymentBreakdown as $pay)
                    <div class="p-3 rounded-xl bg-surface-alt/50 border border-ui-border">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold uppercase text-heading">{{ $pay->payment_method }}</span>
                            <span class="font-bold text-primary">{{ number_format($pay->total_revenue, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-muted">
                            <span>{{ $pay->order_count }} đơn hàng</span>
                            <span>{{ $pay->share_percentage }}% doanh thu</span>
                        </div>
                        <div class="w-full h-1.5 bg-ui-border rounded-full overflow-hidden mt-2">
                            <div class="h-full bg-primary" style="width: {{ $pay->share_percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-muted">
                        Chưa có giao dịch thanh toán nào trong giai đoạn này.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="lg:col-span-2 p-5 rounded-2xl bg-surface border border-ui-border space-y-4">
            <h2 class="text-sm font-bold text-heading">Top Sản phẩm Bán chạy nhất</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="text-muted font-semibold uppercase tracking-wider text-[11px] border-b border-ui-border">
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Tên sản phẩm</th>
                            <th class="py-2.5 px-3 text-center">Đã bán</th>
                            <th class="py-2.5 px-3 text-right">Doanh thu thu về</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ui-border">
                        @forelse($topProducts as $index => $prod)
                            <tr class="hover:bg-surface-alt/30 transition">
                                <td class="py-2.5 px-3 font-bold text-muted">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3 font-semibold text-heading">{{ $prod->product_name }}</td>
                                <td class="py-2.5 px-3 text-center font-bold text-heading">{{ $prod->total_quantity }}</td>
                                <td class="py-2.5 px-3 text-right font-medium text-primary">{{ number_format($prod->total_revenue, 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-muted">Chưa có sản phẩm nào phát sinh doanh thu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-4">
        <h2 class="text-sm font-bold text-heading">Chi tiết Doanh thu theo Ngày</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-alt/50 text-muted font-semibold uppercase tracking-wider text-[11px] border-b border-ui-border">
                        <th class="py-3 px-4">Ngày</th>
                        <th class="py-3 px-4 text-center">Số đơn hoàn thành</th>
                        <th class="py-3 px-4 text-right">Doanh thu trong ngày</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($dailyRecords as $record)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3 px-4 font-semibold text-heading">{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 text-center font-bold text-heading">{{ $record->order_count }}</td>
                            <td class="py-3 px-4 text-right font-bold text-primary">{{ number_format($record->daily_revenue, 0, ',', '.') }}đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-muted">Không có dữ liệu trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
