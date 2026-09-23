@extends('layouts.admin')

@section('title', 'Chi tiết khách hàng | Mộc An Admin')
@section('page_title', 'Khách hàng: '.$customer->name)

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.customers.index') }}" class="text-xs font-semibold text-primary hover:underline">&larr; Quay lại danh sách</a>
        <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $customer->isActive() ? 'bg-rose-500/10 text-rose-600 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20' }}">
                {{ $customer->isActive() ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
            </button>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Tổng đơn hàng</span>
            <div class="mt-2 text-2xl font-bold font-display text-heading">{{ $ordersCount }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Tổng chi tiêu</span>
            <div class="mt-2 text-2xl font-bold font-display text-heading">{{ number_format($totalSpent, 0, ',', '.') }}₫</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Trạng thái</span>
            <div class="mt-2">
                @if($customer->isActive())
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-600">Đang hoạt động</span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-600">Đã khóa</span>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
        <h3 class="text-sm font-bold text-heading mb-4">Thông tin tài khoản</h3>
        <dl class="grid gap-3 sm:grid-cols-2 text-xs">
            <div><dt class="text-muted font-semibold uppercase">Họ tên</dt><dd class="mt-1 font-medium text-heading">{{ $customer->name }}</dd></div>
            <div><dt class="text-muted font-semibold uppercase">Email</dt><dd class="mt-1 font-medium text-heading">{{ $customer->email }}</dd></div>
            <div><dt class="text-muted font-semibold uppercase">Số điện thoại</dt><dd class="mt-1 font-medium text-heading">{{ $customer->phone ?: '—' }}</dd></div>
            <div><dt class="text-muted font-semibold uppercase">Ngày tạo</dt><dd class="mt-1 font-medium text-heading">{{ $customer->created_at?->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-muted font-semibold uppercase">Vai trò</dt><dd class="mt-1 font-medium text-heading">{{ $customer->role === 'customer' ? 'Khách hàng' : $customer->role }}</dd></div>
            <div><dt class="text-muted font-semibold uppercase">Đăng nhập gần nhất</dt><dd class="mt-1 font-medium text-heading">{{ $customer->updated_at?->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-ui-border">
            <h3 class="text-sm font-bold text-heading">Lịch sử đơn hàng</h3>
            <p class="text-xs text-muted mt-0.5">Tối đa 20 đơn gần nhất.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt uppercase text-muted font-bold border-b border-ui-border">
                    <tr>
                        <th class="py-3 px-4">Mã đơn</th>
                        <th class="py-3 px-4">Ngày đặt</th>
                        <th class="py-3 px-4">Tổng tiền</th>
                        <th class="py-3 px-4">Thanh toán</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($customer->orders as $order)
                        <tr class="hover:bg-surface-alt/50">
                            <td class="py-3 px-4 font-mono font-bold text-heading">{{ $order->order_code }}</td>
                            <td class="py-3 px-4 text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-4 font-bold text-heading">{{ number_format((float) $order->total_price, 0, ',', '.') }}₫</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-500/10 text-emerald-600' : ($order->payment_status === 'failed' ? 'bg-rose-500/10 text-rose-600' : 'bg-amber-500/10 text-amber-600') }}">
                                    {{ $order->payment_status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $order->order_status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : ($order->order_status === 'canceled' ? 'bg-rose-500/10 text-rose-600' : 'bg-amber-500/10 text-amber-600') }}">
                                    {{ $order->order_status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary hover:underline">Xem</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-muted">Khách hàng chưa có đơn hàng nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
