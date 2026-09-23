@extends('layouts.admin')

@section('title', 'Chá»‰nh Sá»­a Sáº£n Pháº©m Ná»™i Tháº¥t')
@section('page_title', 'Chá»‰nh Sá»­a: ' . $product->name)

@section('content')
<div class="max-w-4xl bg-white rounded-xl border shadow-sm p-6">
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">TÃªn sáº£n pháº©m *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Danh má»¥c *</label>
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
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">GiÃ¡ bÃ¡n gá»‘c (VNÄ) *</label>
                <input type="number" name="base_price" value="{{ old('base_price', old('price', $product->base_price)) }}" required min="0" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('base_price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">GiÃ¡ khuyáº¿n mÃ£i (VNÄ)</label>
                <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" min="0" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('sale_price') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Tá»“n kho (theo biáº¿n thá»ƒ)</label>
                <input type="text" value="{{ $product->total_stock }} sáº£n pháº©m" disabled class="w-full text-sm border rounded-lg p-2.5 bg-gray-50 text-gray-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Cháº¥t liá»‡u ná»™i tháº¥t</label>
                <input type="text" name="material" value="{{ old('material', $product->material) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">KÃ­ch thÆ°á»›c máº·c Ä‘á»‹nh (D x R x C)</label>
                <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">MÃ u sáº¯c máº·c Ä‘á»‹nh</label>
                <input type="text" name="color" value="{{ old('color', $product->color) }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
        </div>

        <!-- Size / Wood color variants -->
        @php
            $variantRows = old('variants', $product->variants->map(fn ($v) => [
                'size' => $v->size,
                'color' => $v->color,
                'material' => $v->material,
                'sku' => $v->sku,
                'price' => (float) $v->price,
                'stock' => $v->stock,
            ])->values()->all());
            $variantRowsJson = json_encode($variantRows);
        @endphp
        <div class="border rounded-xl p-4 bg-amber-50/40 border-amber-100" data-variant-section>
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">KÃ­ch thÆ°á»›c & MÃ u gá»— (biáº¿n thá»ƒ)</h3>
                    <p class="text-xs text-gray-500">Má»—i dÃ²ng = 1 tá»• há»£p size + mÃ u gá»—, cÃ³ giÃ¡ vÃ  tá»“n kho riÃªng. XÃ³a tráº¯ng dÃ²ng Ä‘á»ƒ gá»¡ biáº¿n thá»ƒ.</p>
                </div>
                <button type="button" class="text-xs bg-amber-800 hover:bg-amber-900 text-white font-bold px-3 py-2 rounded-lg" data-add-variant>
                    + ThÃªm biáº¿n thá»ƒ
                </button>
            </div>
            <div class="space-y-3" data-variant-list data-existing="{{ $variantRowsJson }}">
                {{-- rows injected / restored by JS --}}
            </div>
            <p class="text-xs text-gray-400 mt-3">MÃ u gá»— gá»£i Ã½: Gá»— sá»“i tá»± nhiÃªn, Gá»— sá»“i tráº¯ng, Gá»— Ã³c chÃ³, Gá»— táº§n bÃ¬, Gá»— cÄƒm xe, Gá»— xoan Ä‘Ã o, Walnut Ä‘áº­m</p>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">MÃ´ táº£ ngáº¯n</label>
            <textarea name="short_description" rows="2" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('short_description', $product->short_description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">MÃ´ táº£ chi tiáº¿t</label>
            <textarea name="description" rows="4" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">{{ old('description', $product->description) }}</textarea>
        </div>

        <!-- Current Images Gallery with Delete & Set Primary -->
        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-2">HÃ¬nh áº£nh hiá»‡n táº¡i</label>
            <div class="flex flex-wrap gap-4">
                @foreach($product->images as $img)
                    <div class="relative w-28 h-28 border rounded-lg overflow-hidden group">
                        <img src="{{ Str::startsWith($img->image_path, 'http') ? $img->image_path : asset('storage/' . $img->image_path) }}" class="w-full h-full object-cover">
                        @if($img->is_primary)
                            <span class="absolute top-1 left-1 bg-amber-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">áº¢nh Ä‘áº¡i diá»‡n</span>
                        @else
                            <form action="{{ route('admin.products.images.primary', $img) }}" method="POST" class="absolute top-1 left-1 opacity-0 group-hover:opacity-100 transition">
                                @csrf
                                <button type="submit" class="bg-gray-900/80 text-white text-[10px] px-1.5 py-0.5 rounded hover:bg-amber-700">Chá»n Ä‘áº¡i diá»‡n</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.products.images.destroy', $img) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition" onsubmit="return confirm('XÃ³a áº£nh nÃ y?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-rose-600 text-white text-[10px] p-1 rounded-full hover:bg-rose-700">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Táº£i thÃªm áº£nh má»›i</label>
            <input type="file" name="images[]" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
        </div>

        <div class="flex items-center space-x-6 pt-2">
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="mr-2 text-amber-800">
                Sáº£n pháº©m ná»•i báº­t (Trang chá»§)
            </label>
            <label class="flex items-center text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="mr-2 text-amber-800">
                Äang má»Ÿ bÃ¡n
            </label>
        </div>

        <div class="pt-4 flex items-center space-x-3 border-t">
            <button type="submit" class="bg-amber-800 hover:bg-amber-900 text-white font-bold text-xs px-6 py-2.5 rounded-lg transition">
                Cáº­p Nháº­t Sáº£n Pháº©m
            </button>
            <a href="{{ route('admin.products.index') }}" class="text-xs text-gray-600 hover:underline">Há»§y bá»</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const list = document.querySelector('[data-variant-list]');
    const addBtn = document.querySelector('[data-add-variant]');
    if (!list || !addBtn) return;

    const WOOD_PRESETS = [
        { name: 'Gá»— sÃ¡ng', hex: '#E8D5B7' },
        { name: 'Gá»— sá»“i sÃ¡ng', hex: '#E8D5B7' },
        { name: 'Gá»— sá»“i tráº¯ng', hex: '#E8D5B7' },
        { name: 'Gá»— sá»“i tá»± nhiÃªn', hex: '#D2B48C' },
        { name: 'Gá»— sá»“i', hex: '#D2B48C' },
        { name: 'NÃ¢u Ã³c chÃ³', hex: '#5C4033' },
        { name: 'Gá»— Ã³c chÃ³', hex: '#5C4033' },
        { name: 'Walnut Ä‘áº­m', hex: '#3E2723' },
        { name: 'NÃ¢u háº¡t dáº»', hex: '#8B4513' },
        { name: 'Gá»— cÄƒm xe', hex: '#8B4513' },
        { name: 'Gá»— xoan Ä‘Ã o', hex: '#A0522D' },
        { name: 'Gá»— táº§n bÃ¬', hex: '#C4A35A' },
        { name: 'Gá»— maple', hex: '#F5DEB3' },
        { name: 'Tráº¯ng', hex: '#F5F5F5' },
        { name: 'Äen', hex: '#2C2C2C' },
    ];

    let seed = 0;

    function createRow(data = {}) {
        const index = seed++;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-2 md:grid-cols-12 gap-2 items-end bg-white border rounded-lg p-3';
        row.setAttribute('data-variant-row', '');
        row.innerHTML = `
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">KÃ­ch thÆ°á»›c</label>
                <input type="text" name="variants[${index}][size]" value="${data.size || ''}" placeholder="VD: 160 x 80 x 75 cm" class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">TÃªn mÃ u gá»— *</label>
                <input type="text" name="variants[${index}][color]" value="${data.color || ''}" list="wood-color-presets" placeholder="VD: Gá»— sá»“i sÃ¡ng" required class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">MÃ u</label>
                <input type="color" name="variants[${index}][color_hex]" value="${data.color_hex || '#C4A574'}" class="w-full h-9 border rounded-md cursor-pointer" data-color-preview>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">GiÃ¡ *</label>
                <input type="number" name="variants[${index}][price]" value="${data.price ?? ''}" min="0" required class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Cháº¥t liá»‡u</label>
                <input type="text" name="variants[${index}][material]" value="${data.material || ''}" placeholder="VD: Gá»— sá»“i" class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Tá»“n kho *</label>
                <input type="number" name="variants[${index}][stock]" value="${data.stock ?? 5}" min="0" required class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
            </div>
            <div class="md:col-span-2 flex items-center gap-2 pb-1">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">SKU</label>
                    <input type="text" name="variants[${index}][sku]" value="${data.sku || ''}" class="w-full text-xs border rounded-md p-2 outline-none focus:ring-1 focus:ring-amber-700">
                </div>
                <button type="button" class="text-rose-500 hover:text-rose-700 text-xs pb-1" data-remove-variant title="XÃ³a dÃ²ng">âœ•</button>
            </div>
        `;
        return row;
    }

    function ensurePresetList() {
        if (document.getElementById('wood-color-presets')) return;
        const dl = document.createElement('datalist');
        dl.id = 'wood-color-presets';
        WOOD_PRESETS.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.name;
            dl.appendChild(opt);
        });
        document.body.appendChild(dl);
    }

    function bindColorHelper(row) {
        const nameInput = row.querySelector('input[name$="[color]"]');
        const hexInput = row.querySelector('input[name$="[color_hex]"]');
        nameInput?.addEventListener('change', () => {
            const match = WOOD_PRESETS.find((p) => p.name === nameInput.value.trim());
            if (match && hexInput) hexInput.value = match.hex;
        });
    }

    function hydrate() {
        let existing = [];
        try {
            existing = JSON.parse(list.getAttribute('data-existing') || '[]');
        } catch (e) {
            existing = [];
        }
        if (!Array.isArray(existing) || existing.length === 0) return;
        existing.forEach((item) => {
            const row = createRow(item);
            bindColorHelper(row);
            list.appendChild(row);
        });
    }

    addBtn.addEventListener('click', () => {
        const row = createRow();
        bindColorHelper(row);
        list.appendChild(row);
    });

    list.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-remove-variant]');
        if (!btn) return;
        btn.closest('[data-variant-row]')?.remove();
    });

    ensurePresetList();
    hydrate();
})();
</script>
@endpush
@endsection

