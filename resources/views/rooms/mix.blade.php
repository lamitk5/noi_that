@extends('layouts.app')

@section('title', 'Phối combo | Mộc An')

@section('content')
@php
    $catalog = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) ($product->variants->first(fn ($variant) => $variant->stock > 0)?->final_price ?? $product->final_price),
        'image' => $product->primary_image_url,
    ])->values();
@endphp
<div
    class="bg-page min-h-screen py-10"
    x-data="roomMix({
        catalog: {{ \Illuminate\Support\Js::from($catalog) }},
        rooms: {{ \Illuminate\Support\Js::from($rooms) }},
        room: {{ \Illuminate\Support\Js::from($layout['room'] ?? 'khach') }},
        placed: {{ \Illuminate\Support\Js::from($layout['items'] ?? []) }}
    })"
>
    <div class="page-shell">
        <p class="eyebrow">Phòng mẫu</p>
        <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Phối combo</h1>
        <p class="mt-3 max-w-2xl text-sm text-muted">Kéo món từ danh sách thả lên ảnh phòng, rồi kéo tiếp để chỉnh vị trí. Tổng tiền cập nhật theo giá đang bán. Mua trọn bộ đưa cả combo vào giỏ.</p>

        @if (session('success'))
            <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</p>
        @endif

        <div class="mt-6 flex flex-wrap gap-2">
            <template x-for="(meta, key) in rooms" :key="key">
                <button type="button" class="rounded-full px-4 py-2 text-xs font-bold" :class="room === key ? 'bg-primary text-primary-foreground' : 'border border-ui-border bg-surface text-heading'" @click="room = key" x-text="meta.label"></button>
            </template>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <div
                    class="relative aspect-16/10 overflow-hidden rounded-3xl border border-ui-border bg-surface"
                    x-ref="stage"
                    @pointermove="move($event)"
                    @pointerup="end($event)"
                    @pointercancel="end($event)"
                >
                    <img :src="rooms[room].image" alt="" class="size-full object-cover">
                    <template x-for="item in placed" :key="item.product_id">
                        <button
                            type="button"
                            class="absolute w-24 -translate-x-1/2 -translate-y-1/2 touch-none"
                            :style="`left:${item.x}%; top:${item.y}%`"
                            @pointerdown.prevent="start(item, $event)"
                        >
                            <img :src="lookup(item.product_id)?.image" :alt="lookup(item.product_id)?.name" class="h-20 w-full rounded-xl border border-white object-cover shadow-lg">
                        </button>
                    </template>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-heading">Tổng combo: <strong x-text="format(total)"></strong></p>
                    <form method="POST" action="{{ route('rooms.mix.save') }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="room" :value="room">
                        <template x-for="(item, index) in placed" :key="'buy-' + item.product_id">
                            <span>
                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                <input type="hidden" :name="`items[${index}][x]`" :value="item.x">
                                <input type="hidden" :name="`items[${index}][y]`" :value="item.y">
                            </span>
                        </template>
                        <button type="submit" class="rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs font-bold text-heading">Lưu bố cục</button>
                        <button type="submit" formaction="{{ route('rooms.mix.buy') }}" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground">Mua trọn bộ</button>
                    </form>
                </div>
            </div>
            <div class="lg:col-span-4 max-h-[640px] space-y-3 overflow-y-auto">
                <template x-for="product in catalog" :key="product.id">
                    <button type="button" class="flex w-full items-center gap-3 rounded-2xl border border-ui-border bg-surface p-3 text-left touch-none" @pointerdown.prevent="startCatalog(product, $event)">
                        <img :src="product.image" :alt="product.name" class="size-16 rounded-xl object-cover">
                        <span>
                            <span class="block text-sm font-semibold text-heading" x-text="product.name"></span>
                            <span class="text-xs text-muted" x-text="format(product.price)"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
<script>
    function roomMix(config) {
        return {
            catalog: config.catalog,
            rooms: config.rooms,
            room: config.room || 'khach',
            placed: (Array.isArray(config.placed) ? config.placed : []).filter((item) => config.catalog.some((product) => product.id === item.product_id)),
            dragging: null,
            dragSource: null,
            dragOrigin: null,
            pointerBound: false,
            lookup(id) {
                return this.catalog.find((product) => product.id === id);
            },
            get total() {
                return this.placed.reduce((sum, item) => sum + (this.lookup(item.product_id)?.price || 0), 0);
            },
            pointOnStage(event) {
                if (!this.$refs.stage) return null;
                const rect = this.$refs.stage.getBoundingClientRect();
                if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) {
                    return null;
                }
                return {
                    x: Math.min(92, Math.max(8, ((event.clientX - rect.left) / rect.width) * 100)),
                    y: Math.min(88, Math.max(12, ((event.clientY - rect.top) / rect.height) * 100)),
                };
            },
            place(product, x, y) {
                if (this.placed.some((item) => item.product_id === product.id) || this.placed.length >= 12) return;
                this.placed.push({ product_id: product.id, x, y });
            },
            bindPointer() {
                if (this.pointerBound) return;
                this.pointerBound = true;
                this.onMove = (event) => this.move(event);
                this.onEnd = (event) => this.end(event);
                window.addEventListener('pointermove', this.onMove);
                window.addEventListener('pointerup', this.onEnd);
                window.addEventListener('pointercancel', this.onEnd);
            },
            unbindPointer() {
                if (!this.pointerBound) return;
                window.removeEventListener('pointermove', this.onMove);
                window.removeEventListener('pointerup', this.onEnd);
                window.removeEventListener('pointercancel', this.onEnd);
                this.pointerBound = false;
            },
            startCatalog(product, event) {
                this.dragSource = product;
                this.dragOrigin = { x: event.clientX, y: event.clientY };
                this.bindPointer();
            },
            start(item, event) {
                this.dragging = item;
                this.bindPointer();
            },
            move(event) {
                if (!this.dragging) return;
                const point = this.pointOnStage(event);
                if (!point) return;
                this.dragging.x = point.x;
                this.dragging.y = point.y;
            },
            end(event) {
                if (!event) return;
                if (this.dragSource) {
                    const point = this.pointOnStage(event);
                    const tap = this.dragOrigin && Math.hypot(event.clientX - this.dragOrigin.x, event.clientY - this.dragOrigin.y) < 8;
                    if (point) this.place(this.dragSource, point.x, point.y);
                    else if (tap) this.place(this.dragSource, 50, 60);
                }
                this.dragging = null;
                this.dragSource = null;
                this.dragOrigin = null;
                this.unbindPointer();
            },
            format(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
            }
        };
    }
</script>
@endsection
