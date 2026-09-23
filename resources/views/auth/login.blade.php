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

            @if (session('error'))
                <div class="mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 p-4 text-xs font-semibold text-red-600 dark:text-red-400 flex items-center gap-2.5">
                    <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2.5">
                    <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="mb-6 rounded-2xl border border-amber-500/25 bg-amber-500/10 p-3.5 text-xs text-heading">
                <div class="font-bold text-accent mb-1">Tài khoản Quản trị viên (Admin):</div>
                <div class="space-y-1 text-muted text-[11px]">
                    <div>Tài khoản: <strong class="text-heading font-mono select-all">admin@mocan.test</strong> hoặc <strong class="text-heading font-mono select-all">admin</strong></div>
                    <div>Mật khẩu: <strong class="text-heading font-mono select-all">Admin@123</strong></div>
                </div>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="login" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Email, SĐT hoặc Tên đăng nhập</label>
                    <input
                        id="login"
                        type="text"
                        name="login"
                        value="{{ old('login', old('email')) }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @if($errors->has('login') || $errors->has('email')) border-red-400 ring-2 ring-red-200 @endif"
                        placeholder="email@example.com, 0901234567 hoặc username"
                    >
                    @if ($errors->has('login'))
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $errors->first('login') }}</p>
                    @elseif ($errors->has('email'))
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $errors->first('email') }}</p>
                    @endif
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
                        class="w-full rounded-full bg-primary py-3.5 px-6 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Đăng nhập
                    </button>
                </div>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-ui-border"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-surface px-3 text-muted font-bold tracking-wider">Hoặc tiếp tục với</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a
                    href="{{ route('auth.social.redirect', 'google') }}"
                    class="flex items-center justify-center gap-2.5 rounded-xl border border-ui-border bg-surface-alt py-2.5 px-4 text-xs font-bold text-heading transition-all hover:bg-surface hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
                    <svg class="size-4" viewBox="0 0 24 24">
                        <path fill="#EA4335" d="M12 5c1.54 0 2.93.56 4.02 1.48l3.01-3.01C17.21 1.76 14.81 1 12 1 7.37 1 3.48 3.66 1.63 7.51l3.66 2.84C6.17 7.35 8.84 5 12 5z"/>
                        <path fill="#4285F4" d="M23.49 12.27c0-.8-.07-1.57-.2-2.27H12v4.55h6.45c-.28 1.47-1.11 2.72-2.36 3.56l3.66 2.84c2.14-1.97 3.74-4.88 3.74-8.68z"/>
                        <path fill="#FBBC05" d="M5.29 14.65c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2L1.63 7.51C.59 9.58 0 11.96 0 14.5s.59 4.92 1.63 6.99l3.66-2.84z"/>
                        <path fill="#34A853" d="M12 23c3.24 0 5.95-1.07 7.93-2.91l-3.66-2.84c-1.07.72-2.45 1.15-4.27 1.15-3.16 0-5.83-2.35-6.71-5.35L1.63 15.89C3.48 19.74 7.37 23 12 23z"/>
                    </svg>
                    <span>Google</span>
                </a>

                <a
                    href="{{ route('auth.social.redirect', 'github') }}"
                    class="flex items-center justify-center gap-2.5 rounded-xl border border-ui-border bg-surface-alt py-2.5 px-4 text-xs font-bold text-heading transition-all hover:bg-surface hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
                    <svg class="size-4 text-heading fill-current" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                    </svg>
                    <span>GitHub</span>
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