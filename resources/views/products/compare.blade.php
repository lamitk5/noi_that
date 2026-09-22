@extends('layouts.app')

@section('title', 'So sánh sản phẩm | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page" x-data="productCompare">
    <div class="max-w-7xl mx-auto">
        <!-- Breadcrumb & Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="mb-2 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
                    <span>/</span>
                    <a href="{{ route('products.index') }}" class="hover:text-heading transition">Sản phẩm</a>
                    <span>/</span>
                    <span class="text-heading font-medium" aria-current="page">So sánh sản phẩm</span>
                </nav>
                <h1 class="font-display text-2xl sm:text-3xl font-semibold text-heading">So sánh sản phẩm</h1>
                <p class="mt-1 text-sm text-muted">So sánh thông số, giá cả, chất liệu và kích thước trực quan tối đa 4 sản phẩm.</p>
            </div>
            <template x-if="items.length > 0">
                <button @click="clearAll()" class="text-xs font-bold text-rose-500 hover:underline">
                    Xóa tất cả so sánh
                </button>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="items.length === 0" x-cloak class="rounded-3xl border border-ui-border bg-surface p-12 text-center shadow-sm">
            <div class="size-16 rounded-full bg-primary/10 text-primary mx-auto grid place-items-center mb-4">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <h3 class="font-display text-lg font-bold text-heading">Chưa có sản phẩm nào để so sánh</h3>
            <p class="text-sm text-muted mt-1 max-w-md mx-auto">Vui lòng chọn nút "So sánh" trên các sản phẩm trong cửa hàng để xem bảng đối chiếu chi tiết.</p>
            <a href="{{ route('products.index') }}" class="mt-6 btn-primary inline-flex items-center gap-2">
                <span>Khám phá sản phẩm</span>
            </a>
        </div>

        <!-- Comparison Table -->
        <div x-show="items.length > 0" x-cloak class="overflow-x-auto rounded-3xl border border-ui-border bg-surface shadow-sm">
            <table class="w-full border-collapse text-left text-sm">
                <tbody>
                    <!-- Image & Name Row -->
                    <tr class="border-b border-ui-border/60">
                        <td class="w-44 bg-surface-alt/50 p-4 font-bold uppercase tracking-wider text-xs text-muted">Sản phẩm</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-6 text-center align-top min-w-[240px] max-w-[280px]">
                                <div class="relative group">
                                    <button 
                                        @click="removeItem(item.id)" 
                                        class="absolute -top-2 -right-2 size-7 rounded-full bg-rose-100 text-rose-600 hover:bg-rose-200 grid place-items-center transition shadow-sm z-10"
                                        title="Xóa khỏi so sánh"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                    <div class="aspect-square rounded-2xl overflow-hidden bg-surface-alt mb-3">
                                        <img :src="item.image" :alt="item.name" class="h-full w-full object-cover">
                                    </div>
                                    <a :href="item.url" class="font-display font-bold text-heading hover:text-primary transition line-clamp-2" x-text="item.name"></a>
                                    <div class="mt-2 text-primary font-bold text-base" x-text="item.formatted_price"></div>
                                </div>
                            </td>
                        </template>
                    </tr>

                    <!-- Category -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Danh mục</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center text-heading font-medium" x-text="item.category || 'Nội thất'"></td>
                        </template>
                    </tr>

                    <!-- Materials -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Chất liệu</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center text-body" x-text="item.materials"></td>
                        </template>
                    </tr>

                    <!-- Colors -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Màu sắc</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center text-body" x-text="item.colors"></td>
                        </template>
                    </tr>

                    <!-- Sizes -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Kích thước</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center text-body" x-text="item.sizes"></td>
                        </template>
                    </tr>

                    <!-- Stock Status -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Tình trạng kho</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center">
                                <span 
                                    class="inline-block rounded-full px-3 py-1 text-xs font-bold"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': item.total_stock > 10,
                                        'bg-amber-500/10 text-amber-600 dark:text-amber-400': item.total_stock > 0 && item.total_stock <= 10,
                                        'bg-rose-500/10 text-rose-600 dark:text-rose-400': item.total_stock <= 0
                                    }"
                                    x-text="item.stock_status"
                                ></span>
                            </td>
                        </template>
                    </tr>

                    <!-- Rating -->
                    <tr class="border-b border-ui-border/60">
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Đánh giá</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-4 text-center text-body">
                                <template x-if="item.avg_rating">
                                    <div class="flex items-center justify-center gap-1.5 text-amber-500">
                                        <svg class="size-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <span class="font-bold text-heading" x-text="item.avg_rating"></span>
                                        <span class="text-xs text-muted" x-text="'(' + item.reviews_count + ')'"></span>
                                    </div>
                                </template>
                                <template x-if="!item.avg_rating">
                                    <span class="text-xs text-muted">Chưa có đánh giá</span>
                                </template>
                            </td>
                        </template>
                    </tr>

                    <!-- Action -->
                    <tr>
                        <td class="bg-surface-alt/50 p-4 font-bold text-xs text-muted uppercase tracking-wider">Hành động</td>
                        <template x-for="item in items" :key="item.id">
                            <td class="p-6 text-center">
                                <a :href="item.url" class="w-full btn-primary inline-flex items-center justify-center text-xs">
                                    Xem chi tiết
                                </a>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('productCompare', () => ({
        items: [],
        init() {
            this.loadFromStorage();
        },
        async loadFromStorage() {
            let saved = [];
            try {
                saved = JSON.parse(localStorage.getItem('moc_an_compare_ids') || '[]');
            } catch(e) {
                saved = [];
            }

            if (!Array.isArray(saved) || saved.length === 0) {
                this.items = [];
                return;
            }

            try {
                const res = await fetch('/api/products/compare?ids=' + saved.join(','));
                const data = await res.json();
                this.items = data.products || [];
            } catch (err) {
                console.error('Failed to load compare items', err);
            }
        },
        removeItem(id) {
            let saved = [];
            try {
                saved = JSON.parse(localStorage.getItem('moc_an_compare_ids') || '[]');
            } catch(e) {}
            saved = saved.filter(item => item !== id);
            localStorage.setItem('moc_an_compare_ids', JSON.stringify(saved));
            this.items = this.items.filter(item => item.id !== id);
            window.dispatchEvent(new CustomEvent('compare-updated', { detail: { count: saved.length } }));
        },
        clearAll() {
            localStorage.removeItem('moc_an_compare_ids');
            this.items = [];
            window.dispatchEvent(new CustomEvent('compare-updated', { detail: { count: 0 } }));
        }
    }));
});
</script>
@endsection
