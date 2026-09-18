@extends('layouts.admin')

@section('title', 'Tạo Mã Khuyến Mãi - Quản trị Mộc An')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Tạo Mã Khuyến Mãi Mới</h1>
            <p class="text-xs text-muted mt-1">Thiết lập điều kiện giảm giá theo phần trăm hoặc số tiền cố định.</p>
        </div>
        <a
            href="{{ route('admin.vouchers.index') }}"
            class="px-3.5 py-2 rounded-xl bg-surface border border-ui-border text-xs font-semibold text-heading hover:bg-surface-alt transition"
        >
            Quay lại
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vouchers.store') }}" class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Mã Voucher (Code) <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    placeholder="VD: MOCAN50K, TET2026..."
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading uppercase font-mono font-bold focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Tên chương trình <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="VD: Ưu đãi tân gia 2026"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Loại giảm giá <span class="text-red-500">*</span></label>
                <select
                    name="type"
                    required
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="fixed" @selected(old('type') === 'fixed')>Số tiền cố định (VNĐ)</option>
                    <option value="percent" @selected(old('type') === 'percent')>Theo phần trăm (%)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Giá trị giảm <span class="text-red-500">*</span></label>
                <input
                    type="number"
                    name="value"
                    value="{{ old('value') }}"
                    required
                    min="1"
                    placeholder="VD: 50000 (đ) hoặc 10 (%)"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Đơn hàng tối thiểu (VNĐ)</label>
                <input
                    type="number"
                    name="min_order_amount"
                    value="{{ old('min_order_amount', 0) }}"
                    min="0"
                    step="1000"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Giảm tối đa (Khi áp dụng %)</label>
                <input
                    type="number"
                    name="max_discount"
                    value="{{ old('max_discount') }}"
                    min="0"
                    step="1000"
                    placeholder="Để trống nếu không giới hạn"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Giới hạn số lượt dùng</label>
                <input
                    type="number"
                    name="usage_limit"
                    value="{{ old('usage_limit') }}"
                    min="1"
                    placeholder="Để trống nếu không giới hạn"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Ngày hết hạn (Tùy chọn)</label>
                <input
                    type="datetime-local"
                    name="expires_at"
                    value="{{ old('expires_at') }}"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', 1))
                        class="size-4 rounded border-ui-border text-primary"
                    >
                    <span class="text-xs font-medium text-heading">Kích hoạt áp dụng mã ngay lập tức</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button
                type="submit"
                class="px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
            >
                Lưu mã khuyến mãi
            </button>
        </div>
    </form>
</div>
@endsection
