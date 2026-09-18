@extends('layouts.admin')

@section('title', 'Chỉnh sửa Sản phẩm: ' . $product->name . ' - Quản trị Mộc An')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <!-- Top breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('admin.products.index') }}" class="hover:text-primary transition">Sản phẩm</a>
                <span>/</span>
                <span class="text-heading font-semibold">{{ $product->name }}</span>
            </div>
            <h1 class="text-xl font-bold font-display text-heading">Chỉnh sửa Sản phẩm</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a
                href="{{ route('products.show', $product->slug) }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface border border-ui-border text-xs font-semibold text-heading hover:bg-surface-alt transition"
            >
                <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                <span>Xem trang ngoài</span>
            </a>
            <a
                href="{{ route('admin.products.index') }}"
                class="px-3.5 py-2 rounded-xl bg-surface-alt border border-ui-border text-xs font-semibold text-heading hover:bg-surface transition"
            >
                Quay lại
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 text-xs">
            <div class="font-bold mb-1">Có lỗi xảy ra, vui lòng kiểm tra lại:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Section 1: Product General Info -->
    <div class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs">
        <h2 class="text-sm font-bold text-heading mb-4">1. Thông tin cơ bản</h2>
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Tên sản phẩm <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name) }}"
                        required
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary font-medium"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Danh mục <span class="text-red-500">*</span></label>
                    <select
                        name="category_id"
                        required
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Giá niêm yết mặc định (VNĐ) <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        name="base_price"
                        value="{{ old('base_price', (int) $product->base_price) }}"
                        min="0"
                        step="1000"
                        required
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mã SKU chung</label>
                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku', $product->sku) }}"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading uppercase font-mono focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-heading mb-1.5">Đường dẫn tĩnh (Slug)</label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug', $product->slug) }}"
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mô tả ngắn</label>
                    <textarea
                        name="short_description"
                        rows="2"
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >{{ old('short_description', $product->short_description) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-heading mb-1.5">Mô tả chi tiết</label>
                    <textarea
                        name="description"
                        rows="5"
                        class="w-full px-3.5 py-2 text-xs rounded-xl bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $product->is_active))
                            class="size-4 rounded border-ui-border text-primary focus:ring-primary/20"
                        >
                        <span class="text-xs font-medium text-heading">Kích hoạt hiển thị sản phẩm trên cửa hàng</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shadow-xs"
                >
                    Lưu thông tin sản phẩm
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: Product Variants & Inventory -->
    <div class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-heading">2. Quản lý Biến thể & Tồn kho</h2>
                <p class="text-xs text-muted mt-0.5">Tồn kho của sản phẩm được tính toán hoàn toàn từ tổng số lượng tồn của các biến thể này.</p>
            </div>
        </div>

        <!-- Variants Table -->
        <div class="overflow-x-auto rounded-xl border border-ui-border">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-alt/60 text-muted font-semibold uppercase tracking-wider text-[11px] border-b border-ui-border">
                        <th class="py-2.5 px-3">Mã SKU</th>
                        <th class="py-2.5 px-3">Màu sắc</th>
                        <th class="py-2.5 px-3">Kích thước</th>
                        <th class="py-2.5 px-3">Chất liệu</th>
                        <th class="py-2.5 px-3">Đơn giá (đ)</th>
                        <th class="py-2.5 px-3">Tồn kho</th>
                        <th class="py-2.5 px-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border">
                    @forelse($product->variants as $variant)
                        <tr>
                            <form method="POST" action="{{ route('admin.products.variants.update', [$product, $variant]) }}">
                                @csrf
                                @method('PUT')
                                <td class="py-2.5 px-3 font-mono">
                                    <input
                                        type="text"
                                        name="sku"
                                        value="{{ $variant->sku }}"
                                        required
                                        class="w-32 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border text-heading font-mono focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="text"
                                        name="color"
                                        value="{{ $variant->color }}"
                                        placeholder="Màu sắc"
                                        class="w-28 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="text"
                                        name="size"
                                        value="{{ $variant->size }}"
                                        placeholder="Kích thước"
                                        class="w-28 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="text"
                                        name="material"
                                        value="{{ $variant->material }}"
                                        placeholder="Chất liệu"
                                        class="w-28 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        name="price"
                                        value="{{ (int) $variant->price }}"
                                        min="0"
                                        step="1000"
                                        required
                                        class="w-28 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3">
                                    <input
                                        type="number"
                                        name="stock"
                                        value="{{ $variant->stock }}"
                                        min="0"
                                        required
                                        class="w-20 px-2 py-1 text-xs rounded bg-surface-alt border border-ui-border font-bold text-heading focus:outline-hidden focus:border-primary"
                                    >
                                </td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="submit"
                                            class="px-2 py-1 rounded bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground font-semibold text-[11px] transition"
                                        >
                                            Cập nhật
                                        </button>
                            </form>
                                        <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa biến thể SKU {{ $variant->sku }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="px-2 py-1 rounded bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white font-semibold text-[11px] transition"
                                                title="Xóa biến thể"
                                            >
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-muted">Chưa có biến thể nào. Vui lòng thêm biến thể bên dưới.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Add New Variant Form -->
        <div class="p-4 rounded-xl bg-surface-alt/40 border border-ui-border space-y-3">
            <h3 class="text-xs font-bold text-heading">Thêm biến thể mới</h3>
            <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                @csrf
                <div>
                    <label class="block text-[11px] text-muted mb-1">Mã SKU *</label>
                    <input
                        type="text"
                        name="sku"
                        placeholder="VD: {{ $product->sku }}-V{{ $product->variants->count() + 1 }}"
                        required
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading font-mono focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">Màu sắc</label>
                    <input
                        type="text"
                        name="color"
                        placeholder="VD: Trắng"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">Kích thước</label>
                    <input
                        type="text"
                        name="size"
                        placeholder="VD: D160 x R80"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">Chất liệu</label>
                    <input
                        type="text"
                        name="material"
                        placeholder="VD: Gỗ Óc Chó"
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">Giá bán *</label>
                    <input
                        type="number"
                        name="price"
                        value="{{ (int) $product->base_price }}"
                        min="0"
                        step="1000"
                        required
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary"
                    >
                </div>
                <div>
                    <label class="block text-[11px] text-muted mb-1">Tồn kho ban đầu *</label>
                    <div class="flex gap-2">
                        <input
                            type="number"
                            name="stock"
                            value="10"
                            min="0"
                            required
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-surface border border-ui-border text-heading focus:outline-hidden focus:border-primary font-semibold"
                        >
                        <button
                            type="submit"
                            class="px-3 py-1.5 rounded-lg bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shrink-0"
                        >
                            Thêm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Section 3: Product Images Gallery & Upload -->
    <div class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs space-y-6">
        <div>
            <h2 class="text-sm font-bold text-heading">3. Thư viện Hình ảnh Sản phẩm</h2>
            <p class="text-xs text-muted mt-0.5">Tải lên hình ảnh sản phẩm thực tế và chọn hình đại diện chính.</p>
        </div>

        <!-- Images Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
            @forelse($product->images as $img)
                <div class="relative group rounded-xl bg-surface-alt border {{ $img->is_primary ? 'border-primary ring-2 ring-primary/20' : 'border-ui-border' }} overflow-hidden">
                    <div class="aspect-square bg-surface-alt overflow-hidden">
                        <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </div>

                    @if($img->is_primary)
                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-primary text-primary-foreground text-[10px] font-bold shadow-xs">
                            Ảnh chính
                        </div>
                    @endif

                    <!-- Action buttons overlay -->
                    <div class="p-2 bg-surface/90 border-t border-ui-border flex items-center justify-between gap-1 text-xs">
                        @if(! $img->is_primary)
                            <form method="POST" action="{{ route('admin.products.images.primary', [$product, $img]) }}">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="text-[11px] text-primary hover:underline font-medium"
                                >
                                    Đặt làm ảnh chính
                                </button>
                            </form>
                        @else
                            <span class="text-[11px] text-muted font-medium">Đại diện</span>
                        @endif

                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $img]) }}" onsubmit="return confirm('Bạn có chắc muốn xóa hình này?');">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="p-1 rounded text-red-500 hover:bg-red-500/10 transition"
                                title="Xóa hình ảnh"
                            >
                                <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-6 text-center text-muted text-xs border border-dashed border-ui-border rounded-xl">
                    Chưa có hình ảnh nào cho sản phẩm này. Hãy tải ảnh lên bên dưới.
                </div>
            @endforelse
        </div>

        <!-- Upload Image Form -->
        <div class="p-4 rounded-xl bg-surface-alt/40 border border-ui-border">
            <form method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                @csrf
                <div class="flex-1">
                    <input
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        required
                        class="text-xs text-muted file:mr-3 file:py-1.5 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-heading hover:file:bg-surface-alt file:border file:border-ui-border cursor-pointer"
                    >
                </div>
                <label class="flex items-center gap-2 cursor-pointer text-xs">
                    <input type="checkbox" name="is_primary" value="1" class="size-4 rounded border-ui-border text-primary">
                    <span>Đặt làm ảnh đại diện ngay</span>
                </label>
                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition shrink-0"
                >
                    Tải ảnh lên
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
