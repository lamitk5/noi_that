@extends('layouts.app')

@section('title', 'Tài khoản của tôi | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Tổng quan</span>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Tài khoản của tôi</h1>
            <p class="mt-2 text-sm text-muted">Quản lý thông tin cá nhân và đơn mua hàng tại Mộc An.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Profile Card -->
            <div class="md:col-span-1 bg-surface rounded-3xl p-6 sm:p-8 shadow-xl shadow-black/5 border border-ui-border flex flex-col items-center text-center">
                <div class="size-20 rounded-full bg-primary text-primary-foreground font-display text-2xl font-bold grid place-items-center shadow-lg shadow-primary/20">
                    {{ mb_strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}
                </div>
                <h2 class="mt-4 font-display text-xl font-semibold text-heading">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $user->email }}</p>
                @if ($user->created_at)
                    <div class="mt-4 inline-flex items-center gap-1.5 text-xs text-muted">
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <span>Tham gia từ {{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>

            <!-- Shortcuts and Details -->
            <div class="md:col-span-2 space-y-6">
                <div class="bg-surface rounded-3xl p-6 sm:p-8 shadow-xl shadow-black/5 border border-ui-border">
                    <h3 class="font-display text-lg font-semibold text-heading mb-4">Lối tắt nhanh</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @if (Route::has('orders.index'))
                            <a href="{{ route('orders.index') }}" class="group flex items-center gap-4 rounded-2xl border border-ui-border p-4 transition-all hover:border-primary hover:bg-surface-alt">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-heading group-hover:text-primary transition">Lịch sử đơn hàng</h4>
                                    <p class="text-xs text-muted">Xem lại các đơn đã mua</p>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('home') }}" class="group flex items-center gap-4 rounded-2xl border border-ui-border p-4 transition-all hover:border-primary hover:bg-surface-alt">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent/10 text-accent transition group-hover:bg-accent group-hover:text-accent-foreground">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 0V21m0 0H2.36m0 0L12 3l9.64 8.349"/></svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-heading group-hover:text-primary transition">Tiếp tục mua sắm</h4>
                                <p class="text-xs text-muted">Khám phá các bộ sưu tập</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="bg-surface rounded-3xl p-6 sm:p-8 shadow-xl shadow-black/5 border border-ui-border">
                    <h3 class="font-display text-lg font-semibold text-heading mb-4">Thông tin cá nhân</h3>
                    <dl class="divide-y divide-ui-border text-sm">
                        <div class="py-3 flex justify-between">
                            <dt class="text-muted">Họ và tên</dt>
                            <dd class="font-medium text-heading">{{ $user->name }}</dd>
                        </div>
                        <div class="py-3 flex justify-between">
                            <dt class="text-muted">Email</dt>
                            <dd class="font-medium text-heading">{{ $user->email }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection