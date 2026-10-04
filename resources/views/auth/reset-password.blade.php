@extends('layouts.app')

@section('title', 'Đặt Lại Mật Khẩu | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <h1 class="text-2xl sm:text-3xl font-bold font-display text-heading text-center mb-2">Đặt Lại Mật Khẩu</h1>
        <p class="text-xs text-muted text-center mb-6">Mật khẩu mới cần ít nhất 8 ký tự, gồm cả chữ và số. Sau khi đổi, mọi phiên đăng nhập khác sẽ bị đăng xuất.</p>

        @include('auth.partials.flash')

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">Mật khẩu mới</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('password') border-rose-500 @enderror"
                    >
                    @include('partials.password-toggle')
                </div>
                @error('password') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">Nhập lại mật khẩu mới</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                    >
                    @include('partials.password-toggle')
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition shadow-sm text-sm">
                Cập Nhật Mật Khẩu
            </button>
        </form>
    </div>
</div>
@endsection
