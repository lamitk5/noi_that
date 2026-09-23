@extends('layouts.admin')

@section('title', 'Quản lý mã giảm giá | Mộc An Admin')
@section('page_title', 'Danh Sách Mã Giảm Giá & Khuyến Mãi')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-4 text-sm text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-base font-bold text-heading">Danh Sách Mã Giảm Giá</h2>
                <p class="text-xs text-muted mt-0.5">Tạo các chương trình khuyến mãi, voucher giảm giá theo phần trăm hoặc số tiền cố định.</p>
            </div>
            <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground hover:opacity-95 transition self-start sm:self-auto">
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                <span>Tạo mã mới</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt uppercase text-muted font-bold border-b border-ui-border">
                    <tr>
                        <th class="py-3 px-4">Mã Code</th>
                        <th class="py-3 px-4">Tên chương trình</th>
                        <th class="py-3 px-4">Mức giảm</th>
                        <th class="py-3 px-4">Đơn tối thiểu</th>
                        <th class="py-3 px-4">Đã dùng / Giới hạn</th>
                        <th class="py-3 px-4">Hạn dùng</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-body">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-surface-alt/50 transition-colors">
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-heading px-2 py-1 rounded-lg bg-surface-alt border border-ui-border">
                                    {{ $coupon->code }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-medium text-heading">
                                {{ $coupon->name ?: '—' }}
                            </td>
                            <td class="py-3 px-4 font-bold text-emerald-600">
                                @if($coupon->type === 'percent')
                                    Giảm {{ (float) $coupon->value }}%
                                    @if($coupon->max_discount_amount)
                                        <div class="text-[10px] text-muted font-normal">(tối đa {{ number_format((float) $coupon->max_discount_amount, 0, ',', '.') }}₫)</div>
                                    @endif
                                @else
                                    Giảm {{ number_format((float) $coupon->value, 0, ',', '.') }}₫
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                {{ $coupon->min_order_amount ? number_format((float) $coupon->min_order_amount, 0, ',', '.') . '₫' : 'Không yêu cầu' }}
                            </td>
                            <td class="py-3 px-4 font-mono">
                                {{ $coupon->used_count }} / {{ $coupon->usage_limit ?: '∞' }}
                            </td>
                            <td class="py-3 px-4 text-muted">
                                @if($coupon->expires_at)
                                    {{ $coupon->expires_at->format('d/m/Y H:i') }}
                                @else
                                    Vô thời hạn
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($coupon->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 dark:bg-stone-800 px-2 py-0.5 text-[10px] font-semibold text-stone-600 dark:text-stone-400">
                                        Tạm dừng
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="font-semibold text-accent hover:underline">Sửa</a>
                                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa mã giảm giá này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-red-500 hover:underline">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-muted">Chưa có mã giảm giá nào. Hãy tạo mã khuyến mãi đầu tiên!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $coupons->links() }}
        </div>
    </div>
</div>
@endsection
