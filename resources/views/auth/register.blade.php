@extends('layouts.app')

@section('title', 'Đăng ký tài khoản | Mộc An')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="w-full max-w-md">
        <div class="bg-surface rounded-3xl p-8 sm:p-10 shadow-xl shadow-black/5 border border-ui-border">
            <div class="text-center mb-8">
                <span class="inline-block text-[11px] font-bold uppercase tracking-[0.2em] text-accent mb-2">Thành viên mới</span>
                <h1 class="font-display text-3xl font-semibold text-heading">Tạo tài khoản</h1>
                <p class="mt-2 text-sm text-muted">Gia nhập Mộc An để lưu giữ và theo dõi các đơn hàng nội thất ấm áp cho tổ ấm của bạn.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Họ và tên</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @error('name') border-red-400 ring-2 ring-red-200 @enderror"
                        placeholder="Nguyễn Văn A"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Địa chỉ Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
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
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @error('password') border-red-400 ring-2 ring-red-200 @enderror"
                        placeholder="Tối thiểu 8 ký tự"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-body mb-1.5">Xác nhận mật khẩu</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        placeholder="Nhập lại mật khẩu"
                    >
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full rounded-full bg-primary py-3.5 px-6 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Đăng ký tài khoản
                    </button>
                </div>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-ui-border"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-surface px-3 text-muted font-bold tracking-wider">Hoặc đăng ký nhanh với</span>
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
                <span>Đã có tài khoản?</span>
                <a href="{{ route('login') }}" class="ml-1 font-bold text-primary hover:underline">Đăng nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection