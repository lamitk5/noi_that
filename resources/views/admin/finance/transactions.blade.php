@extends('layouts.admin')

@section('title', 'Giao dịch thanh toán')
@section('page_title', 'Giao dịch thanh toán')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col gap-1">
        <div class="text-xs text-muted flex items-center gap-1.5">
            <span>Quản trị</span>
            <span>/</span>
            <span class="text-heading font-medium">Giao dịch thanh toán</span>
        </div>
        <h1 class="text-xl font-bold text-heading">Giao dịch thanh toán</h1>
        <p class="text-xs text-muted">Tra cứu thanh toán theo đơn hàng và cập nhật trạng thái COD.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.finance.index') }}" class="px-4 py-2 rounded-lg text-xs font-semibold bg-white border border-ui-border text-body hover:bg-surface-alt hover:text-heading transition">
            Thống kê chỉ số
        </a>
        <a href="{{ route('admin.finance.transactions') }}" class="px-4 py-2 rounded-lg text-xs font-semibold bg-gray-900 text-white shadow-xs">
            Giao dịch thanh toán
        </a>
    </div>

    <!-- Errors Notification -->
    @if (isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            <div class="font-bold mb-1">Dữ liệu không hợp lệ:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-ui-border p-5 shadow-xs">
        <form action="{{ route('admin.finance.transactions') }}" method="GET" class="space-y-4">
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

            <div class="flex flex-wrap items-center gap-4 pt-1">
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-medium text-heading mb-1">Sắp xếp</label>
                    <select
                        name="sort"
                        class="w-full text-xs rounded-lg border border-ui-border px-3 py-2 bg-surface text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="amount_asc" {{ ($filters['sort'] ?? '') === 'amount_asc' ? 'selected' : '' }}>Số tiền tăng dần</option>
                        <option value="amount_desc" {{ ($filters['sort'] ?? '') === 'amount_desc' ? 'selected' : '' }}>Số tiền giảm dần</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 sm:self-end">
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition"
                    >
                        Áp dụng bộ lọc
                    </button>
                    <a
                        href="{{ route('admin.finance.transactions') }}"
                        class="px-4 py-2 rounded-lg text-xs font-semibold bg-white border border-ui-border text-body hover:bg-surface-alt transition"
                    >
                        Xóa bộ lọc
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Filter Result Note -->
    <div class="text-xs text-muted">
        Có <span class="font-bold text-heading">{{ number_format($orders->total()) }}</span> đơn phù hợp bộ lọc. Số tiền bao gồm phí vận chuyển; ngày lọc là ngày tạo đơn.
    </div>

    <!-- Transactions Table Card -->
    <div class="bg-white rounded-xl border border-ui-border overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-ui-border">
            <h2 class="text-sm font-bold text-heading">Danh sách giao dịch ({{ $orders->total() }} đơn)</h2>
            <p class="text-[11px] text-muted mt-0.5">COD: xác nhận thu tiền hoặc thất bại; đơn đã thu tiền có thể chuyển sang chờ hoàn tiền rồi xác nhận đã hoàn tiền.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-ui-border uppercase text-[11px]">
                    <tr>
                        <th class="py-3 px-4">Đơn hàng</th>
                        <th class="py-3 px-4">Khách hàng</th>
                        <th class="py-3 px-4">Phương thức</th>
                        <th class="py-3 px-4 text-right">Số tiền</th>
                        <th class="py-3 px-4">Thanh toán</th>
                        <th class="py-3 px-4 text-center">Cập nhật COD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-body">
                    @forelse($orders as $order)
                        @php
                            $isCod = $order->gateway === 'cod' || in_array($order->status, ['cod_ordered', 'cod_paid'], true);
                            $transitions = $isCod ? ($codTransitions[$order->payment_status] ?? []) : [];
                            $statusColor = match($order->payment_status) {
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'initiated' => 'bg-pink-50 text-pink-700 border-pink-200',
                                'failed' => 'bg-red-50 text-red-700 border-red-200',
                                'cancelled' => 'bg-gray-100 text-gray-700 border-gray-200',
                                'refund_pending' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'refunded' => 'bg-purple-50 text-purple-700 border-purple-200',
                                default => 'bg-gray-50 text-gray-600 border-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-surface-alt transition">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="font-bold text-heading hover:text-primary hover:underline">
                                    #{{ $order->id }}
                                </a>
                                @if(!empty($order->order_code))
                                    <div class="text-[11px] font-mono text-muted">{{ $order->order_code }}</div>
                                @endif
                                <div class="text-[11px] text-muted">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-heading">
                                    {{ $order->name ?: ($order->customer_name ?: 'Khách #' . $order->id) }}
                                </div>
                                <div class="text-[11px] text-muted">
                                    {{ $order->phone ?: $order->customer_phone }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-800">
                                    {{ $methods[$order->gateway] ?? strtoupper($order->gateway) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-heading">
                                {{ number_format($order->total_price, 0, ',', '.') }} đ
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $statusColor }}">
                                    {{ $statuses[$order->payment_status] ?? $order->payment_status }}
                                </span>
                                @if(!empty($order->paid_at))
                                    <div class="text-[10px] text-muted mt-1">
                                        {{ \Carbon\Carbon::parse($order->paid_at)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($isCod && count($transitions) > 0)
                                    <form action="{{ route('admin.finance.update-status', $order->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                                        <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                                        <input type="hidden" name="current_payment_id" value="{{ $order->payment_id ?? 0 }}">
                                        <select
                                            name="payment_status"
                                            class="text-xs rounded border border-ui-border px-2 py-1 bg-white text-heading focus:outline-none focus:ring-1 focus:ring-primary"
                                        >
                                            @foreach($transitions as $target)
                                                <option value="{{ $target }}" {{ $target === $order->payment_status ? 'selected' : '' }}>
                                                    {{ $statuses[$target] ?? $target }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="submit"
                                            class="px-2.5 py-1 text-xs font-semibold rounded bg-blue-600 text-white hover:bg-blue-700 transition"
                                        >
                                            Lưu
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-muted">
                                Không có đơn hàng phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-5 py-4 border-t border-ui-border">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
