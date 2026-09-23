@extends('layouts.admin')

@section('title', 'Khách hàng | Mộc An Admin')
@section('page_title', 'Quản lý Khách hàng')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-ui-border bg-surface p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-heading">Danh sách tài khoản</h2>
                <p class="text-xs text-muted mt-0.5">Khóa/mở khóa tài khoản và xem lịch sử đơn hàng của khách.</p>
            </div>
            <form method="GET" class="flex flex-wrap gap-2">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm tên, email, SĐT..."
                    class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none focus:ring-1 focus:ring-primary w-full sm:w-52"
                >
                <select name="status" class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none focus:ring-1 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status')==='active')>Đang hoạt động</option>
                    <option value="locked" @selected(request('status')==='locked')>Đã khóa</option>
                </select>
                <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:opacity-95 transition">Lọc</button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt uppercase text-muted font-bold border-b border-ui-border">
                    <tr>
                        <th class="py-3 px-4">Khách hàng</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">SĐT</th>
                        <th class="py-3 px-4">Ngày tạo</th>
                        <th class="py-3 px-4">Đơn hàng</th>
                        <th class="py-3 px-4">Tổng chi</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-surface-alt/50 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="size-8 rounded-full bg-primary/10 text-primary grid place-items-center text-[11px] font-bold">
                                        {{ mb_strtoupper(mb_substr($customer->name ?? 'K', 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-heading">{{ $customer->name }}</div>
                                        <div class="text-[10px] text-muted">#{{ $customer->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-body">{{ $customer->email }}</td>
                            <td class="py-3 px-4 text-body">{{ $customer->phone ?: '—' }}</td>
                            <td class="py-3 px-4 text-muted">{{ $customer->created_at?->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-semibold text-heading">{{ $customer->orders_count ?? 0 }}</td>
                            <td class="py-3 px-4 font-semibold text-heading">{{ number_format((float) ($customer->total_spent ?? 0), 0, ',', '.') }}₫</td>
                            <td class="py-3 px-4">
                                @if($customer->isActive())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-600">Hoạt động</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-600">Đã khóa</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-primary hover:underline">Chi tiết</a>
                                    <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="font-semibold {{ $customer->isActive() ? 'text-rose-600 hover:underline' : 'text-emerald-600 hover:underline' }}"
                                        >
                                            {{ $customer->isActive() ? 'Khóa' : 'Mở khóa' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-muted">Không tìm thấy khách hàng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-ui-border px-4 py-3">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection
