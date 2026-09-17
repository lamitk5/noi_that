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
                        class="w-full rounded-full bg-primary py-3.5 px-6 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Đăng nhập
                    </button>
                </div>
            </form>

            <div class="mt-8 border-t border-ui-border pt-6 text-center text-sm text-muted">
                <span>Chưa có tài khoản?</span>
                <a href="{{ route('register') }}" class="ml-1 font-bold text-primary hover:underline">Đăng ký ngay</a>
            </div>
        </div>
    </div>
</div>
@endsection