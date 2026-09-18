@extends('layouts.admin')

@section('title', 'Thêm Danh Mục Nội Thất')
@section('page_title', 'Thêm Danh Mục Mới')

@section('content')
<div class="max-w-2xl bg-white rounded-xl border shadow-sm p-6">
    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tên danh mục *</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mô tả danh mục</label>
            <textarea name="description" rows="3" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Ảnh danh mục</label>
            <input type="file" name="image" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
        </div>

        <div class="flex items-center space-x-6 pt-2">
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="mr-2 text-amber-800">
                Kích hoạt danh mục
            </label>
        </div>

        <div class="pt-4 flex items-center space-x-3">
            <button type="submit" class="bg-amber-800 hover:bg-amber-900 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition">
                Lưu Danh Mục
            </button>
            <a href="{{ route('admin.categories.index') }}" class="text-xs text-gray-600 hover:underline">Hủy bỏ</a>
        </div>
    </form>
</div>
@endsection
