@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10 sm:py-14">
    <div class="page-shell max-w-3xl">
        <p class="eyebrow">Tài khoản</p>
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-heading">Hồ sơ cá nhân</h1>
        <p class="mt-3 text-sm text-muted">Cập nhật thông tin liên hệ của bạn. Email/số điện thoại trùng với tài khoản khác sẽ không được chấp nhận.</p>

        <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
            <a href="{{ route('profile.edit') }}" class="rounded-lg bg-primary px-3.5 py-2 text-primary-foreground">Hồ sơ</a>
            <a href="{{ route('orders.index') }}" class="rounded-lg border border-ui-border bg-surface px-3.5 py-2 text-heading hover:bg-surface-alt transition">Lịch sử đơn hàng</a>
        </div>

        @if (session('success'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="mt-6 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Họ tên *</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Email *</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Số điện thoại</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Địa chỉ</label>
                    <input id="address" type="text" name="address" value="{{ old('address', $user->address) }}"
                           class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                    @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 pt-2 border-t border-ui-border">
                    <p class="text-[11px] font-bold text-heading uppercase tracking-wider mb-3">Đổi mật khẩu (không bắt buộc)</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="current_password" class="block text-xs font-bold text-muted mb-1.5">Mật khẩu hiện tại</label>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                                   class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <div>
                            <label for="password" class="block text-xs font-bold text-muted mb-1.5">Mật khẩu mới</label>
                            <input id="password" type="password" name="password" autocomplete="new-password"
                                   class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-muted mb-1.5">Nhập lại mật khẩu mới</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                                   class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                        </div>
                    </div>
                    @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="pt-4 border-t border-ui-border flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground hover:opacity-95 transition">Lưu thay đổi</button>
                <span class="text-[11px] text-muted">Tên đăng nhập: {{ $user->username }}</span>
            </div>
        </form>
    </div>
</div>
@endsection
