@extends('layouts.app')

@section('title', 'Yêu cầu ' . $ticket->ticket_code . ' | Mộc An')

@section('content')
<div class="page-shell py-12">
    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('home') }}" class="hover:text-heading">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.tickets.index') }}" class="hover:text-heading">Yêu cầu hỗ trợ</a>
        <span>/</span>
        <span class="text-heading font-medium">{{ $ticket->ticket_code }}</span>
    </nav>

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Ticket Meta Header Card -->
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
                </div>
                <div class="text-xs text-muted sm:text-right">
                    <span>Khởi tạo: {{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    <span class="block text-[11px] mt-0.5">Cập nhật: {{ $ticket->last_reply_at?->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Original Message -->
            <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-5">
                <div class="flex items-center justify-between text-xs text-muted mb-2">
                    <span class="font-semibold text-heading">{{ $ticket->name }} (Người gửi)</span>
                    <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="text-sm text-body leading-relaxed whitespace-pre-line">{{ $ticket->message }}</div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl border border-rose-500/30 bg-rose-50/50 dark:bg-rose-950/20 p-4 text-xs text-rose-800 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <!-- Conversation Stream -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-heading uppercase tracking-wider">Lịch sử trao đổi ({{ $ticket->replies->count() }})</h2>

            @foreach($ticket->replies as $reply)
                <div class="rounded-2xl border p-5 transition {{ $reply->is_admin ? 'border-primary/30 bg-primary/5 ml-4 sm:ml-8' : 'border-ui-border bg-surface mr-4 sm:mr-8' }}">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold {{ $reply->is_admin ? 'text-primary' : 'text-heading' }}">
                                {{ $reply->author_name ?? ($reply->is_admin ? 'Hỗ trợ Mộc An' : 'Khách hàng') }}
                            </span>
                            @if($reply->is_admin)
                                <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary">Quản trị viên</span>
                            @endif
                        </div>
                        <span class="text-muted text-[11px]">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="text-sm text-body leading-relaxed whitespace-pre-line">{{ $reply->message }}</div>
                </div>
            @endforeach
        </div>

        <!-- Reply Form -->
        @if($ticket->status !== \App\Models\SupportTicket::STATUS_CLOSED)
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
                <h3 class="text-sm font-bold text-heading uppercase tracking-wider mb-4">Gửi thêm phản hồi</h3>
                <form method="POST" action="{{ route('account.tickets.reply', $ticket->ticket_code) }}" class="space-y-4">
                    @csrf
                    <textarea
                        name="message"
                        rows="4"
                        required
                        placeholder="Nhập thông tin phản hồi của bạn..."
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                    ></textarea>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
                        >
                            Gửi phản hồi
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="rounded-2xl border border-ui-border bg-surface-alt/40 p-4 text-center text-xs text-muted">
                Yêu cầu này đã được đóng. Nếu bạn vẫn cần hỗ trợ, vui lòng <a href="{{ route('account.tickets.create') }}" class="text-primary font-semibold hover:underline">tạo yêu cầu mới</a>.
            </div>
        @endif
    </div>
</div>
@endsection
