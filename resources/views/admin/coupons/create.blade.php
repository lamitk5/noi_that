@extends('layouts.admin')

@section('title', 'Tạo Mã Giảm Giá Mới | Mộc An Admin')
@section('page_title', 'Tạo Mã Giảm Giá Mới')

@section('content')
<div class="max-w-3xl">
    <div class="rounded-2xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
        <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="code" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Mã khuyến mãi (Code) *</label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        required
                        value="{{ old('code') }}"
                        placeholder="VD: MOCAN10, TET2026"
                        class="w-full uppercase font-mono rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('code')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Tên chương trình / Mô tả</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        placeholder="VD: Giảm 10% khách hàng mới"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label for="type" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Loại giảm giá *</label>
                    <select
                        name="type"
                        id="type"
                        required
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Số tiền cố định (₫)</option>
                    </select>
                </div>

                <div>
                    <label for="value" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Giá trị giảm *</label>
                    <input
                        type="number"
                        step="any"
                        name="value"
                        id="value"
                        required
                        value="{{ old('value') }}"
                        placeholder="VD: 10 hoặc 50000"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('value')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_discount_amount" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Giảm tối đa (₫)</label>
                    <input
                        type="number"
                        name="max_discount_amount"
                        id="max_discount_amount"
                        value="{{ old('max_discount_amount') }}"
                        placeholder="Chỉ cho % (bỏ trống = ko giới hạn)"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('max_discount_amount')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="min_order_amount" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Đơn hàng tối thiểu (₫)</label>
                    <input
                        type="number"
                        name="min_order_amount"
                        id="min_order_amount"
                        value="{{ old('min_order_amount') }}"
                        placeholder="VD: 500000"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('min_order_amount')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usage_limit" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Giới hạn số lượt dùng</label>
                    <input
                        type="number"
                        name="usage_limit"
                        id="usage_limit"
                        value="{{ old('usage_limit') }}"
                        placeholder="Bỏ trống nếu không giới hạn"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('usage_limit')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="starts_at" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Ngày bắt đầu</label>
                    <input
                        type="datetime-local"
                        name="starts_at"
                        id="starts_at"
                        value="{{ old('starts_at') }}"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                </div>

                <div>
                    <label for="expires_at" class="block text-xs font-bold text-heading uppercase tracking-wider mb-1.5">Ngày hết hạn</label>
                    <input
                        type="datetime-local"
                        name="expires_at"
                        id="expires_at"
                        value="{{ old('expires_at') }}"
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                    @error('expires_at')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                    class="rounded border-ui-border text-primary focus:ring-primary"
                >
                <label for="is_active" class="text-sm font-semibold text-heading cursor-pointer">Kích hoạt mã giảm giá này ngay</label>
            </div>

            <div class="pt-4 flex items-center gap-4">
                <button
                    type="submit"
                    class="rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:opacity-95 transition"
                >
                    Lưu mã giảm giá
                </button>
                <a
                    href="{{ route('admin.coupons.index') }}"
                    class="rounded-xl border border-ui-border px-5 py-2.5 text-sm font-semibold text-body hover:bg-surface-alt transition"
                >
                    Hủy
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
