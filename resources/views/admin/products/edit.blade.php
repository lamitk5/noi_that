@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Sản Phẩm Nội Thất')
@section('page_title', 'Chỉnh Sửa: ' . $product->name)

@section('content')
<div class="max-w-4xl bg-white rounded-xl border shadow-sm p-6">
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
            <p class="font-bold mb-1">Vui lòng kiểm tra lại dữ liệu:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('admin.products.update', $product) }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
        x-data="{ submitting: false }"
        @submit="submitting = true"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $product->id }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tên sản phẩm *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Danh mục *</label>
                <select name="category_id" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Giá bán gốc (VNĐ) *</label>
                <input type="number" name="price" value="{{ old('price', $product->base_price) }}" required min="0" step="1000" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Giá khuyến mãi (VNĐ)</label>
                <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" min="0" step="1000" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                <p class="mt-1 text-[10px] text-gray-400">Để trống nếu không giảm giá. Phải ≤ giá gốc.</p>
                @error('sale_price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Số lượng kho *</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required min="0" step="1" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('stock_quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Chất liệu nội thất</label>
                <input type="text" name="material" value="{{ old('material', $product->material) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kích thước (D x R x C)</label>
                <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Màu sắc</label>
                <input type="text" name="color" value="{{ old('color', $product->color) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mô tả ngắn</label>
            <textarea name="short_description" rows="2" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('short_description', $product->short_description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mô tả chi tiết</label>
            <textarea name="description" rows="4" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('description', $product->description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-2">Hình ảnh hiện tại</label>
            @if($product->images->isEmpty())
                <p class="text-xs text-muted">Sản phẩm chưa có ảnh. Ảnh đang dùng placeholder.</p>
            @else
                <div class="flex flex-wrap gap-4">
                    @foreach($product->images as $img)
                        <div class="relative w-28 h-28 border rounded-lg overflow-hidden group">
                            <img src="{{ $img->url }}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=300&q=70'">
                            @if($img->is_primary)
                                <span class="absolute top-1 left-1 bg-amber-600 text-white text-2xs font-bold px-1.5 py-0.5 rounded">Ảnh đại diện</span>
                            @else
                                <form action="{{ route('admin.products.images.primary', $img) }}" method="POST" class="absolute top-1 left-1 opacity-0 group-hover:opacity-100 transition">
                                    @csrf
                                    <button type="submit" class="bg-gray-900/80 text-white text-2xs px-1.5 py-0.5 rounded hover:bg-amber-700">Chọn đại diện</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.products.images.destroy', $img) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition" onsubmit="return confirm('Xóa ảnh này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-rose-600 text-white text-2xs p-1 rounded-full hover:bg-rose-700">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tải thêm ảnh mới (không bắt buộc)</label>
            <p class="text-[10px] text-gray-400 mb-1.5">Nếu không chọn file, ảnh hiện tại sẽ được giữ nguyên.</p>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
        </div>

        <div class="flex items-center space-x-6 pt-2">
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="mr-2 text-amber-800">
                Sản phẩm nổi bật (Trang chủ)
            </label>
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="mr-2 text-amber-800">
                Đang mở bán
            </label>
        </div>

        <div class="pt-4 flex items-center space-x-3 border-t">
            <button
                type="submit"
                :disabled="submitting"
                class="bg-amber-800 hover:bg-amber-900 text-white font-bold text-xs px-6 py-2.5 rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span x-show="!submitting">Cập Nhật Sản Phẩm</span>
                <span x-show="submitting" x-cloak>Đang lưu...</span>
            </button>
            <a href="{{ route('admin.products.index') }}" class="text-xs text-gray-600 hover:underline">Hủy bỏ</a>
        </div>
    </form>
</div>
@endsection
