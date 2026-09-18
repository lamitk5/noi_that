@extends('layouts.admin')

@section('title', 'Quản lý Danh mục | Admin Mộc An')
@section('header-title', 'Danh sách danh mục')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold font-display text-heading">Quản lý Danh mục</h1>
            <p class="text-xs text-muted mt-1">Phân loại không gian phòng khách, phòng ngủ, phòng ăn và nội thất.</p>
        </div>
        <a
            href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-sm transition hover:opacity-95 cursor-pointer"
        >
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            <span>Thêm danh mục</span>
        </a>
    </div>

    <!-- Filters & Search -->
    <div class="rounded-2xl border border-ui-border bg-surface p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 items-end">
            <div class="lg:col-span-6">
                <label for="q" class="block text-xs font-semibold text-heading mb-1">Tìm kiếm</label>
                <input
                    type="text"
                    name="q"
                    id="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Tên danh mục, slug..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
            </div>
            <div class="lg:col-span-4">
                <label for="status" class="block text-xs font-semibold text-heading mb-1">Trạng thái</label>
                <select
                    name="status"
                    id="status"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Đang kích hoạt</option>
                    <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Đang tạm ẩn</option>
                </select>
            </div>
            <div class="lg:col-span-2 flex items-center gap-2">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition hover:opacity-95 shadow-xs cursor-pointer"
                >
                    Lọc
                </button>
                @if ($filters['q'] || $filters['status'])
                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="p-2 rounded-xl border border-ui-border text-muted hover:text-heading hover:bg-surface-alt transition shrink-0"
                        title="Xóa bộ lọc"
                    >
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt/70 text-muted font-bold uppercase tracking-wider text-[10px] border-b border-ui-border">
                    <tr>
                        <th class="px-4 py-3.5">ID</th>
                        <th class="px-4 py-3.5">Tên danh mục</th>
                        <th class="px-4 py-3.5">Slug</th>
                        <th class="px-4 py-3.5 text-center">Số sản phẩm</th>
                        <th class="px-4 py-3.5 text-center">Trạng thái</th>
                        <th class="px-4 py-3.5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-surface-alt/50 transition">
                            <td class="px-4 py-3.5 font-mono text-muted">#{{ $category->id }}</td>
                            <td class="px-4 py-3.5 font-semibold text-heading">{{ $category->name }}</td>
                            <td class="px-4 py-3.5 font-mono text-xs text-muted">{{ $category->slug }}</td>
                            <td class="px-4 py-3.5 text-center font-bold text-heading">{{ $category->products_count }}</td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($category->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40">
                                        Hiển thị
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-600 border border-stone-200 dark:bg-stone-900/50 dark:text-stone-400 dark:border-stone-800">
                                        Đang ẩn
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <a
                                    href="{{ route('admin.categories.edit', $category->id) }}"
                                    class="font-semibold text-primary hover:underline"
                                >
                                    Sửa
                                </a>
                                @if ($category->products_count === 0)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.categories.destroy', $category->id) }}"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');"
                                        class="inline"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-rose-600 hover:underline cursor-pointer">
                                            Xóa
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-muted">
                                Không tìm thấy danh mục nào phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
