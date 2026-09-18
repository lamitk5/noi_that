@extends('layouts.admin')

@section('title', 'Quản lý Sản phẩm - Quản trị Mộc An')

@section('content')
<div class="space-y-6">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Quản lý Sản phẩm</h1>
            <p class="text-xs text-muted mt-1">Danh sách tất cả sản phẩm, trạng thái hiển thị và tổng tồn kho biến thể.</p>
        </div>
        <a
            href="{{ route('admin.products.create') }}"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
        >
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>Thêm sản phẩm mới</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-2xl bg-surface border border-ui-border">
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Tìm tên hoặc mã SKU..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
            </div>
            <div>
                <select
                    name="category_id"
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select
                    name="status"
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected($filters['status'] === 'active')>Đang bán (Hiển thị)</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Đang ẩn / Tạm ngưng</option>
                </select>
            </div>
            <div class="flex gap-2">
                <select
                    name="stock_status"
                    class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                >
                    <option value="">Tất cả tồn kho</option>
                    <option value="in_stock" @selected($filters['stock_status'] === 'in_stock')>Còn hàng</option>
                    <option value="low_stock" @selected($filters['stock_status'] === 'low_stock')>Sắp hết hàng</option>
                    <option value="out_of_stock" @selected($filters['stock_status'] === 'out_of_stock')>Hết hàng</option>
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

    <!-- Products Table -->
    <div class="rounded-2xl bg-surface border border-ui-border overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-ui-border bg-surface-alt/50 text-muted font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 w-16">Hình ảnh</th>
                        <th class="py-3 px-4">Sản phẩm</th>
                        <th class="py-3 px-4">Danh mục</th>
                        <th class="py-3 px-4">Giá gốc</th>
                        <th class="py-3 px-4">Số biến thể</th>
                        <th class="py-3 px-4">Tổng tồn</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($products as $product)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3 px-4">
                                <div class="size-12 rounded-lg bg-surface-alt border border-ui-border overflow-hidden flex items-center justify-center">
                                    @if($product->primaryImage)
                                        <img src="{{ asset('storage/' . $product->primaryImage->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <svg viewBox="0 0 24 24" class="size-5 text-muted" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-heading hover:text-primary transition line-clamp-1">
                                    {{ $product->name }}
                                </a>
                                <div class="text-[11px] text-muted font-mono mt-0.5">SKU: {{ $product->sku }}</div>
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $product->category?->name ?? '—' }}
                            </td>
                            <td class="py-3 px-4 font-medium text-heading">
                                {{ number_format($product->base_price, 0, ',', '.') }}đ
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $product->variants_count }} biến thể
                            </td>
                            <td class="py-3 px-4">
                                @php $stock = (int) $product->variants_sum_stock; @endphp
                                @if($stock <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-500/10 text-red-500 border border-red-500/20">
                                        Hết hàng (0)
                                    </span>
                                @elseif($stock <= \App\Models\Product::lowStockThreshold())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                        Sắp hết ({{ $stock }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Còn hàng ({{ $stock }})
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($product->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                        Hiển thị
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface-alt text-muted border border-ui-border">
                                        Đang ẩn
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a
                                        href="{{ route('products.show', $product->slug) }}"
                                        target="_blank"
                                        class="p-1.5 rounded-lg text-muted hover:text-heading hover:bg-surface-alt transition"
                                        title="Xem ngoài trang bán hàng"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    </a>
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        class="p-1.5 rounded-lg text-primary hover:bg-primary/10 transition"
                                        title="Chỉnh sửa chi tiết"
                                    >
                                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="p-1.5 rounded-lg {{ $product->is_active ? 'text-amber-500 hover:bg-amber-500/10' : 'text-emerald-500 hover:bg-emerald-500/10' }} transition"
                                            title="{{ $product->is_active ? 'Ẩn sản phẩm' : 'Kích hoạt hiển thị' }}"
                                        >
                                            @if($product->is_active)
                                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                            @else
                                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-muted">
                                <p>Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
