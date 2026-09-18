@extends('layouts.admin')

@section('title', 'Chỉnh sửa Voucher: ' . $voucher->code . ' - Quản trị Mộc An')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Chỉnh sửa Mã: {{ $voucher->code }}</h1>
            <p class="text-xs text-muted mt-1">Cập nhật thông tin mã khuyến mãi.</p>
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

    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}" class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Mã Voucher (Code) <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="code"
                    value="{{ old('code', $voucher->code) }}"
                    required
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading uppercase font-mono font-bold focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Tên chương trình <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $voucher->name) }}"
                    required
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
                    <option value="fixed" @selected(old('type', $voucher->type) === 'fixed')>Số tiền cố định (VNĐ)</option>
                    <option value="percent" @selected(old('type', $voucher->type) === 'percent')>Theo phần trăm (%)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Giá trị giảm <span class="text-red-500">*</span></label>
                <input
                    type="number"
                    name="value"
                    value="{{ old('value', (int) $voucher->value) }}"
                    required
                    min="1"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Đơn hàng tối thiểu (VNĐ)</label>
                <input
                    type="number"
                    name="min_order_amount"
                    value="{{ old('min_order_amount', (int) $voucher->min_order_amount) }}"
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
                    value="{{ old('max_discount', $voucher->max_discount ? (int) $voucher->max_discount : '') }}"
                    min="0"
                    step="1000"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Giới hạn số lượt dùng</label>
                <input
                    type="number"
                    name="usage_limit"
                    value="{{ old('usage_limit', $voucher->usage_limit) }}"
                    min="1"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold text-heading mb-1.5">Ngày hết hạn</label>
                <input
                    type="datetime-local"
                    name="expires_at"
                    value="{{ old('expires_at', $voucher->expires_at ? $voucher->expires_at->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $voucher->is_active))
                        class="size-4 rounded border-ui-border text-primary"
                    >
                    <span class="text-xs font-medium text-heading">Kích hoạt áp dụng mã</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button
                type="submit"
                class="px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
            >
                Cập nhật mã khuyến mãi
            </button>
        </div>
    </form>
</div>
@endsection
