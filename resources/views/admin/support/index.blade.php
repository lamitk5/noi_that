@extends('layouts.admin')

@section('title', 'Hoàn hàng & Khiếu nại | Mộc An Admin')
@section('page_title', 'Hoàn hàng & Khiếu nại')

@section('content')
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Chờ tiếp nhận</span>
            <div class="mt-2 text-2xl font-bold font-display text-amber-600">{{ $stats['pending'] }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Đang mở</span>
            <div class="mt-2 text-2xl font-bold font-display text-sky-600">{{ $stats['open'] }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Yêu cầu hoàn hàng</span>
            <div class="mt-2 text-2xl font-bold font-display text-heading">{{ $stats['returns'] }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Khiếu nại</span>
            <div class="mt-2 text-2xl font-bold font-display text-heading">{{ $stats['complaints'] }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface p-5 sm:p-6 shadow-xs">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Mã yêu cầu, mã đơn, khách hàng..."
                   class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none focus:ring-1 focus:ring-primary w-full sm:w-64">
            <select name="type" class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none">
                <option value="">Mọi loại</option>
                @foreach(\App\Models\SupportRequest::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none">
                <option value="">Mọi trạng thái</option>
                @foreach(\App\Models\SupportRequest::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:opacity-95 transition">Lọc</button>
        </form>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-surface-alt/60 text-[11px] uppercase tracking-wider text-muted">
                <tr>
                    <th class="px-5 py-3 font-bold">Mã</th>
                    <th class="px-5 py-3 font-bold">Loại</th>
                    <th class="px-5 py-3 font-bold">Khách hàng</th>
                    <th class="px-5 py-3 font-bold">Đơn hàng</th>
                    <th class="px-5 py-3 font-bold">Lý do</th>
                    <th class="px-5 py-3 font-bold">Ngày gửi</th>
                    <th class="px-5 py-3 font-bold">Trạng thái</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ui-border">
                @forelse($requests as $req)
                    <tr class="hover:bg-surface-alt/40">
                        <td class="px-5 py-3 font-mono font-bold text-heading">{{ $req->code }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $req->type === 'return' ? 'bg-primary/10 text-primary' : 'bg-rose-500/10 text-rose-600' }}">{{ $req->typeLabel() }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-semibold text-heading">{{ $req->user?->name ?? 'Khách' }}</span>
                            <span class="block text-[11px] text-muted">{{ $req->user?->email }}</span>
                        </td>
                        <td class="px-5 py-3">
                            @if($req->order)
                                <a href="{{ route('admin.orders.show', $req->order) }}" class="text-primary font-semibold hover:underline">#{{ $req->order->order_code }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-body max-w-56 truncate">{{ $req->reasonLabel() }}</td>
                        <td class="px-5 py-3 text-muted whitespace-nowrap">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold whitespace-nowrap {{ $req->statusTone() }}">{{ $req->statusLabel() }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.support.show', $req) }}" class="rounded-lg border border-ui-border px-3 py-1.5 font-semibold text-heading hover:bg-surface-alt transition whitespace-nowrap">Xử lý</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-muted">Chưa có yêu cầu nào phù hợp.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $requests->links() }}</div>
</div>
@endsection
