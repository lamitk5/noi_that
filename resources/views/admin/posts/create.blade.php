@extends('layouts.admin')

@section('title', 'Tạo bài viết mới | Admin Mộc An')
@section('header', 'Viết bài mới')

@section('content')
<div class="max-w-4xl mx-auto rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
    <div class="border-b border-ui-border pb-4 mb-6">
        <h1 class="text-lg font-bold font-display text-heading">Soạn thảo bài viết mới</h1>
        <p class="text-xs text-muted mt-0.5">Chia sẻ kiến thức, mẹo trang trí và phong cách nội thất.</p>
    </div>

    <form method="POST" action="{{ route('admin.posts.store') }}" class="space-y-5">
        @csrf

        <div class="grid sm:grid-cols-3 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Tiêu đề bài viết <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title') }}"
                    required
                    placeholder="Ví dụ: 5 Cách bố trí sofa phòng khách nhỏ gọn ấm cúng"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none font-medium"
                >
                @error('title')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Chuyên mục
                </label>
                <select name="post_category_id" class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none">
                    <option value="">Chung</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('post_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Đường dẫn tùy chỉnh (Slug)
                </label>
                <input
                    type="text"
                    name="slug"
                    value="{{ old('slug') }}"
                    placeholder="Để trống sẽ tự tạo từ tiêu đề"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading font-mono focus:border-primary focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    URL Ảnh đại diện (Featured Image)
                </label>
                <input
                    type="url"
                    name="featured_image"
                    value="{{ old('featured_image') }}"
                    placeholder="https://images.unsplash.com/..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                Mô tả ngắn (Excerpt)
            </label>
            <textarea
                name="excerpt"
                rows="2"
                placeholder="Tóm tắt nội dung bài viết hiển thị ở danh sách bài..."
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
            >{{ old('excerpt') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                Nội dung chi tiết <span class="text-rose-500">*</span>
            </label>
            <textarea
                name="content"
                rows="12"
                required
                placeholder="Nội dung bài viết đầy đủ..."
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
            >{{ old('content') }}</textarea>
            @error('content')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-2">
            <input
                type="checkbox"
                id="is_published"
                name="is_published"
                value="1"
                class="rounded border-ui-border text-primary focus:ring-primary"
                {{ old('is_published', true) ? 'checked' : '' }}
            >
            <label for="is_published" class="text-xs font-medium text-heading">Xuất bản bài viết ngay</label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-ui-border">
            <a href="{{ route('admin.posts.index') }}" class="text-xs font-semibold text-muted hover:text-heading">
                ← Hủy bỏ
            </a>
            <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
            >
                Lưu và Đăng bài
            </button>
        </div>
    </form>
</div>
@endsection
