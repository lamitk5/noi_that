@extends('layouts.admin')

@section('title', 'Tạo Trang Tĩnh Mới | Admin Mộc An')
@section('header', 'Tạo trang tĩnh')

@section('content')
<div class="max-w-3xl mx-auto rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
    <div class="border-b border-ui-border pb-4 mb-6">
        <h1 class="text-lg font-bold font-display text-heading">Tạo trang nội dung mới</h1>
        <p class="text-xs text-muted mt-0.5">Xây dựng trang tĩnh cho các chính sách, điều khoản hoặc thông tin doanh nghiệp.</p>
    </div>

    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-4">
        @csrf

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Tiêu đề trang <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title') }}"
                    required
                    placeholder="Ví dụ: Chính sách thanh toán"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
                @error('title')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Đường dẫn (Slug) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    name="slug"
                    value="{{ old('slug') }}"
                    required
                    placeholder="chinh-sach-thanh-toan"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading font-mono focus:border-primary focus:outline-none"
                >
                @error('slug')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                Nội dung trang <span class="text-rose-500">*</span>
            </label>
            <textarea
                name="content"
                rows="10"
                required
                placeholder="Nhập toàn bộ nội dung chi tiết của trang..."
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
            >{{ old('content') }}</textarea>
            @error('content')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Meta Title (SEO)
                </label>
                <input
                    type="text"
                    name="meta_title"
                    value="{{ old('meta_title') }}"
                    placeholder="Tiêu đề hiển thị trên Google..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Meta Description (SEO)
                </label>
                <input
                    type="text"
                    name="meta_description"
                    value="{{ old('meta_description') }}"
                    placeholder="Mô tả tóm tắt ngắn..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
            </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
            <input
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                class="rounded border-ui-border text-primary focus:ring-primary"
                {{ old('is_active', true) ? 'checked' : '' }}
            >
            <label for="is_active" class="text-xs font-medium text-heading">Kích hoạt hiển thị công khai</label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-ui-border">
            <a href="{{ route('admin.pages.index') }}" class="text-xs font-semibold text-muted hover:text-heading">
                ← Hủy bỏ
            </a>
            <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
            >
                Lưu trang tĩnh
            </button>
        </div>
    </form>
</div>
@endsection
