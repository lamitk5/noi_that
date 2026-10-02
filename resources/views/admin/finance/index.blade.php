@extends('layouts.admin')

@section('title', 'Thống kê tài chính')
@section('page_title', 'Thống kê tài chính')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-1">
        <div class="text-xs text-muted flex items-center gap-1.5">
            <span>Quản trị</span>
            <span>/</span>
            <span class="text-heading font-medium">Thống kê tài chính</span>
        </div>
        <h1 class="text-xl font-bold text-heading">Thống kê tài chính</h1>
        <p class="text-xs text-muted">Tổng hợp giá trị thanh toán theo trạng thái và phương thức</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-lg text-xs font-semibold bg-gray-900 text-white shadow-xs">
            Thống kê chỉ số
        </a>
        <a href="{{ route('admin.finance.transactions') }}" class="px-4 py-2 rounded-lg text-xs font-semibold bg-white border border-ui-border text-body hover:bg-surface-alt hover:text-heading transition">
            Giao dịch thanh toán
        </a>
    </div>

    <!-- Errors Notification -->
    @if (isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            <div class="font-bold mb-1">Dữ liệu lọc không hợp lệ:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-ui-border p-5 shadow-xs">
        <form action="{{ route('admin.finance.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Tìm đơn hàng</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Mã đơn, tên hoặc số điện thoại"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Từ ngày tạo đơn</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Đến ngày tạo đơn</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Số tiền từ (đ)</label>
                    <input
                        type="number"
                        name="min_amount"
                        value="{{ $filters['min_amount'] ?? '' }}"
                        placeholder="Không giới hạn"
                        min="0"
                        step="1000"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Số tiền đến (đ)</label>
                    <input
                        type="number"
                        name="max_amount"
                        value="{{ $filters['max_amount'] ?? '' }}"
                        placeholder="Không giới hạn"
                        min="0"
                        step="1000"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Phương thức</label>
                    <select
                        name="gateway"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Tất cả</option>
                        @foreach($methods as $key => $name)
                            <option value="{{ $key }}" {{ ($filters['gateway'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-heading mb-1">Trạng thái thanh toán</label>
                    <select
                        name="payment_status"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Tất cả</option>
                        @foreach($statuses as $key => $name)
                            <option value="{{ $key }}" {{ ($filters['payment_status'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition"
                >
                    Áp dụng bộ lọc
                </button>
                <a
                    href="{{ route('admin.finance.index') }}"
                    class="px-4 py-2 rounded-lg text-xs font-semibold bg-white border border-ui-border text-body hover:bg-surface-alt transition"
                >
                    Xóa bộ lọc
                </a>
            </div>
        </form>
    </div>

    <!-- Filter Result Note -->
    <div class="text-xs text-muted">
        Có <span class="font-bold text-heading">{{ number_format($summary->order_count ?? 0) }}</span> đơn phù hợp. Số tiền bao gồm phí vận chuyển; thống kê theo ngày tạo đơn trên toàn bộ kết quả lọc.
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Tổng giá trị đơn hàng -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Tổng giá trị đơn hàng</div>
            <div class="text-xl font-bold text-heading mt-2">
                {{ number_format($summary->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($summary->order_count ?? 0) }} đơn, bao gồm đơn đã hủy
            </div>
        </div>

        <!-- Card 2: Chờ thanh toán -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Chờ thanh toán</div>
            <div class="text-xl font-bold text-amber-600 mt-2">
                {{ number_format($statusTotals['pending']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['pending']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 3: Đang chờ MoMo -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Đang chờ MoMo</div>
            <div class="text-xl font-bold text-pink-600 mt-2">
                {{ number_format($statusTotals['initiated']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['initiated']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 4: Đã thanh toán -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Đã thanh toán</div>
            <div class="text-xl font-bold text-emerald-600 mt-2">
                {{ number_format($statusTotals['paid']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['paid']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 5: Thanh toán thất bại -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Thanh toán thất bại</div>
            <div class="text-xl font-bold text-red-600 mt-2">
                {{ number_format($statusTotals['failed']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['failed']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 6: Đã hủy -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Đã hủy</div>
            <div class="text-xl font-bold text-gray-500 mt-2">
                {{ number_format($statusTotals['cancelled']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['cancelled']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 7: Chờ hoàn tiền -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Chờ hoàn tiền</div>
            <div class="text-xl font-bold text-indigo-600 mt-2">
                {{ number_format($statusTotals['refund_pending']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['refund_pending']->order_count ?? 0) }} đơn
            </div>
        </div>

        <!-- Card 8: Đã hoàn tiền -->
        <div class="bg-white rounded-xl border border-ui-border p-4 shadow-xs">
            <div class="text-xs font-medium text-muted">Đã hoàn tiền</div>
            <div class="text-xl font-bold text-purple-600 mt-2">
                {{ number_format($statusTotals['refunded']->total_amount ?? 0, 0, ',', '.') }} đ
            </div>
            <div class="text-[11px] text-muted mt-1">
                {{ number_format($statusTotals['refunded']->order_count ?? 0) }} đơn
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown Table -->
    <div class="bg-white rounded-xl border border-ui-border overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-ui-border">
            <h2 class="text-sm font-bold text-heading">Thống kê theo phương thức</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-ui-border uppercase text-[11px]">
                    <tr>
                        <th class="py-3 px-5">Phương thức</th>
                        <th class="py-3 px-5 text-center">Số đơn</th>
                        <th class="py-3 px-5 text-right">Tổng giá trị</th>
                        <th class="py-3 px-5 text-right">Đã thanh toán</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-body">
                    @foreach($methods as $key => $name)
                        <tr class="hover:bg-surface-alt transition">
                            <td class="py-3.5 px-5 font-semibold text-heading">
                                {{ $name }}
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                {{ number_format($methodTotals[$key]->order_count ?? 0) }}
                            </td>
                            <td class="py-3.5 px-5 text-right font-medium">
                                {{ number_format($methodTotals[$key]->total_amount ?? 0, 0, ',', '.') }} đ
                            </td>
                            <td class="py-3.5 px-5 text-right font-semibold text-emerald-700">
                                {{ number_format($methodTotals[$key]->paid_amount ?? 0, 0, ',', '.') }} đ
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
