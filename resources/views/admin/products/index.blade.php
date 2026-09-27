@extends('layouts.admin')

@section('title', 'Quản Lý Sản Phẩm Nội Thất')
@section('page_title', 'Danh Sách Sản Phẩm Nội Thất')

@section('content')
<div class="bg-white rounded-xl border shadow-sm p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên, SKU, chất liệu..." class="text-xs border rounded-lg p-2 outline-none w-56">
            <select name="category_id" class="text-xs border rounded-lg p-2 outline-none">
                <option value="">Tất cả danh mục</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-800 text-white text-xs px-3 py-2 rounded-lg hover:bg-gray-900">
                <i class="fa-solid fa-magnifying-glass"></i> Lọc
            </button>
        </form>

        <a href="{{ route('admin.products.create') }}" class="bg-amber-800 hover:bg-amber-900 text-white font-semibold text-xs px-4 py-2 rounded-lg transition whitespace-nowrap">
            <i class="fa-solid fa-plus mr-1"></i> Thêm Sản Phẩm Mới
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 uppercase text-gray-500 font-semibold border-b">
                <tr>
                    <th class="py-3 px-3">Ảnh</th>
                    <th class="py-3 px-3">Tên sản phẩm / SKU</th>
                    <th class="py-3 px-3">Danh mục</th>
                    <th class="py-3 px-3">Giá bán</th>
                    <th class="py-3 px-3">Tồn kho</th>
                    <th class="py-3 px-3">Trạng thái</th>
                    <th class="py-3 px-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y text-gray-700">
                @foreach($products as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-3">
                            <img src="{{ $p->primary_image_url }}" class="w-12 h-12 object-cover rounded-lg border bg-gray-50">
                        </td>
                        <td class="py-3 px-3">
                            <h4 class="font-bold text-gray-900 line-clamp-1 max-w-xs">{{ $p->name }}</h4>
                            <span class="text-gray-400 font-mono">{{ $p->sku }}</span>
                        </td>
                        <td class="py-3 px-3 font-medium">{{ $p->category->name ?? '---' }}</td>
                        <td class="py-3 px-3">
                            <span class="font-extrabold text-amber-900">{{ number_format($p->final_price, 0, ',', '.') }} đ</span>
                            @if($p->is_on_sale)
                                <span class="block text-gray-400 line-through text-2xs">{{ number_format($p->price, 0, ',', '.') }} đ</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            @if($p->stock_quantity <= 5)
                                <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded font-bold">{{ $p->stock_quantity }} (Sắp hết)</span>
                            @else
                                <span class="font-semibold text-gray-800">{{ $p->stock_quantity }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            @if($p->is_active)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-semibold">Đang bán</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-semibold">Tạm dừng</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-right space-x-2">
                            <a href="{{ route('admin.products.edit', $p) }}" class="text-amber-800 hover:underline font-semibold">Sửa</a>
                            <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
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
        {{ $products->links() }}
    </div>
</div>
@endsection
