@extends('layouts.app')

@section('title', 'Xác thực đổi mật khẩu | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold font-display text-heading">Xác thực đổi mật khẩu</h1>
            <p class="text-xs text-muted mt-2">
                Mã xác thực gồm 6 chữ số đã được gửi tới
                <span class="font-bold text-heading block text-sm mt-0.5">{{ $target }}</span>
            </p>
        </div>

        @if(session('info'))
            <div class="mb-4 p-3 bg-blue-500/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs rounded-xl">
                {{ session('info') }}
            </div>
        @endif

        <form action="{{ route('profile.password.verify.submit') }}" method="POST" class="space-y-4">
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
                    inputmode="numeric"
                    required
                    autocomplete="one-time-code"
                    placeholder="••••••"
                    class="w-full text-center text-2xl tracking-[0.5em] font-mono rounded-xl border border-ui-border bg-surface-alt p-3 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                >
                @error('code') <span class="text-xs text-rose-500 block text-center mt-1.5">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition shadow-sm text-sm">
                Xác nhận mật khẩu mới
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-ui-border text-center text-xs text-muted">
            <p>Chưa nhận được mã?</p>
            <form action="{{ route('profile.password.resend') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="text-primary font-bold hover:underline">Gửi lại mã</button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('profile.edit') }}" class="text-xs text-muted hover:text-heading transition">← Quay lại hồ sơ</a>
        </div>
    </div>
</div>
@endsection
