@extends('layouts.app')

@section('title', 'Chỉnh sửa thông tin cá nhân | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-2xl mx-auto">
        <!-- Navigation / Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-8" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('account.index') }}" class="hover:text-heading transition-colors">Tài khoản</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Chỉnh sửa hồ sơ</span>
        </nav>

        <div class="mb-8">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Hồ sơ cá nhân</span>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Chỉnh sửa thông tin</h1>
            <p class="mt-2 text-sm text-muted">Cập nhật họ tên và địa chỉ email liên kết với tài khoản Mộc An của bạn.</p>
        </div>

        <div class="bg-surface rounded-3xl p-6 sm:p-10 shadow-xl shadow-black/5 border border-ui-border">
            <form method="POST" action="{{ route('account.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-heading mb-2">
                        Họ và tên <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition @error('name') border-red-500 @enderror"
                        placeholder="Nguyễn Văn A"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-heading mb-2">
                        Địa chỉ Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-3 text-sm text-body placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition @error('email') border-red-500 @enderror"
                        placeholder="email@example.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-muted">Email dùng để đăng nhập và nhận thông báo đơn hàng.</p>
                </div>

                <!-- Actions -->
                <div class="pt-4 flex items-center justify-end gap-3 border-t border-ui-border">
                    <a
                        href="{{ route('account.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-ui-border bg-surface px-5 py-2.5 text-sm font-semibold text-muted hover:text-heading hover:border-heading/40 transition"
                    >
                        Hủy
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground shadow-sm transition hover:opacity-90"
                    >
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
