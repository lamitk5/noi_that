@extends('layouts.app')

@section('title', 'Đăng Ký Tài Khoản | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <h1 class="text-2xl sm:text-3xl font-bold font-display text-heading text-center mb-2">Đăng Ký Tài Khoản</h1>
        <p class="text-xs text-muted text-center mb-6">Trở thành thành viên để nhận các ưu đãi nội thất hấp dẫn</p>

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

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <!-- 1. Username -->
            <div>
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">
                    1. Tên đăng nhập (Username) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autocomplete="username"
                    placeholder="Ví dụ: nguyenvanan"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('username') border-rose-500 @enderror"
                >
                @error('username') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <!-- 2. Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">
                    2. Họ và tên <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                    placeholder="Ví dụ: Nguyễn Văn An"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('name') border-rose-500 @enderror"
                >
                @error('name') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <!-- 3. Email or Phone -->
            <div>
                <label for="email_or_phone" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">
                    3. Email hoặc Số điện thoại <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="email_or_phone"
                    name="email_or_phone"
                    value="{{ old('email_or_phone') }}"
                    required
                    placeholder="email@domain.com hoặc 0912345678"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('email_or_phone') border-rose-500 @enderror"
                >
                @error('email_or_phone') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <!-- 4. Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">
                    4. Mật khẩu <span class="text-rose-500">*</span>
                </label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Tối thiểu 6 ký tự"
                        class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('password') border-rose-500 @enderror"
                    >
                    @include('partials.password-toggle')
                </div>
                @error('password') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <!-- 5. Password Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">
                    5. Nhập lại mật khẩu <span class="text-rose-500">*</span>
                </label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Nhập lại chính xác mật khẩu"
                        class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
                    >
                    @include('partials.password-toggle')
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition mt-2 shadow-sm text-sm">
                Đăng Ký
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
            <span>Đăng ký với Google</span>
        </a>

        <div class="mt-6 pt-4 border-t border-ui-border text-center text-xs text-muted">
            Đã có tài khoản? <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">Đăng nhập</a>
        </div>
    </div>
</div>
@endsection
