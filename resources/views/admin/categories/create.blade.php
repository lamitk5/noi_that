@extends('layouts.admin')

@section('title', 'Thêm Danh mục | Admin Mộc An')
@section('header-title', 'Thêm danh mục mới')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold font-display text-heading">Thêm danh mục mới</h1>
            <p class="text-xs text-muted mt-1">Tạo nhóm sản phẩm cho các không gian nội thất.</p>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="text-xs font-semibold text-muted hover:text-heading transition">
            ← Quay lại
        </a>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-heading mb-1.5">Tên danh mục <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Ví dụ: Phòng Khách, Bàn Ghế Ăn..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    required
                >
                @error('name')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="block text-xs font-semibold text-heading mb-1.5">Đường dẫn (Slug - để trống sẽ tự sinh)</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug') }}"
                    placeholder="phong-khach"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs font-mono text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                @error('slug')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-heading mb-1.5">Mô tả ngắn</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    placeholder="Mô tả danh mục hiển thị trên website..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                        class="rounded border-ui-border text-primary focus:ring-primary size-4"
                    >
                    <span class="text-xs font-semibold text-heading">Kích hoạt hiển thị danh mục ngay</span>
                </label>
            </div>

            <div class="pt-4 border-t border-ui-border flex items-center justify-end gap-3">
                <a
                    href="{{ route('admin.categories.index') }}"
                    class="rounded-xl border border-ui-border px-4 py-2 text-xs font-semibold text-muted hover:text-heading hover:bg-surface-alt transition"
                >
                    Hủy
                </a>
                <button
                    type="submit"
                    class="rounded-xl bg-primary px-5 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-sm transition hover:opacity-95 cursor-pointer"
                >
                    Lưu danh mục
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
