@extends('layouts.app')

@section('title', 'Yêu cầu hỗ trợ | Mộc An')

@section('content')
<div class="page-shell py-12">
    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('home') }}" class="hover:text-heading">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}" class="hover:text-heading">Tài khoản</a>
        <span>/</span>
        <span class="text-heading font-medium">Yêu cầu hỗ trợ</span>
    </nav>

    <div class="grid md:grid-cols-4 gap-8 items-start">
        <!-- Sidebar Navigation -->
        <div class="md:col-span-1 rounded-3xl border border-ui-border bg-surface p-6 shadow-xs">
            <h2 class="text-xs font-bold uppercase tracking-widest text-muted mb-4">Menu tài khoản</h2>
            <div class="space-y-1 text-sm font-medium">
                <a href="{{ route('account.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Hồ sơ cá nhân</span>
                </a>
                <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Lịch sử đơn hàng</span>
                </a>
                <a href="{{ route('account.wishlist') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Sản phẩm yêu thích</span>
                </a>
                <a href="{{ route('account.loyalty') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Điểm thưởng & Hạng thẻ</span>
                </a>
                <a href="{{ route('account.notifications') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Thông báo</span>
                </a>
                <a href="{{ route('account.tickets.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-primary text-primary-foreground transition shadow-xs">
                    <span>Yêu cầu hỗ trợ</span>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="md:col-span-3 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold font-display text-heading">Yêu cầu hỗ trợ & Khiếu nại</h1>
                    <p class="text-xs text-muted mt-1">Theo dõi tiến trình giải quyết thắc mắc, đổi trả và bảo hành.</p>
                </div>
                <a
                    href="{{ route('account.tickets.create') }}"
                    class="px-4 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs"
                >
                    + Tạo yêu cầu mới
                </a>
            </div>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if($tickets->isEmpty())
                <div class="text-center py-12 rounded-3xl border border-dashed border-ui-border bg-surface p-8">
                    <svg viewBox="0 0 24 24" class="size-12 text-muted mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
                    <p class="text-sm font-medium text-heading">Bạn chưa có yêu cầu hỗ trợ nào.</p>
                    <p class="text-xs text-muted mt-1">Cần hỗ trợ về đơn hàng hoặc sản phẩm? Đừng ngần ngại liên hệ với chúng tôi.</p>
                    <a href="{{ route('account.tickets.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-95">
                        Tạo yêu cầu ngay →
                    </a>
                </div>
            @else
                <div class="divide-y divide-ui-border rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs">
                    @foreach($tickets as $ticket)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-surface-alt/40 transition">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-accent">{{ $ticket->ticket_code }}</span>
                                    <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $ticket->statusBadgeClass() }}">
                                        {{ $ticket->statusLabel() }}
                                    </span>
                                    <span class="text-xs text-muted">· Ưu tiên: {{ $ticket->priorityLabel() }}</span>
                                </div>
                                <h2 class="text-sm font-bold text-heading">
                                    <a href="{{ route('account.tickets.show', $ticket->ticket_code) }}" class="hover:text-primary transition">
                                        {{ $ticket->subject }}
                                    </a>
                                </h2>
                                <p class="text-xs text-muted line-clamp-1">{{ $ticket->message }}</p>
                                <div class="text-[11px] text-muted/70">
                                    Cập nhật gần nhất: {{ $ticket->last_reply_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}
                                    · {{ $ticket->replies_count ?? $ticket->replies()->count() }} phản hồi
                                </div>
                            </div>
                            <div class="shrink-0">
                                <a
                                    href="{{ route('account.tickets.show', $ticket->ticket_code) }}"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-ui-border bg-surface-alt text-xs font-semibold text-heading hover:border-primary hover:text-primary transition"
                                >
                                    <span>Chi tiết</span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
