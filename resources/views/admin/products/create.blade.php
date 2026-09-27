@extends('layouts.admin')

@section('title', 'Thêm Sản Phẩm Nội Thất')
@section('page_title', 'Thêm Sản Phẩm Mới')

@section('content')
<div class="max-w-4xl bg-white rounded-xl border shadow-sm p-6">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tên sản phẩm *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Danh mục *</label>
                <select name="category_id" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                    <option value="">Chọn danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Giá bán gốc (VNĐ) *</label>
                <input type="number" name="price" value="{{ old('price') }}" required min="0" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Giá khuyến mãi (VNĐ)</label>
                <input type="number" name="sale_price" value="{{ old('sale_price') }}" min="0" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('sale_price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Số lượng kho *</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 10) }}" required min="0" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('stock_quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Chất liệu nội thất</label>
                <input type="text" name="material" value="{{ old('material') }}" placeholder="VD: Gỗ sồi Nga, Da bò Ý..." class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Kích thước (D x R x C)</label>
                <input type="text" name="dimensions" value="{{ old('dimensions') }}" placeholder="VD: 200cm x 180cm x 90cm" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Màu sắc</label>
                <input type="text" name="color" value="{{ old('color') }}" placeholder="VD: Nâu hạt dẻ, Trắng vân mây" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mô tả ngắn</label>
            <textarea name="short_description" rows="2" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('short_description') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mô tả chi tiết</label>
            <textarea name="description" rows="4" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Ảnh sản phẩm (Có thể chọn nhiều ảnh)</label>
            <input type="file" name="images[]" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
        </div>

        <div class="flex items-center space-x-6 pt-2">
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_featured" value="1" class="mr-2 text-amber-800">
                Sản phẩm nổi bật (Trang chủ)
            </label>
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="mr-2 text-amber-800">
                Kích hoạt bán ngay
            </label>
        </div>

        <div class="pt-4 flex items-center space-x-3 border-t">
            <button type="submit" class="bg-amber-800 hover:bg-amber-900 text-white font-bold text-xs px-6 py-2.5 rounded-lg transition">
                Lưu Sản Phẩm
            </button>
            <a href="{{ route('admin.products.index') }}" class="text-xs text-gray-600 hover:underline">Hủy bỏ</a>
        </div>
    </form>
</div>
@endsection
