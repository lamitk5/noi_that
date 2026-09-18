@extends('layouts.admin')

@section('title', 'Quản lý Tồn kho & Cảnh báo - Quản trị Mộc An')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Quản lý Tồn kho & Cảnh báo Hàng</h1>
            <p class="text-xs text-muted mt-1">Theo dõi số lượng tồn kho theo từng biến thể SKU, nhận diện sớm sản phẩm sắp hết hàng.</p>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface border border-ui-border text-xs text-muted">
            <span>Ngưỡng cảnh báo sắp hết:</span>
            <span class="font-bold text-heading font-mono">&le; {{ $threshold }} đơn vị</span>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase">Tổng số biến thể SKU</div>
            <div class="text-xl font-bold font-display text-heading mt-1">{{ number_format($summary['total_variants']) }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-ui-border">
            <div class="text-[11px] font-semibold text-muted uppercase">Tổng số lượng trong kho</div>
            <div class="text-xl font-bold font-display text-primary mt-1">{{ number_format($summary['total_stock']) }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-amber-500/30 bg-amber-500/5">
            <div class="text-[11px] font-semibold text-amber-500 uppercase">Sắp hết hàng (&le; {{ $threshold }})</div>
            <div class="text-xl font-bold font-display text-amber-500 mt-1">{{ number_format($summary['low_stock']) }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-surface border border-red-500/30 bg-red-500/5">
            <div class="text-[11px] font-semibold text-red-500 uppercase">Hết hàng (0)</div>
            <div class="text-xl font-bold font-display text-red-500 mt-1">{{ number_format($summary['out_of_stock']) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-2xl bg-surface border border-ui-border">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Tìm theo SKU biến thể hoặc tên sản phẩm..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                    name="filter"
                    class="w-full sm:w-auto px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="all" @selected($filters['filter'] === 'all')>Tất cả trạng thái kho</option>
                    <option value="low_stock" @selected($filters['filter'] === 'low_stock')>Sắp hết hàng (&le; {{ $threshold }})</option>
                    <option value="out_of_stock" @selected($filters['filter'] === 'out_of_stock')>Hết hàng (0)</option>
                    <option value="in_stock" @selected($filters['filter'] === 'in_stock')>Còn dồi dào (&gt; {{ $threshold }})</option>
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

    <!-- Inventory Table -->
    <div class="rounded-2xl bg-surface border border-ui-border overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-ui-border bg-surface-alt/50 text-muted font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Mã SKU</th>
                        <th class="py-3 px-4">Sản phẩm</th>
                        <th class="py-3 px-4">Thuộc tính biến thể</th>
                        <th class="py-3 px-4">Đơn giá</th>
                        <th class="py-3 px-4">Trạng thái kho</th>
                        <th class="py-3 px-4 text-center">Cập nhật số lượng</th>
                        <th class="py-3 px-4 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($variants as $variant)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3 px-4 font-mono font-bold text-heading">
                                {{ $variant->sku }}
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.products.edit', $variant->product) }}" class="font-semibold text-heading hover:text-primary transition line-clamp-1">
                                    {{ $variant->product->name }}
                                </a>
                                <span class="text-[11px] text-muted">{{ $variant->product->category?->name ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4 text-muted">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($variant->color)<span class="px-1.5 py-0.5 rounded bg-surface-alt border border-ui-border text-[10px]">{{ $variant->color }}</span>@endif
                                    @if($variant->size)<span class="px-1.5 py-0.5 rounded bg-surface-alt border border-ui-border text-[10px]">{{ $variant->size }}</span>@endif
                                    @if($variant->material)<span class="px-1.5 py-0.5 rounded bg-surface-alt border border-ui-border text-[10px]">{{ $variant->material }}</span>@endif
                                </div>
                            </td>
                            <td class="py-3 px-4 font-medium text-heading">
                                {{ number_format($variant->price, 0, ',', '.') }}đ
                            </td>
                            <td class="py-3 px-4">
                                @if($variant->stock <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-500 border border-red-500/20">
                                        Hết hàng (0)
                                    </span>
                                @elseif($variant->stock <= $threshold)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                        Sắp hết ({{ $variant->stock }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Còn hàng ({{ $variant->stock }})
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('admin.inventory.update', $variant) }}" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <input
                                        type="number"
                                        name="stock"
                                        value="{{ $variant->stock }}"
                                        min="0"
                                        class="w-20 px-2 py-1 text-xs rounded-lg bg-surface-alt border border-ui-border text-center font-bold text-heading focus:outline-hidden focus:border-primary"
                                    >
                                    <button
                                        type="submit"
                                        class="px-2.5 py-1 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground font-semibold text-[11px] transition shadow-2xs"
                                        title="Lưu số lượng"
                                    >
                                        Lưu
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a
                                    href="{{ route('admin.products.edit', $variant->product) }}"
                                    class="p-1.5 rounded-lg text-muted hover:text-primary hover:bg-surface-alt transition inline-block"
                                    title="Quản lý sản phẩm"
                                >
                                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-muted">
                                Không tìm thấy biến thể nào phù hợp với bộ lọc tồn kho.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($variants->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $variants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
