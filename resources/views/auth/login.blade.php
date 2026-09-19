@extends('layouts.app')

@section('title', 'Đăng nhập | Mộc An')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="w-full max-w-md">
        <div class="bg-surface rounded-3xl p-8 sm:p-10 shadow-xl shadow-black/5 border border-ui-border">
            <div class="text-center mb-8">
                <span class="inline-block text-[11px] font-bold uppercase tracking-[0.2em] text-accent mb-2">Chào mừng trở lại</span>
                <h1 class="font-display text-3xl font-semibold text-heading">Đăng nhập</h1>
                <p class="mt-2 text-sm text-muted">Đăng nhập vào tài khoản Mộc An để xem các đơn hàng và quản lý thông tin của bạn.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Địa chỉ Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @error('email') border-red-400 ring-2 ring-red-200 @enderror"
                        placeholder="email@example.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Mật khẩu</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @error('password') border-red-400 ring-2 ring-red-200 @enderror"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="size-4 rounded border-ui-border bg-surface-alt text-primary focus:ring-primary/20"
                        >
                        <span class="text-xs font-medium text-muted">Ghi nhớ đăng nhập</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full rounded-full bg-primary py-3.5 px-6 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 cursor-pointer"
                    >
                        Đăng nhập
                    </button>
                </div>
            </form>

            <div class="my-6 flex items-center gap-3">
                <div class="h-px flex-1 bg-ui-border"></div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Hoặc tiếp tục với</span>
                <div class="h-px flex-1 bg-ui-border"></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a
                    href="{{ route('auth.social.redirect', 'google') }}"
                    class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border border-ui-border hover:bg-surface-alt transition text-xs font-semibold text-heading shadow-2xs"
                >
                    <svg class="size-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.98 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                    </svg>
                    <span>Google</span>
                </a>

                <a
                    href="{{ route('auth.social.redirect', 'facebook') }}"
                    class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border border-ui-border hover:bg-surface-alt transition text-xs font-semibold text-heading shadow-2xs"
                >
                    <span class="size-4 rounded-full bg-[#1877F2] text-white flex items-center justify-center text-[11px] font-bold">f</span>
                    <span>Facebook</span>
                </a>
            </div>

            <div class="mt-8 border-t border-ui-border pt-6 text-center text-sm text-muted">
                <span>Chưa có tài khoản?</span>
                <a href="{{ route('register') }}" class="ml-1 font-bold text-primary hover:underline">Đăng ký ngay</a>
            </div>
        </div>
    </div>
</div>
@endsection