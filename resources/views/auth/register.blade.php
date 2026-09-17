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

            <div class="mt-8 border-t border-ui-border pt-6 text-center text-sm text-muted">
                <span>Đã có tài khoản?</span>
                <a href="{{ route('login') }}" class="ml-1 font-bold text-primary hover:underline">Đăng nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection