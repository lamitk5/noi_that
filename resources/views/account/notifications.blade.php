@extends('layouts.app')

@section('title', 'Trung tâm Thông báo - Mộc An')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-muted mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}" class="hover:text-primary transition">Tài khoản</a>
        <span>/</span>
        <span class="text-heading font-semibold">Thông báo</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Sidebar Navigation -->
        <div class="md:col-span-1">
            <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-1.5 text-xs font-semibold">
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
                <a href="{{ route('account.notifications') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-primary text-primary-foreground transition shadow-xs">
                    <span>Thông báo</span>
                </a>
            </div>
        </div>

        <!-- Notification List -->
        <div class="md:col-span-3 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold font-display text-heading">Thông báo của bạn</h1>
                    <p class="text-xs text-muted mt-1">Cập nhật về đơn hàng, ưu đãi và hoạt động tài khoản.</p>
                </div>

                @if($notifications->isNotEmpty())
                    <form method="POST" action="{{ route('account.notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            class="text-xs font-semibold text-primary hover:underline"
                        >
                            Đánh dấu tất cả là đã đọc
                        </button>
                    </form>
                @endif
            </div>

            @if(session('status'))
                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-xs font-medium">
                    {{ session('status') }}
                </div>
            @endif

            <div class="space-y-3">
                @forelse($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isRead = $notification->read_at !== null;
                    @endphp
                    <div class="p-4 rounded-2xl bg-surface border {{ $isRead ? 'border-ui-border opacity-85' : 'border-primary/40 ring-1 ring-primary/20' }} flex items-start justify-between gap-4 transition">
                        <div class="flex items-start gap-3">
                            <div class="size-9 rounded-xl {{ $isRead ? 'bg-surface-alt text-muted' : 'bg-primary/10 text-primary' }} grid place-items-center shrink-0 mt-0.5">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-heading">{{ $data['title'] ?? 'Thông báo hệ thống' }}</h3>
                                    @if(! $isRead)
                                        <span class="size-1.5 rounded-full bg-primary inline-block"></span>
                                    @endif
                                </div>
                                <p class="text-xs text-muted mt-1 leading-relaxed">{{ $data['message'] ?? '' }}</p>
                                <div class="text-[11px] text-muted/70 mt-2 font-mono">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if(isset($data['action_url']))
                                <a
                                    href="{{ $data['action_url'] }}"
                                    class="px-2.5 py-1 rounded-lg bg-surface-alt border border-ui-border text-xs font-medium text-heading hover:bg-surface transition"
                                >
                                    Xem
                                </a>
                            @endif

                            @if(! $isRead)
                                <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="p-1 rounded-lg text-muted hover:text-primary transition"
                                        title="Đánh dấu đã đọc"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center rounded-2xl bg-surface border border-ui-border text-muted">
                        <div class="size-12 mx-auto rounded-full bg-surface-alt grid place-items-center mb-2">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        </div>
                        <p class="text-xs">Bạn chưa có thông báo mới nào.</p>
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="pt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
