@extends('layouts.app')

@section('title', 'Đăng Nhập Tài Khoản | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <h1 class="text-2xl sm:text-3xl font-bold font-display text-heading text-center mb-2">Đăng Nhập</h1>
        <p class="text-xs text-muted text-center mb-6">Truy cập tài khoản cá nhân hoặc bảng quản trị</p>

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

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">Tên đăng nhập / Email / SĐT</label>
                <input
                    type="text"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="Username, email hoặc số điện thoại"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('email') border-rose-500 @enderror"
                >
                @error('email') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">Mật khẩu</label>
                <input
                    type="password"
                    name="password"
                    required
                    placeholder="••••••••"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('password') border-rose-500 @enderror"
                >
                @error('password') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between text-xs pt-0.5">
                <label class="flex items-center text-muted cursor-pointer hover:text-heading transition">
                    <input type="checkbox" name="remember" class="mr-2 rounded border-ui-border bg-surface-alt text-primary focus:ring-primary">
                    Ghi nhớ đăng nhập
                </label>
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition shadow-sm text-sm">
                Đăng Nhập
            </button>
        </form>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-ui-border"></div>
            </div>
            <div class="relative flex justify-center text-[11px] uppercase tracking-wider">
                <span class="bg-surface px-3 text-muted font-medium">Hoặc tiếp tục với</span>
            </div>
        </div>

        <a
            href="{{ route('auth.social.redirect', 'google') }}"
            class="w-full flex items-center justify-center gap-3 py-2.5 px-4 border border-ui-border rounded-xl text-sm font-semibold text-heading bg-surface hover:bg-surface-alt transition shadow-xs"
        >
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.98 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
            </svg>
            <span>Đăng nhập bằng Google</span>
        </a>

        <div class="mt-6 pt-4 border-t border-ui-border text-center text-xs text-muted">
            Chưa có tài khoản? <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">Đăng ký ngay</a>
        </div>
    </div>
</div>
@endsection
