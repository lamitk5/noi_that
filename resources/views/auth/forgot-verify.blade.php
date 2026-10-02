@extends('layouts.app')

@section('title', 'Xác Thực Đặt Lại Mật Khẩu | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center size-14 rounded-full bg-primary/10 text-primary mb-3">
                <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold font-display text-heading">Nhập Mã Xác Thực</h1>
            <p class="text-xs text-muted mt-1">Mã gồm 6 chữ số, có hiệu lực trong 10 phút. Bạn được nhập sai tối đa 5 lần.</p>
        </div>

        @include('auth.partials.flash')

        <form action="{{ route('password.verify.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5 text-center">Mã xác thực</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    required
                    autofocus
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
            <form action="{{ route('password.resend') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="text-primary font-bold hover:underline">Gửi lại mã</button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('password.request') }}" class="text-xs text-muted hover:text-heading transition">← Nhập tài khoản khác</a>
        </div>
    </div>
</div>
@endsection
