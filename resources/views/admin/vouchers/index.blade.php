@extends('layouts.admin')

@section('title', 'Quản lý Mã Khuyến Mãi - Quản trị Mộc An')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Quản lý Mã Khuyến Mãi & Voucher</h1>
            <p class="text-xs text-muted mt-1">Cấu hình mã giảm giá, giới hạn lượt dùng và thời gian áp dụng.</p>
        </div>
        <a
            href="{{ route('admin.vouchers.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
        >
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>Tạo mã mới</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="p-4 rounded-2xl bg-surface border border-ui-border">
        <form method="GET" action="{{ route('admin.vouchers.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Tìm theo mã code hoặc tên chương trình..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                    name="status"
                    class="w-full sm:w-auto px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang kích hoạt</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Đang tắt</option>
                </select>
                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl bg-surface-alt border border-ui-border text-xs font-semibold text-heading hover:bg-surface transition shrink-0"
                >
                    Lọc
                </button>
            </div>
        </form>
    </div>

    <!-- Vouchers Table -->
    <div class="rounded-2xl bg-surface border border-ui-border overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-ui-border bg-surface-alt/50 text-muted font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Mã Voucher</th>
                        <th class="py-3 px-4">Tên chương trình</th>
                        <th class="py-3 px-4">Mức giảm</th>
                        <th class="py-3 px-4">Đơn tối thiểu</th>
                        <th class="py-3 px-4">Đã dùng / Giới hạn</th>
                        <th class="py-3 px-4">Hạn dùng</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($vouchers as $voucher)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3 px-4 font-mono font-bold text-primary">
                                {{ $voucher->code }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-heading">
                                {{ $voucher->name }}
                            </td>
                            <td class="py-3 px-4 font-bold text-heading">
                                @if($voucher->type === 'percent')
                                    {{ (int) $voucher->value }}%
                                    @if($voucher->max_discount)
                                        <span class="text-[10px] text-muted font-normal block">Tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ</span>
                                    @endif
                                @else
                                    {{ number_format($voucher->value, 0, ',', '.') }}đ
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $voucher->used_count }} / {{ $voucher->usage_limit ?? '∞' }}
                            </td>
                            <td class="py-3 px-4 text-muted">
                                @if($voucher->expires_at)
                                    {{ $voucher->expires_at->format('d/m/Y') }}
                                @else
                                    Vô thời hạn
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($voucher->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface-alt text-muted border border-ui-border">
                                        Đang tắt
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a
                                        href="{{ route('admin.vouchers.edit', $voucher) }}"
                                        class="p-1.5 rounded-lg text-primary hover:bg-primary/10 transition"
                                        title="Chỉnh sửa"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mã này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-500/10 transition"
                                            title="Xóa"
                                        >
                                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-muted">Chưa có mã khuyến mãi nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
