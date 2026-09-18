@extends('layouts.admin')

@section('title', 'Thêm Sản phẩm Mới - Quản trị Mộc An')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Thêm Sản phẩm Mới</h1>
            <p class="text-xs text-muted mt-1">Khởi tạo thông tin sản phẩm và biến thể mặc định đầu tiên.</p>
        </div>
        <a
            href="{{ route('admin.products.index') }}"
            class="px-3.5 py-2 rounded-xl bg-surface border border-ui-border text-xs font-semibold text-heading hover:bg-surface-alt transition"
        >
            Quay lại danh sách
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs">
            <div class="font-bold mb-1">Vui lòng kiểm tra lại dữ liệu nhập:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
        @csrf

        <!-- Product Basic Information -->
        <div class="p-6 rounded-2xl bg-surface border border-ui-border space-y-4">
            <h2 class="text-sm font-bold text-heading">1. Thông tin chung sản phẩm</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Tên sản phẩm <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Ví dụ: Bàn Trà Gỗ Sồi Tự Nhiên Scandinavia"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Danh mục <span class="text-red-500">*</span></label>
                    <select
                        name="category_id"
                        required
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Giá niêm yết (VNĐ) <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="base_price"
                        value="{{ old('base_price') }}"
                        min="0"
                        step="1000"
                        required
                        placeholder="Ví dụ: 3500000"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mã SKU chung (Tùy chọn)</label>
                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku') }}"
                        placeholder="Để trống hệ thống sẽ tự sinh (VD: MA-89DF2A)"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading uppercase font-mono focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Đường dẫn URL Slug (Tùy chọn)</label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug') }}"
                        placeholder="Để trống hệ thống tự sinh từ tên sản phẩm"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mô tả ngắn</label>
                    <textarea
                        name="short_description"
                        rows="2"
                        placeholder="Tóm tắt ngắn gọn chất liệu và điểm nhấn nổi bật..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >{{ old('short_description') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mô tả chi tiết</label>
                    <textarea
                        name="description"
                        rows="5"
                        placeholder="Chi tiết thiết kế, xuất xứ, bảo hành, công năng sử dụng..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >{{ old('description') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', 1))
                            class="size-4 rounded border-ui-border text-primary focus:ring-primary/20"
                        >
                        <span class="text-xs font-medium text-heading">Kích hoạt hiển thị sản phẩm ngay sau khi tạo</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Initial Variant Info -->
        <div class="p-6 rounded-2xl bg-surface border border-ui-border space-y-4">
            <div>
                <h2 class="text-sm font-bold text-heading">2. Biến thể & Tồn kho ban đầu</h2>
                <p class="text-xs text-muted mt-0.5">Tạo ngay phiên bản đầu tiên của sản phẩm để có thể giao dịch và kiểm kho.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Màu sắc</label>
                    <input
                        type="text"
                        name="variant_color"
                        value="{{ old('variant_color', 'Màu Gỗ Tự Nhiên') }}"
                        placeholder="VD: Nâu Óc Chó, Sồi Vàng..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Kích thước</label>
                    <input
                        type="text"
                        name="variant_size"
                        value="{{ old('variant_size', 'Tiêu chuẩn') }}"
                        placeholder="VD: D120 x R60 x C75 cm"
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Chất liệu</label>
                    <input
                        type="text"
                        name="variant_material"
                        value="{{ old('variant_material', 'Gỗ Tự Nhiên') }}"
                        placeholder="VD: Gỗ Sồi Nga, Đệm Da..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Số lượng tồn kho ban đầu <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="variant_stock"
                        value="{{ old('variant_stock', 10) }}"
                        min="0"
                        required
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary font-semibold"
                    >
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a
                href="{{ route('admin.products.index') }}"
                class="px-4 py-2.5 rounded-xl bg-surface-alt border border-ui-border text-xs font-semibold text-heading hover:bg-surface transition"
            >
                Hủy bỏ
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
            >
                Lưu và Chuyển đến Quản lý Biến thể & Hình ảnh
            </button>
        </div>
    </form>
</div>
@endsection
