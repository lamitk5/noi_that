@extends('layouts.admin')

@section('title', 'Sửa Nhân viên')
@section('page_title', 'Sửa Nhân viên: ' . $staff->name)

@section('content')
<div class="max-w-2xl bg-surface rounded-xl border border-ui-border p-6">
    <form action="{{ route('admin.staff.update', $staff) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Họ tên *</label>
                <input type="text" name="name" value="{{ old('name', $staff->name) }}" required class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Tên đăng nhập *</label>
                <input type="text" name="username" value="{{ old('username', $staff->username) }}" required class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('username') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $staff->email) }}" required class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('phone') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Vai trò *</label>
                <select name="role" required class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                    <option value="staff" {{ old('role', $staff->role) === 'staff' ? 'selected' : '' }}>Nhân viên</option>
                    <option value="manager" {{ old('role', $staff->role) === 'manager' ? 'selected' : '' }}>Quản lý</option>
                </select>
                @error('role') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-heading cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }} class="rounded text-primary">
                    Đang hoạt động
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Mật khẩu mới (để trống nếu không đổi)</label>
                <input type="password" name="password" class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
                @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-muted mb-1">Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="w-full text-sm border border-ui-border rounded-lg p-2.5 bg-page text-heading outline-none focus:ring-1 focus:ring-primary">
            </div>
        </div>

        <div class="pt-4 flex items-center gap-3 border-t border-ui-border">
            <button type="submit" class="bg-primary hover:opacity-90 text-primary-foreground font-bold text-xs px-6 py-2.5 rounded-lg transition">Cập nhật</button>
            <a href="{{ route('admin.staff.index') }}" class="text-xs text-muted hover:underline">Quay lại</a>
        </div>
    </form>
</div>
@endsection
