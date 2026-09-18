@extends('layouts.admin')

@section('title', 'Quản Lý Danh Mục Nội Thất')
@section('page_title', 'Danh Mục Sản Phẩm Nội Thất')

@section('content')
<div class="bg-white rounded-xl border shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-gray-900 text-base">Danh Sách Danh Mục</h3>
        <a href="{{ route('admin.categories.create') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold text-xs px-4 py-2 rounded-lg transition">
            <i class="fa-solid fa-plus mr-1"></i> Thêm Danh Mục Mới
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 uppercase text-gray-500 font-semibold border-b">
                <tr>
                    <th class="py-3 px-4">Ảnh</th>
                    <th class="py-3 px-4">Tên danh mục</th>
                    <th class="py-3 px-4">Slug</th>
                    <th class="py-3 px-4">Số sản phẩm</th>
                    <th class="py-3 px-4">Trạng thái</th>
                    <th class="py-3 px-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y text-gray-700">
                @foreach($categories as $cat)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <img src="{{ $cat->image ?? 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=100&q=80' }}" class="w-10 h-10 object-cover rounded-md border">
                        </td>
                        <td class="py-3 px-4 font-bold text-gray-900">{{ $cat->name }}</td>
                        <td class="py-3 px-4 font-mono text-gray-500">{{ $cat->slug }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $cat->products_count }}</td>
                        <td class="py-3 px-4">
                            @if($cat->is_active)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold text-xs">Đang hoạt động</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-semibold text-xs">Tạm ẩn</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right space-x-2">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="text-amber-800 hover:underline font-semibold">Sửa</a>
                            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:underline font-semibold">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
