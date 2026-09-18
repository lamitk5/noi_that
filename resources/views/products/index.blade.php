@extends('layouts.app')

@section('title', 'Bộ Sưu Tập Sản Phẩm Nội Thất')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Filter -->
        <aside class="w-full md:w-64 flex-shrink-0">
            <form action="{{ route('products.index') }}" method="GET" class="bg-white p-5 rounded-xl border shadow-sm space-y-6">
                <!-- Search -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Tên, chất liệu..." class="w-full text-sm border rounded-lg p-2 focus:ring-1 focus:ring-amber-700 outline-none">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Danh mục</label>
                    <div class="space-y-1.5 text-sm">
                        <label class="flex items-center text-gray-700">
                            <input type="radio" name="category_id" value="" {{ empty($filters['category_id']) ? 'checked' : '' }} class="mr-2 text-amber-800">
                            Tất cả danh mục
                        </label>
                        @foreach($categories as $cat)
                            <label class="flex items-center text-gray-700 justify-between">
                                <span class="flex items-center">
                                    <input type="radio" name="category_id" value="{{ $cat->id }}" {{ ($filters['category_id'] ?? null) == $cat->id ? 'checked' : '' }} class="mr-2 text-amber-800">
                                    {{ $cat->name }}
                                </span>
                                <span class="text-xs text-gray-400">({{ $cat->products_count }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Price Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Khoảng giá (VNĐ)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Từ" class="w-full text-xs border rounded p-1.5 outline-none">
                        <input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Đến" class="w-full text-xs border rounded p-1.5 outline-none">
                    </div>
                </div>

                <!-- Material Filter -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Chất liệu</label>
                    <select name="material" class="w-full text-xs border rounded p-2 outline-none">
                        <option value="">Tất cả chất liệu</option>
                        @foreach($materials as $mat)
                            <option value="{{ $mat }}" {{ ($filters['material'] ?? '') == $mat ? 'selected' : '' }}>{{ $mat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Sắp xếp</label>
                    <select name="sort" class="w-full text-xs border rounded p-2 outline-none">
                        <option value="latest" {{ ($filters['sort'] ?? '') == 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="price_asc" {{ ($filters['sort'] ?? '') == 'price_asc' ? 'selected' : '' }}>Giá tăng dần</option>
                        <option value="price_desc" {{ ($filters['sort'] ?? '') == 'price_desc' ? 'selected' : '' }}>Giá giảm dần</option>
                        <option value="popular" {{ ($filters['sort'] ?? '') == 'popular' ? 'selected' : '' }}>Xem nhiều nhất</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-medium py-2 rounded-lg text-sm transition">
                        Áp Dụng Bộ Lọc
                    </button>
                    <a href="{{ route('products.index') }}" class="block text-center text-xs text-gray-500 hover:underline mt-2">Đặt lại</a>
                </div>
            </form>
        </aside>

        <!-- Product Grid -->
        <div class="flex-grow">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-bold text-gray-900">Danh Sách Sản Phẩm ({{ $products->total() }})</h1>
            </div>

            @if($products->isEmpty())
                <div class="bg-white rounded-xl p-12 text-center border">
                    <i class="fa-solid fa-box-open text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-base">Không tìm thấy sản phẩm nội thất nào phù hợp với bộ lọc.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        <div class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-lg transition flex flex-col">
                            <a href="{{ route('products.show', $product->slug) }}" class="relative block h-56 bg-gray-100 overflow-hidden">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                                @if($product->is_on_sale)
                                    <span class="absolute top-2 left-2 bg-rose-600 text-white text-xs font-bold px-2 py-1 rounded">
                                        Giảm giá
                                    </span>
                                @endif
                            </a>
                            <div class="p-4 flex-grow flex flex-col justify-between">
                                <div>
                                    <span class="text-xs text-amber-700 font-medium">{{ $product->category->name ?? 'Nội thất' }}</span>
                                    <h3 class="font-bold text-gray-800 text-base mt-1 line-clamp-2 hover:text-amber-800">
                                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                    </h3>
                                    @if($product->material)
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-1"><i class="fa-solid fa-gem mr-1"></i>{{ $product->material }}</p>
                                    @endif
                                </div>
                                <div class="mt-4 pt-3 border-t flex justify-between items-center">
                                    <div>
                                        <span class="text-lg font-extrabold text-amber-900">{{ number_format($product->final_price, 0, ',', '.') }} đ</span>
                                        @if($product->is_on_sale)
                                            <span class="block text-xs text-gray-400 line-through">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                                        @endif
                                    </div>
                                    <form action="{{ route('cart.add', $product) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 bg-amber-800 hover:bg-amber-900 text-white rounded-lg transition" title="Thêm vào giỏ">
                                            <i class="fa-solid fa-cart-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
