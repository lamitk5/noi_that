@extends('layouts.app')

@section('title', 'Xác Thực Tài Khoản | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center size-14 rounded-full bg-primary/10 text-primary mb-3">
                @if(($type ?? '') === 'email')
                    <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                @else
                    <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
            <h1 class="text-2xl font-bold font-display text-heading">
                {{ ($type ?? '') === 'email' ? 'Xác Thực Email' : 'Xác Thực Số Điện Thoại' }}
            </h1>
            <p class="text-xs text-muted mt-1">
                Mã xác thực gồm 6 chữ số đã được gửi tới:
                <span class="font-bold text-heading block text-sm mt-0.5">{{ $target ?? '' }}</span>
            </p>
        </div>

        @if(session('info'))
            <div class="mb-4 p-3 bg-blue-500/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs rounded-xl">
                {{ session('info') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-4 p-3 bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs rounded-xl">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('auth.verify.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5 text-center">
                    Nhập mã xác thực 6 chữ số
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    required
                    autocomplete="one-time-code"
                    placeholder="••••••"
                    class="w-full text-center text-2xl tracking-[0.5em] font-mono rounded-xl border border-ui-border bg-surface-alt p-3 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('code') border-rose-500 @enderror"
                >
                @error('code') <span class="text-xs text-rose-500 block text-center mt-1.5">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition shadow-sm text-sm">
                Xác Nhận
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-ui-border text-center text-xs text-muted">
            <p>Chưa nhận được mã?</p>
            <form action="{{ route('auth.verify.resend') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="text-primary font-bold hover:underline">
                    Gửi lại mã xác thực
                </button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('register') }}" class="text-xs text-muted hover:text-heading transition">
                ← Quay lại trang đăng ký
            </a>
        </div>
    </div>
</div>
@endsection
