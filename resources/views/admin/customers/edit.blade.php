@extends('layouts.admin')

@section('title', 'Sửa khách hàng | Mộc An Admin')
@section('page_title', 'Sửa khách hàng: ' . $customer->name)

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.customers.show', $customer) }}" class="text-xs font-semibold text-primary hover:underline">&larr; Quay lại chi tiết khách hàng</a>

    <form action="{{ route('admin.customers.update', $customer) }}" method="POST" class="mt-4 rounded-2xl border border-ui-border bg-surface p-6 shadow-xs space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Họ tên *</label>
                <input id="name" type="text" name="name" value="{{ old('name', $customer->name) }}" required
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="username" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Tên đăng nhập</label>
                <input id="username" type="text" name="username" value="{{ old('username', $customer->username) }}"
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Email *</label>
                <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" required
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Số điện thoại</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Địa chỉ</label>
                <input id="address" type="text" name="address" value="{{ old('address', $customer->address) }}"
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Mật khẩu mới</label>
                <input id="password" type="password" name="password" autocomplete="new-password" placeholder="Để trống nếu không đổi"
                       class="w-full rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-heading cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active)) class="rounded text-primary">
                    Tài khoản đang hoạt động
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-ui-border flex items-center gap-3">
            <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground hover:opacity-95 transition">Lưu thay đổi</button>
            <a href="{{ route('admin.customers.show', $customer) }}" class="text-xs font-semibold text-muted hover:text-heading">Hủy bỏ</a>
        </div>

        <p class="text-[11px] text-muted leading-relaxed">
            Email và số điện thoại được đối chiếu tự động với dữ liệu trong hệ thống — nếu đã tồn tại ở tài khoản khác, hệ thống sẽ báo lỗi và không lưu.
        </p>
    </form>
</div>
@endsection
