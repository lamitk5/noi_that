@extends('layouts.admin')

@section('title', 'Yêu cầu hỗ trợ khách hàng | Admin Mộc An')
@section('header', 'Hỗ trợ khách hàng')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Danh sách yêu cầu hỗ trợ</h1>
            <p class="text-xs text-muted mt-0.5">Tiếp nhận và phản hồi các thắc mắc, yêu cầu từ khách hàng.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="rounded-3xl border border-ui-border bg-surface p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm theo mã TK, tên, email, chủ đề..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                >
            </div>
            <div>
                <select name="status" class="rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none">
                    <option value="">Tất cả trạng thái</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Đang chờ xử lý</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Đang xử lý</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Đã giải quyết</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Đã đóng</option>
                </select>
            </div>
            <div>
                <select name="priority" class="rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none">
                    <option value="">Tất cả mức độ</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Thấp</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Bình thường</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Cao</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 cursor-pointer">
                Lọc
            </button>
            @if(request()->hasAny(['q', 'status', 'priority']))
                <a href="{{ route('admin.tickets.index') }}" class="px-3 py-2 rounded-xl border border-ui-border text-xs text-muted hover:text-heading">
                    Đặt lại
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt border-b border-ui-border text-muted uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3 px-4">Mã TK</th>
                        <th class="py-3 px-4">Khách hàng</th>
                        <th class="py-3 px-4">Tiêu đề yêu cầu</th>
                        <th class="py-3 px-4">Mức độ</th>
                        <th class="py-3 px-4 text-center">Trạng thái</th>
                        <th class="py-3 px-4">Cập nhật cuối</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-heading">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-accent">{{ $ticket->ticket_code }}</td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold block">{{ $ticket->name }}</span>
                                <span class="text-muted block text-[11px]">{{ $ticket->email }}</span>
                                @if($ticket->phone)
                                    <span class="text-muted block text-[11px]">{{ $ticket->phone }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-bold hover:text-primary transition">
                                    {{ $ticket->subject }}
                                </a>
                                <span class="text-muted line-clamp-1 text-[11px] mt-0.5">{{ $ticket->message }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-medium">
                                {{ $ticket->priorityLabel() }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $ticket->statusBadgeClass() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-muted text-[11px]">
                                {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a
                                    href="{{ route('admin.tickets.show', $ticket) }}"
                                    class="inline-flex items-center gap-1 font-semibold text-primary hover:underline"
                                >
                                    <span>Xử lý</span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-muted">Không có yêu cầu hỗ trợ nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
