@extends('layouts.admin')

@section('title', 'Xử lý yêu cầu ' . $ticket->ticket_code . ' | Admin Mộc An')
@section('header', 'Chi tiết yêu cầu hỗ trợ')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tickets.index') }}" class="text-xs font-semibold text-muted hover:text-heading flex items-center gap-1">
            <span aria-hidden="true">←</span>
            <span>Quay lại danh sách</span>
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Ticket Details Card -->
    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-ui-border pb-5 mb-5">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="font-mono text-xs font-bold text-accent">{{ $ticket->ticket_code }}</span>
                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $ticket->statusBadgeClass() }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                    <span class="text-xs text-muted">· Ưu tiên: {{ $ticket->priorityLabel() }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold font-display text-heading">{{ $ticket->subject }}</h1>
                <div class="text-xs text-muted mt-2">
                    Người gửi: <strong class="text-heading">{{ $ticket->name }}</strong> ({{ $ticket->email }})
                    @if($ticket->phone) · SĐT: {{ $ticket->phone }} @endif
                </div>
            </div>

            <!-- Quick Status Change Form -->
            <form method="POST" action="{{ route('admin.tickets.update-status', $ticket) }}" class="flex items-center gap-2 shrink-0">
                @csrf
                @method('PATCH')
                <select name="status" class="rounded-xl border border-ui-border bg-surface-alt px-3 py-1.5 text-xs text-heading focus:border-primary focus:outline-none">
                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Đang chờ xử lý</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>Đang xử lý</option>
                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Đã giải quyết</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Đã đóng</option>
                </select>
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-surface-alt border border-ui-border text-xs font-bold text-heading hover:border-primary cursor-pointer">
                    Cập nhật
                </button>
            </form>
        </div>

        <!-- Initial Message -->
        <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-5">
            <div class="flex items-center justify-between text-xs text-muted mb-2">
                <span class="font-semibold text-heading">{{ $ticket->name }}</span>
                <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-sm text-body leading-relaxed whitespace-pre-line">{{ $ticket->message }}</div>
        </div>
    </div>

    <!-- Replies History -->
    <div class="space-y-4">
        <h2 class="text-sm font-bold text-heading uppercase tracking-wider">Lịch sử phản hồi ({{ $ticket->replies->count() }})</h2>

        @foreach($ticket->replies as $reply)
            <div class="rounded-2xl border p-5 {{ $reply->is_admin ? 'border-primary/30 bg-primary/5 ml-6' : 'border-ui-border bg-surface mr-6' }}">
                <div class="flex items-center justify-between text-xs mb-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold {{ $reply->is_admin ? 'text-primary' : 'text-heading' }}">
                            {{ $reply->author_name ?? ($reply->is_admin ? 'Quản trị viên Mộc An' : 'Khách hàng') }}
                        </span>
                        @if($reply->is_admin)
                            <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary">Admin</span>
                        @endif
                    </div>
                    <span class="text-muted text-[11px]">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="text-sm text-body leading-relaxed whitespace-pre-line">{{ $reply->message }}</div>
            </div>
        @endforeach
    </div>

    <!-- Admin Reply Form -->
    <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
        <h3 class="text-sm font-bold text-heading uppercase tracking-wider mb-4">Gửi câu trả lời cho khách hàng</h3>
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="space-y-4">
            @csrf

            <textarea
                name="message"
                rows="5"
                required
                placeholder="Nhập nội dung phản hồi, hướng dẫn hoặc giải pháp gửi tới khách hàng..."
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
            ></textarea>
            @error('message')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
            @enderror

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div class="flex items-center gap-2 text-xs">
                    <label class="text-muted">Chuyển trạng thái sang:</label>
                    <select name="new_status" class="rounded-xl border border-ui-border bg-surface-alt px-3 py-1.5 text-xs text-heading focus:border-primary focus:outline-none">
                        <option value="in_progress">Đang xử lý</option>
                        <option value="resolved" selected>Đã giải quyết</option>
                        <option value="closed">Đóng yêu cầu</option>
                    </select>
                </div>
                <button
                    type="submit"
                    class="px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
                >
                    Gửi phản hồi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
