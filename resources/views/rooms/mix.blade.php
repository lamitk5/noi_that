@extends('layouts.app')

@section('title', 'Phối phòng 3D | Mộc An')

@section('content')
<script>
    function roomPlanner(config) {
        let stage = null;
        const palettes = new Map();
        const NO_COLLIDE = ['rug', 'pendant', 'wall-lamp', 'curtain'];
        const AGAINST_WALL = ['bed', 'sofa', 'tv', 'cabinet', 'shelf', 'nightstand', 'screen', 'curtain', 'mirror', 'wall-lamp', 'tub'];
        const DEFAULT_COLORS = { main: '#d9cbb7', accent: '#9a6b43' };
        let uidSeed = 0;

        function extractPalette(img) {
            const size = 72;
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, size, size);
            const data = ctx.getImageData(Math.round(size * 0.2), Math.round(size * 0.25), Math.round(size * 0.6), Math.round(size * 0.6)).data;
            const buckets = new Map();
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i], g = data[i + 1], b = data[i + 2];
                const key = (r >> 5) * 64 + (g >> 5) * 8 + (b >> 5);
                const bucket = buckets.get(key) || { n: 0, r: 0, g: 0, b: 0 };
                bucket.n++; bucket.r += r; bucket.g += g; bucket.b += b;
                buckets.set(key, bucket);
            }
            const clusters = [...buckets.values()]
                .map((c) => ({ n: c.n, r: c.r / c.n, g: c.g / c.n, b: c.b / c.n }))
                .sort((a, b) => b.n - a.n);
            const lum = (c) => 0.2126 * c.r + 0.7152 * c.g + 0.0722 * c.b;
            const hex = (c) => '#' + [c.r, c.g, c.b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('');
            const primary = clusters[0];
            const secondary = clusters.slice(1).find((c) => c.n >= primary.n * 0.15 && Math.abs(lum(c) - lum(primary)) > 40);
            const accent = secondary || { r: primary.r * 0.7, g: primary.g * 0.7, b: primary.b * 0.7 };
            return { main: hex(primary), accent: hex(accent) };
        }

        return {
            catalog: config.catalog,
            rooms: config.rooms,
            roomKey: 'khach',
            customWidth: 4,
            customDepth: 4,
            floor: 'oak',
            wall: '#f5f0e8',
            placed: [],
            selectedUid: null,
            view: '3d',
            search: '',
            category: 'Tất cả',
            pick: {},
            ready: false,
            failed: false,
            floors: [
                { key: 'oak', label: 'Gỗ sồi', swatch: '#d9c3a2' },
                { key: 'walnut', label: 'Óc chó', swatch: '#8a6446' },
                { key: 'stone', label: 'Đá', swatch: '#dcd8d1' },
            ],
            walls: ['#f5f0e8', '#e8e4dd', '#e3e9e2', '#efe0d2', '#dfe4ea'],

            init() {
                this.catalog.forEach((p) => {
                    this.pick[p.id] = (p.sizes.find((s) => s.in_stock) || p.sizes[0]).variant_id;
                });

                const saved = config.layout || {};
                if (this.rooms[saved.room]) this.roomKey = saved.room;
                if (saved.width) this.customWidth = saved.width;
                if (saved.depth) this.customDepth = saved.depth;
                if (saved.floor) this.floor = saved.floor;
                if (saved.wall) this.wall = saved.wall;
                (saved.items || []).forEach((raw) => {
                    const product = this.findProduct(raw.product_id);
                    if (!product) return;
                    const size = product.sizes.find((s) => s.variant_id === raw.variant_id) || product.sizes[0];
                    this.placed.push({ uid: this.newUid(), product_id: product.id, variant_id: size.variant_id, x: raw.x, y: raw.y, rotation: raw.rotation || 0 });
                });

                const boot = () => {
                    if (stage) return;
                    try {
                        stage = new window.MocanRoomStage(this.$refs.stage, {
                            label: this.$refs.label,
                            onSelect: (uid) => { this.selectedUid = uid; },
                            onMove: (uid, x, y) => this.moveTo(uid, x, y),
                            onDragEnd: () => this.refresh(),
                        });
                    } catch (error) {
                        console.error(error);
                        this.failed = true;
                        return;
                    }
                    stage.setRoom(this.roomSpec());
                    this.placed.forEach((item) => { this.clamp(item); this.loadPalette(this.findProduct(item.product_id)); });
                    this.ready = true;
                    const focus = config.focusProduct ? this.findProduct(config.focusProduct) : null;
                    if (focus && !this.placed.some((i) => i.product_id === focus.id)) {
                        this.add(focus);
                    } else {
                        this.refresh();
                    }
                };

                if (window.MocanRoomStage) this.$nextTick(boot);
                else window.addEventListener('mocan-room-stage', () => this.$nextTick(boot), { once: true });

                window.addEventListener('keydown', (event) => this.onKey(event));
            },

            newUid() {
                uidSeed += 1;
                return 'i' + Date.now().toString(36) + uidSeed;
            },

            findProduct(id) {
                return this.catalog.find((p) => p.id === Number(id));
            },

            sizeOf(item) {
                const product = this.findProduct(item.product_id);
                return product.sizes.find((s) => s.variant_id === Number(item.variant_id)) || product.sizes[0];
            },

            pickedSize(product) {
                return product.sizes.find((s) => s.variant_id === Number(this.pick[product.id])) || product.sizes[0];
            },

            get room() {
                const preset = this.rooms[this.roomKey];
                if (this.roomKey !== 'custom') return preset;
                return { ...preset, width: this.dim(this.customWidth), depth: this.dim(this.customDepth) };
            },

            dim(value) {
                const n = Number(value);
                return Number.isFinite(n) ? Math.min(15, Math.max(1.5, Math.round(n * 10) / 10)) : 4;
            },

            roomSpec() {
                return { width: this.room.width, depth: this.room.depth, height: this.room.height, floor: this.floor, wall: this.wall };
            },

            footprint(item) {
                const box = this.sizeOf(item).box;
                const turned = Math.round((item.rotation || 0) / 90) % 2 === 1;
                return { w: turned ? box.w : box.l, d: turned ? box.l : box.w };
            },

            isElevated(item) {
                const shape = this.findProduct(item.product_id).shape;
                return shape === 'pendant' || shape === 'wall-lamp' || (shape === 'mirror' && this.sizeOf(item).box.h < 120);
            },

            elevation(item) {
                const shape = this.findProduct(item.product_id).shape;
                const box = this.sizeOf(item).box;
                if (shape === 'pendant') return Math.max(1.9, this.room.height - box.h / 100 - 0.45);
                if (shape === 'wall-lamp') return 1.75;
                if (shape === 'mirror' && box.h < 120) return 1.0;
                return 0;
            },

            collides(item) {
                return !NO_COLLIDE.includes(this.findProduct(item.product_id).shape) && !this.isElevated(item);
            },

            overlap(a, b) {
                const fa = this.footprint(a);
                const fb = this.footprint(b);
                const dx = (fa.w + fb.w) / 2 - Math.abs(a.x - b.x);
                const dy = (fa.d + fb.d) / 2 - Math.abs(a.y - b.y);
                return dx > 1 && dy > 1;
            },

            get conflictUids() {
                const solid = this.placed.filter((i) => this.collides(i));
                const hits = new Set();
                for (let i = 0; i < solid.length; i++) {
                    for (let j = i + 1; j < solid.length; j++) {
                        if (this.overlap(solid[i], solid[j])) {
                            hits.add(solid[i].uid);
                            hits.add(solid[j].uid);
                        }
                    }
                }
                return hits;
            },

            get oversized() {
                return this.placed.filter((item) => {
                    const fp = this.footprint(item);
                    return fp.w > this.room.width * 100 || fp.d > this.room.depth * 100;
                });
            },

            get usedArea() {
                return this.placed
                    .filter((i) => this.collides(i))
                    .reduce((sum, i) => {
                        const fp = this.footprint(i);
                        return sum + (fp.w * fp.d) / 10000;
                    }, 0);
            },

            get occupancy() {
                return Math.min(100, Math.round((this.usedArea / (this.room.width * this.room.depth)) * 100));
            },

            get status() {
                if (this.occupancy <= 35) return { label: 'Thoáng', tone: 'bg-emerald-500', text: 'text-emerald-700', hint: 'Lối đi rộng, đi lại thoải mái.' };
                if (this.occupancy <= 55) return { label: 'Vừa vặn', tone: 'bg-amber-500', text: 'text-amber-700', hint: 'Chừa lối đi khoảng 70–90 cm giữa các món.' };
                return { label: 'Chật', tone: 'bg-rose-500', text: 'text-rose-700', hint: 'Đồ chiếm hơn nửa sàn, nên bớt hoặc chọn size nhỏ hơn.' };
            },

            get warnings() {
                const lines = [];
                if (this.conflictUids.size) lines.push(`${this.conflictUids.size} món đang chồng lên nhau (viền đỏ). Kéo ra hoặc đổi size.`);
                this.oversized.forEach((item) => lines.push(`${this.findProduct(item.product_id).name} (${this.sizeOf(item).label}) lớn hơn phòng.`));
                return lines;
            },

            get rows() {
                return this.placed.map((item) => ({ item, product: this.findProduct(item.product_id), size: this.sizeOf(item), conflict: this.conflictUids.has(item.uid) }));
            },

            get selected() {
                const item = this.placed.find((i) => i.uid === this.selectedUid);
                return item ? { item, product: this.findProduct(item.product_id), size: this.sizeOf(item) } : null;
            },

            get distances() {
                if (!this.selected) return null;
                const { item } = this.selected;
                const fp = this.footprint(item);
                return {
                    left: Math.max(0, Math.round(item.x - fp.w / 2)),
                    right: Math.max(0, Math.round(this.room.width * 100 - item.x - fp.w / 2)),
                    back: Math.max(0, Math.round(item.y - fp.d / 2)),
                    front: Math.max(0, Math.round(this.room.depth * 100 - item.y - fp.d / 2)),
                };
            },

            get totalPrice() {
                return this.placed.reduce((sum, item) => sum + this.sizeOf(item).price, 0);
            },

            get categories() {
                return ['Tất cả', ...new Set(this.catalog.map((p) => p.category))];
            },

            get filteredCatalog() {
                const q = this.search.trim().toLowerCase();
                return this.catalog.filter((p) => (this.category === 'Tất cả' || p.category === this.category) && (!q || p.name.toLowerCase().includes(q)));
            },

            placedCount(productId) {
                return this.placed.filter((i) => i.product_id === productId).length;
            },

            stageItems() {
                return this.placed.map((item) => {
                    const product = this.findProduct(item.product_id);
                    const size = this.sizeOf(item);
                    const elevation = this.elevation(item);
                    return {
                        uid: item.uid,
                        name: product.name,
                        shape: product.shape,
                        box: size.box,
                        colors: palettes.get(product.id) || DEFAULT_COLORS,
                        x: item.x,
                        y: item.y,
                        rotation: item.rotation || 0,
                        elevation,
                        cord: product.shape === 'pendant' ? Math.max(0.05, this.room.height - elevation - size.box.h / 100) : 0,
                    };
                });
            },

            refresh() {
                if (!stage) return;
                stage.setItems(this.stageItems());
                stage.setConflicts([...this.conflictUids]);
                stage.select(this.selectedUid);
            },

            loadPalette(product) {
                if (!product || palettes.has(product.id)) return;
                palettes.set(product.id, null);
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    try {
                        palettes.set(product.id, extractPalette(img));
                        this.refresh();
                    } catch (error) {
                        palettes.delete(product.id);
                    }
                };
                img.src = product.image;
            },

            clamp(item) {
                const fp = this.footprint(item);
                const W = this.room.width * 100;
                const D = this.room.depth * 100;
                item.x = fp.w >= W ? W / 2 : Math.min(W - fp.w / 2, Math.max(fp.w / 2, item.x));
                item.y = fp.d >= D ? D / 2 : Math.min(D - fp.d / 2, Math.max(fp.d / 2, item.y));
            },

            freeSpot(item) {
                const others = this.placed.filter((i) => i.uid !== item.uid && this.collides(i));
                const free = () => !this.collides(item) || !others.some((o) => this.overlap(item, o));
                const shape = this.findProduct(item.product_id).shape;
                const W = this.room.width * 100;
                const D = this.room.depth * 100;
                const fp = this.footprint(item);

                if (shape === 'rug' || shape === 'pendant') {
                    item.x = W / 2;
                    item.y = D / 2;
                    return;
                }
                if (AGAINST_WALL.includes(shape)) {
                    item.y = fp.d / 2 + 1;
                    for (let x = fp.w / 2 + 10; x <= W - fp.w / 2; x += 10) {
                        item.x = x;
                        if (free()) return;
                    }
                }
                for (let ring = 0; ring < 40; ring++) {
                    for (let step = 0; step < Math.max(1, ring * 8); step++) {
                        const angle = (step / Math.max(1, ring * 8)) * Math.PI * 2;
                        item.x = W / 2 + Math.cos(angle) * ring * 15;
                        item.y = D / 2 + Math.sin(angle) * ring * 15;
                        this.clamp(item);
                        if (free()) return;
                    }
                }
                item.x = W / 2;
                item.y = D / 2;
            },

            add(product, variantId = null) {
                if (this.placed.length >= 30) {
                    window.Toast?.error('Phòng đã có 30 món, hãy gỡ bớt trước khi thêm.');
                    return;
                }
                const item = { uid: this.newUid(), product_id: product.id, variant_id: variantId ?? Number(this.pick[product.id]), x: 0, y: 0, rotation: 0 };
                this.freeSpot(item);
                this.clamp(item);
                this.placed.push(item);
                this.selectedUid = item.uid;
                this.loadPalette(product);
                this.refresh();
            },

            duplicate(uid) {
                const source = this.placed.find((i) => i.uid === uid);
                if (!source) return;
                const item = { ...source, uid: this.newUid(), x: source.x + this.footprint(source).w + 10 };
                this.clamp(item);
                if (this.placed.some((o) => this.collides(o) && this.overlap(item, o))) this.freeSpot(item);
                this.placed.push(item);
                this.selectedUid = item.uid;
                this.refresh();
            },

            moveTo(uid, x, y) {
                const item = this.placed.find((i) => i.uid === uid);
                if (!item) return;
                item.x = x;
                item.y = y;
                this.clamp(item);
                this.refresh();
            },

            nudge(dx, dy) {
                if (!this.selected) return;
                this.moveTo(this.selectedUid, this.selected.item.x + dx, this.selected.item.y + dy);
            },

            rotate(uid, deg = 90) {
                const item = this.placed.find((i) => i.uid === uid);
                if (!item) return;
                item.rotation = ((item.rotation || 0) + deg + 360) % 360;
                this.clamp(item);
                this.refresh();
            },

            setSize(uid, variantId) {
                const item = this.placed.find((i) => i.uid === uid);
                if (!item) return;
                item.variant_id = Number(variantId);
                this.clamp(item);
                this.refresh();
            },

            remove(uid) {
                this.placed = this.placed.filter((i) => i.uid !== uid);
                if (this.selectedUid === uid) this.selectedUid = null;
                this.refresh();
            },

            clearAll() {
                this.placed = [];
                this.selectedUid = null;
                this.refresh();
            },

            select(uid) {
                this.selectedUid = uid;
                if (stage) stage.select(uid);
            },

            applyRoom() {
                this.placed.forEach((item) => this.clamp(item));
                if (stage) stage.setRoom(this.roomSpec());
                this.refresh();
            },

            setRoom(key) {
                if (key === 'custom' && this.roomKey !== 'custom') {
                    this.customWidth = this.room.width;
                    this.customDepth = this.room.depth;
                }
                this.roomKey = key;
                this.applyRoom();
            },

            setDims() {
                this.roomKey = 'custom';
                this.applyRoom();
            },

            setFloor(key) {
                this.floor = key;
                this.applyRoom();
            },

            setWall(color) {
                this.wall = color;
                this.applyRoom();
            },

            setView(view) {
                this.view = view;
                if (stage) stage.setView(view);
            },

            download() {
                if (!stage) return;
                const link = document.createElement('a');
                link.href = stage.snapshot();
                link.download = 'phoi-phong-moc-an.png';
                link.click();
            },

            onKey(event) {
                if (!this.selectedUid || ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target.tagName)) return;
                const key = event.key;
                if (key === 'Delete' || key === 'Backspace') { event.preventDefault(); this.remove(this.selectedUid); }
                else if (key === 'r' || key === 'R') this.rotate(this.selectedUid, 90);
                else if (key === 'Escape') this.select(null);
                else if (key === 'ArrowLeft') { event.preventDefault(); this.nudge(-5, 0); }
                else if (key === 'ArrowRight') { event.preventDefault(); this.nudge(5, 0); }
                else if (key === 'ArrowUp') { event.preventDefault(); this.nudge(0, -5); }
                else if (key === 'ArrowDown') { event.preventDefault(); this.nudge(0, 5); }
            },

            money(amount) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(amount || 0)) + '₫';
            },

            cm(box) {
                return `${Math.round(box.l)} × ${Math.round(box.w)} × ${Math.round(box.h)} cm`;
            },
        };
    }
</script>

<div
    class="bg-page min-h-screen py-8 lg:py-10"
    x-data="roomPlanner({
        catalog: {{ \Illuminate\Support\Js::from($catalog) }},
        rooms: {{ \Illuminate\Support\Js::from($rooms) }},
        layout: {{ \Illuminate\Support\Js::from($layout) }},
        focusProduct: {{ \Illuminate\Support\Js::from($focusProduct) }}
    })"
>
    <div class="page-shell space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow text-xs font-bold uppercase tracking-wider text-primary">Phối phòng 3D</p>
                <h1 class="mt-1 font-display text-3xl sm:text-4xl font-semibold text-heading">Đặt nội thất vào phòng của bạn</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted">Nhập kích thước phòng, chọn đúng size từng món rồi kéo thả. Mọi khối đều theo tỉ lệ thật 1:1, có khoảng cách tới tường và cảnh báo khi đồ chồng lên nhau.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl border border-ui-border bg-surface p-1 shadow-sm">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-bold transition" :class="view === '3d' ? 'bg-primary text-primary-foreground' : 'text-heading hover:bg-surface-alt'" @click="setView('3d')">Góc 3D</button>
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-bold transition" :class="view === 'top' ? 'bg-primary text-primary-foreground' : 'text-heading hover:bg-surface-alt'" @click="setView('top')">Mặt bằng</button>
                </div>
                <button type="button" class="rounded-xl border border-ui-border bg-surface px-3 py-2 text-xs font-semibold text-heading shadow-sm hover:bg-surface-alt" @click="setView(view)">Căn lại khung nhìn</button>
                <button type="button" class="rounded-xl border border-ui-border bg-surface px-3 py-2 text-xs font-semibold text-heading shadow-sm hover:bg-surface-alt" @click="download()" :disabled="!ready">Tải ảnh phòng</button>
                <button type="button" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 shadow-sm hover:bg-rose-100" x-show="placed.length" @click="clearAll()">Làm trống</button>
            </div>
        </div>

        @if (session('success'))
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</p>
        @endif

        <div class="grid gap-4 rounded-2xl border border-ui-border bg-surface p-4 shadow-sm md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Phòng</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <template x-for="(meta, key) in rooms" :key="key">
                        <button type="button" class="rounded-full px-3.5 py-1.5 text-xs font-bold transition" :class="roomKey === key ? 'bg-primary text-primary-foreground shadow' : 'border border-ui-border bg-surface text-heading hover:bg-surface-alt'" @click="setRoom(key)">
                            <span x-text="meta.label"></span>
                            <span class="ml-1 text-[10px] opacity-80" x-show="key !== 'custom'" x-text="`${meta.width}×${meta.depth} m`"></span>
                        </button>
                    </template>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-muted">
                    <label class="flex items-center gap-1.5">Rộng
                        <input type="number" step="0.1" min="1.5" max="15" class="w-20 rounded-lg border border-ui-border bg-page px-2 py-1.5 text-sm text-heading" :value="room.width" @change="customWidth = $event.target.value; customDepth = room.depth; setDims()">
                    </label>
                    <span>×</span>
                    <label class="flex items-center gap-1.5">Sâu
                        <input type="number" step="0.1" min="1.5" max="15" class="w-20 rounded-lg border border-ui-border bg-page px-2 py-1.5 text-sm text-heading" :value="room.depth" @change="customDepth = $event.target.value; customWidth = room.width; setDims()">
                    </label>
                    <span>m</span>
                </div>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Sàn</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <template x-for="f in floors" :key="f.key">
                        <button type="button" class="flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs font-semibold transition" :class="floor === f.key ? 'border-primary ring-2 ring-primary/20 text-heading' : 'border-ui-border text-body hover:border-heading/40'" @click="setFloor(f.key)">
                            <span class="size-4 rounded-full border border-black/10" :style="`background:${f.swatch}`"></span>
                            <span x-text="f.label"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Màu tường</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <template x-for="c in walls" :key="c">
                        <button type="button" class="size-8 rounded-full border transition" :class="wall === c ? 'border-primary ring-2 ring-primary/30' : 'border-ui-border'" :style="`background:${c}`" @click="setWall(c)" :aria-label="`Màu tường ${c}`"></button>
                    </template>
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-12 items-start">
            <div class="lg:col-span-8 space-y-4">
                <div class="relative overflow-hidden rounded-3xl border border-ui-border bg-[#eee7dc] shadow-xl" style="height: 580px;">
                    <div x-ref="stage" class="absolute inset-0 select-none"></div>

                    <div x-ref="label" class="pointer-events-none absolute left-0 top-0 z-10 rounded-full bg-heading/90 px-3 py-1 text-[11px] font-semibold text-white shadow-lg transition-opacity" style="opacity: 0;">
                        <span x-text="selected ? selected.product.name : ''"></span>
                        <span class="opacity-75" x-text="selected ? ' · ' + cm(selected.size.box) : ''"></span>
                    </div>

                    <div x-show="!ready && !failed" class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-[#eee7dc]">
                        <div class="size-10 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                        <p class="mt-3 text-xs font-semibold text-muted">Đang dựng phòng 3D...</p>
                    </div>
                    <div x-show="failed" x-cloak class="absolute inset-0 z-30 flex items-center justify-center bg-[#eee7dc] p-6 text-center text-sm text-rose-700">
                        Trình duyệt không mở được WebGL nên không dựng được phòng 3D. Hãy thử Chrome, Edge hoặc Safari bản mới.
                    </div>

                    <div class="pointer-events-none absolute left-4 top-4 z-20 w-64 rounded-2xl border border-ui-border/70 bg-surface/90 p-3 shadow-lg backdrop-blur">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted">Diện tích đã dùng</p>
                            <span class="text-[11px] font-bold" :class="status.text" x-text="status.label"></span>
                        </div>
                        <p class="mt-1 text-sm font-bold text-heading">
                            <span x-text="occupancy + '%'"></span>
                            <span class="text-[11px] font-medium text-muted" x-text="`· ${usedArea.toFixed(1)} / ${(room.width * room.depth).toFixed(1)} m²`"></span>
                        </p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-stone-200">
                            <div class="h-full rounded-full transition-all" :class="status.tone" :style="`width:${occupancy}%`"></div>
                        </div>
                        <p class="mt-1.5 text-[11px] leading-snug text-muted" x-text="status.hint"></p>
                        <template x-for="line in warnings" :key="line">
                            <p class="mt-1.5 text-[11px] font-semibold leading-snug text-rose-600" x-text="line"></p>
                        </template>
                    </div>

                    <div x-show="ready && !placed.length" class="pointer-events-none absolute inset-x-0 bottom-24 z-10 flex justify-center">
                        <p class="rounded-full bg-surface/90 px-4 py-2 text-xs font-semibold text-heading shadow">Chọn size và bấm “Thêm” ở danh sách bên phải để đặt món vào phòng.</p>
                    </div>

                    <div x-show="selected" x-transition class="absolute inset-x-3 bottom-3 z-20">
                        <template x-if="selected">
                            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-primary/30 bg-surface/95 p-3 shadow-2xl backdrop-blur">
                                <img :src="selected.product.image" alt="" class="size-12 rounded-xl border border-ui-border object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-heading" x-text="selected.product.name"></p>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <template x-for="s in selected.product.sizes" :key="s.variant_id">
                                            <button type="button" class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold transition" :class="s.variant_id === selected.size.variant_id ? 'border-primary bg-primary text-primary-foreground' : 'border-ui-border text-body hover:border-heading/40'" @click="setSize(selected.item.uid, s.variant_id)" x-text="s.label"></button>
                                        </template>
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted" x-show="distances" x-text="distances ? `Cách tường trái ${distances.left} cm · phải ${distances.right} cm · sau ${distances.back} cm · trước ${distances.front} cm` : ''"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-primary" x-text="money(selected.size.price)"></p>
                                    <a :href="selected.product.url" class="text-[11px] font-semibold text-muted underline hover:text-heading">Xem sản phẩm</a>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" class="rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs font-semibold text-heading hover:bg-stone-200" @click="rotate(selected.item.uid, 90)" title="Xoay 90° (phím R)">Xoay</button>
                                    <button type="button" class="rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs font-semibold text-heading hover:bg-stone-200" @click="duplicate(selected.item.uid)">Nhân bản</button>
                                    <button type="button" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100" @click="remove(selected.item.uid)" title="Gỡ (phím Delete)">Gỡ</button>
                                    <button type="button" class="p-2 text-muted hover:text-heading" @click="select(null)" aria-label="Bỏ chọn">✕</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p x-show="!selected" class="pointer-events-none absolute bottom-3 right-4 z-10 hidden rounded-full bg-heading/75 px-3 py-1 text-[10px] text-white sm:block">
                        Kéo món để di chuyển · Kéo nền để xoay phòng · Cuộn để phóng to · R xoay · Delete gỡ · Mũi tên dịch 5 cm
                    </p>
                </div>

                <div class="rounded-2xl border border-ui-border bg-surface p-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-muted">Tổng giá trị phòng</p>
                            <p class="mt-0.5 flex items-baseline gap-2">
                                <span class="font-display text-2xl font-bold text-primary" x-text="money(totalPrice)"></span>
                                <span class="text-xs text-muted" x-text="`${placed.length} món`"></span>
                            </p>
                        </div>
                        <form method="POST" action="{{ route('rooms.mix.save') }}" class="flex flex-wrap items-center gap-2.5">
                            @csrf
                            <input type="hidden" name="room" :value="roomKey">
                            <input type="hidden" name="width" :value="room.width">
                            <input type="hidden" name="depth" :value="room.depth">
                            <input type="hidden" name="floor" :value="floor">
                            <input type="hidden" name="wall" :value="wall">
                            <template x-for="(item, index) in placed" :key="'f-' + item.uid">
                                <span>
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${index}][variant_id]`" :value="item.variant_id">
                                    <input type="hidden" :name="`items[${index}][x]`" :value="Math.round(item.x)">
                                    <input type="hidden" :name="`items[${index}][y]`" :value="Math.round(item.y)">
                                    <input type="hidden" :name="`items[${index}][rotation]`" :value="item.rotation || 0">
                                </span>
                            </template>
                            <button type="submit" class="rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-xs font-bold text-heading hover:bg-stone-200">Lưu bố cục</button>
                            <button type="submit" formaction="{{ route('rooms.mix.buy') }}" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground shadow-md transition disabled:cursor-not-allowed disabled:opacity-50" :disabled="placed.length === 0">Mua cả phòng</button>
                        </form>
                    </div>

                    <div class="mt-4 divide-y divide-ui-border border-t border-ui-border" x-show="placed.length">
                        <template x-for="row in rows" :key="row.item.uid">
                            <div class="flex flex-wrap items-center gap-3 py-2.5 cursor-pointer" :class="row.item.uid === selectedUid ? 'bg-primary/5' : ''" @click="select(row.item.uid)">
                                <img :src="row.product.image" alt="" class="size-10 rounded-lg border border-ui-border object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-heading">
                                        <span x-text="row.product.name"></span>
                                        <span x-show="row.conflict" class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Chồng lên món khác</span>
                                    </p>
                                    <p class="text-[11px] text-muted" x-text="cm(row.size.box)"></p>
                                </div>
                                <select class="rounded-lg border border-ui-border bg-page px-2 py-1.5 text-xs text-heading" x-show="row.product.sizes.length > 1" @click.stop @change="setSize(row.item.uid, $event.target.value)">
                                    <template x-for="s in row.product.sizes" :key="s.variant_id">
                                        <option :value="s.variant_id" :selected="s.variant_id === row.size.variant_id" x-text="s.label"></option>
                                    </template>
                                </select>
                                <span class="w-24 text-right text-xs font-bold text-primary" x-text="money(row.size.price)"></span>
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-muted hover:text-heading" @click.stop="rotate(row.item.uid, 90)">Xoay</button>
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800" @click.stop="remove(row.item.uid)">Gỡ</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <aside class="lg:col-span-4 flex flex-col rounded-3xl border border-ui-border bg-surface p-4 shadow-sm lg:sticky lg:top-24" style="max-height: 860px;">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-heading">Nội thất Mộc An</h2>
                    <span class="rounded-full bg-surface-alt px-2.5 py-0.5 text-[11px] font-semibold text-muted" x-text="`${filteredCatalog.length} món`"></span>
                </div>
                <input type="search" x-model="search" placeholder="Tìm sofa, giường, bàn..." class="mt-3 w-full rounded-xl border border-ui-border bg-page px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none">
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <template x-for="c in categories" :key="c">
                        <button type="button" class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition" :class="category === c ? 'bg-heading text-white' : 'bg-surface-alt text-body hover:bg-stone-200'" @click="category = c" x-text="c"></button>
                    </template>
                </div>

                <div class="mt-3 flex-1 space-y-2.5 overflow-y-auto pr-1">
                    <template x-for="product in filteredCatalog" :key="product.id">
                        <div class="rounded-2xl border p-2.5 transition" :class="placedCount(product.id) ? 'border-primary/40 bg-primary/5' : 'border-ui-border hover:bg-surface-alt'">
                            <div class="flex gap-3">
                                <img :src="product.image" :alt="product.name" loading="lazy" class="size-16 shrink-0 rounded-xl border border-ui-border bg-stone-100 object-cover">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="truncate text-xs font-bold text-heading" x-text="product.name"></h3>
                                        <span x-show="placedCount(product.id)" class="shrink-0 rounded-full bg-primary px-1.5 text-[10px] font-bold text-primary-foreground" x-text="'×' + placedCount(product.id)"></span>
                                    </div>
                                    <p class="mt-0.5 text-xs font-bold text-primary" x-text="money(pickedSize(product).price)"></p>
                                    <p class="text-[10px] text-muted" x-text="cm(pickedSize(product).box)"></p>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <select x-show="product.sizes.length > 1" x-model.number="pick[product.id]" class="min-w-0 flex-1 rounded-lg border border-ui-border bg-page px-2 py-1.5 text-[11px] text-heading">
                                    <template x-for="s in product.sizes" :key="s.variant_id">
                                        <option :value="s.variant_id" x-text="s.label + (s.in_stock ? '' : ' (hết hàng)')"></option>
                                    </template>
                                </select>
                                <span x-show="product.sizes.length <= 1" class="min-w-0 flex-1 truncate text-[11px] text-muted">Một kích thước</span>
                                <button type="button" class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-[11px] font-bold text-primary-foreground shadow-sm transition hover:opacity-90 disabled:opacity-50" :disabled="!ready" @click="add(product)">+ Thêm</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!filteredCatalog.length" class="py-6 text-center text-xs text-muted">Không tìm thấy món phù hợp.</p>
                </div>
            </aside>
        </div>
    </div>
</div>

@vite('resources/js/room-stage.js')
@endsection
