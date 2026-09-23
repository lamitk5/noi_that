@extends('layouts.app')

@section('title', 'Mộc An | Nội thất cho tổ ấm Việt')

@section('content')
    <section class="relative isolate min-h-[690px] overflow-hidden bg-[#ded8cb]">
        <!-- Gradient overlay: dark on the left, fades to transparent on the right -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 via-45% to-black/10 sm:to-transparent"></div>
        <div class="absolute inset-0 bg-black/20 mix-blend-multiply sm:hidden"></div>
        <img
            src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=2000&q=88"
            alt="Phòng khách hiện đại với nội thất gỗ tự nhiên"
            class="absolute inset-0 -z-10 size-full object-cover object-center"
        >

        <div class="page-shell relative z-10 flex min-h-[690px] items-center py-20 sm:py-24">
            <div class="max-w-2xl text-white">
                <p class="eyebrow hero-reveal text-[#e0bd91]">Bộ sưu tập Thu Đông 2026</p>
                <h1 class="hero-reveal hero-reveal-delay mt-7 font-display text-5xl font-medium leading-[1.06] tracking-[-0.035em] text-white drop-shadow-md sm:text-6xl lg:text-7xl">
                    Chạm vào sự<br><span class="italic text-[#e9d3b5]">an nhiên</span> trong tổ ấm
                </h1>
                <p class="hero-reveal hero-reveal-late mt-8 max-w-xl text-base leading-8 text-white/90 drop-shadow-sm sm:text-lg">Sofa, bàn trà và nội thất tinh giản từ vật liệu tự nhiên, được tuyển chọn để mỗi góc nhà đều mang cảm giác ấm áp và riêng biệt.</p>
                <div class="hero-reveal hero-reveal-late mt-10 flex flex-wrap gap-4">
                    <a href="{{ route('products.index') }}" class="button-primary">Khám phá sản phẩm <span aria-hidden="true">→</span></a>
                    <a href="#bo-suu-tap" class="button-ghost">Xem bộ sưu tập <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        </div>

        <!-- Showroom / warranty stats — floating with clear edge margins -->
        <div class="absolute bottom-8 right-8 z-10 hidden rounded-2xl border border-ui-border/80 bg-surface/95 px-8 py-5 text-heading shadow-xl shadow-black/15 backdrop-blur-md transition-all hover:bg-surface md:block lg:bottom-14 lg:right-14">
            <div class="flex items-center gap-7 lg:gap-9">
                <div class="flex flex-col gap-0.5">
                    <strong class="font-display text-2xl font-semibold">02</strong>
                    <span class="text-[11px] uppercase tracking-widest text-muted">Showroom</span>
                </div>
                <span class="h-10 w-px bg-ui-border"></span>
                <div class="flex flex-col gap-0.5">
                    <strong class="font-display text-2xl font-semibold">5 năm</strong>
                    <span class="text-[11px] uppercase tracking-widest text-muted">Bảo hành</span>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-ui-border bg-surface">
        <div class="page-shell grid grid-cols-1 divide-y divide-ui-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ([
                ['truck', 'Giao hàng tận nơi', 'Nhanh chóng, an toàn'],
                ['shield', 'Bảo hành chính hãng', 'Cam kết đến 5 năm'],
                ['chat', 'Tư vấn không gian', 'Hỗ trợ hoàn toàn miễn phí'],
            ] as [$icon, $title, $description])
                <div class="reveal-on-scroll flex items-center justify-center gap-4 px-6 py-8 sm:py-10 lg:gap-5 lg:py-12">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary/10 text-primary shadow-xs">
                        @if ($icon === 'truck')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                        @elseif ($icon === 'shield')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 5 6v5c0 4.7 2.8 8.1 7 10 4.2-1.9 7-5.3 7-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 text-left">
                        <h3 class="text-sm font-bold tracking-tight text-heading whitespace-nowrap lg:text-base">{{ $title }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="bo-suu-tap" class="bg-page py-14 sm:py-18 lg:py-22">
        <div class="page-shell">
            <div class="reveal-on-scroll flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div class="min-w-0">
                    <p class="eyebrow">Danh mục nổi bật</p>
                    <h2 class="mt-1.5 font-display text-3xl font-medium tracking-tight text-heading sm:text-4xl lg:text-5xl">Tìm cảm hứng cho từng không gian</h2>
                </div>
                <a href="{{ route('products.index') }}" class="text-link shrink-0 self-start sm:self-auto">Xem tất cả <span aria-hidden="true">→</span></a>
            </div>

            @php
                $categoryImages = [
                    'phong-khach' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=85',
                    'phong-ngu' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1000&q=85',
                    'phong-an' => 'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=1000&q=85',
                    'phong-lam-viec' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1000&q=85',
                ];
            @endphp

            <div class="mt-5 grid gap-4 sm:mt-6 sm:gap-5 md:grid-cols-2 lg:grid-cols-12 lg:gap-6">
                @forelse ($categories->take(3) as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-card reveal-on-scroll group min-h-[300px] sm:min-h-[340px] {{ $loop->first ? 'md:col-span-2 md:min-h-[420px] lg:col-span-7 lg:row-span-2 lg:min-h-[540px]' : 'md:col-span-1 lg:col-span-5 lg:min-h-[255px]' }}" data-delay="{{ $loop->index * 90 }}">
                        <img
                            src="{{ $categoryImages[$category->slug] ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1000&q=85' }}"
                            alt="Nội thất {{ $category->name }}"
                            class="category-image"
                        >
                        <span class="category-overlay"></span>
                        <span class="absolute inset-x-0 bottom-0 z-10 p-4 sm:p-6 lg:p-7">
                            <span class="inline-flex max-w-full flex-col rounded-2xl border border-white/20 bg-black/55 px-5 py-4 shadow-xl shadow-black/30 backdrop-blur-md transition-all duration-300 group-hover:border-white/30 group-hover:bg-black/65">
                                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/85">{{ $category->products_count }} sản phẩm</span>
                                <span class="mt-1 block font-display {{ $loop->first ? 'text-2xl sm:text-3xl lg:text-4xl' : 'text-xl sm:text-2xl lg:text-3xl' }} font-medium text-white [text-shadow:0_1px_12px_rgba(0,0,0,0.45)]">{{ $category->name }}</span>
                                <span class="mt-2.5 inline-flex items-center gap-2 text-xs font-semibold text-white/95 transition-colors group-hover:text-white sm:text-sm">
                                    Khám phá <span class="transition-transform duration-300 group-hover:translate-x-1">→</span>
                                </span>
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-muted md:col-span-2 lg:col-span-12">Chưa có danh mục đang hoạt động.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if (isset($flashSaleProducts) && $flashSaleProducts->count() > 0)
        <section id="flash-sale" class="py-12 relative overflow-hidden" style="background: linear-gradient(135deg, #181513 0%, #2b1a0d 50%, #181513 100%) !important; color: #ffffff !important;">
            <div class="page-shell">
                <!-- Header with Title & Live Countdown Timer -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-8 border-b" style="border-color: rgba(255, 255, 255, 0.15) !important;">
                    <div class="flex items-center gap-3.5">
                        <span class="flex size-12 items-center justify-center rounded-2xl font-black text-2xl shadow-lg" style="background: #f59e0b; color: #181513; box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);">
                            ⚡
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider" style="background: #e11d48; color: #ffffff;">Giới hạn thời gian</span>
                                <span class="text-xs font-medium" style="color: #fde68a;">Giảm tới 30% hôm nay</span>
                            </div>
                            <h2 class="mt-1 font-display text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #ffffff !important;">
                                FLASH SALE GIỜ VÀNG
                            </h2>
                        </div>
                    </div>

                    <!-- Countdown Timer Block -->
                    <div class="flex items-center gap-2 px-5 py-3 rounded-2xl" id="flash-sale-countdown" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18);">
                        <span class="text-xs uppercase tracking-widest font-semibold mr-2" style="color: #fde68a;">Kết thúc trong</span>
                        <div class="flex items-center gap-1.5 font-mono text-lg font-bold">
                            <span id="fs-hours" class="px-2.5 py-1 rounded-lg" style="background: #0c0a09; color: #fbbf24; border: 1px solid rgba(255, 255, 255, 0.15);">08</span>
                            <span style="color: #ffffff;">:</span>
                            <span id="fs-minutes" class="px-2.5 py-1 rounded-lg" style="background: #0c0a09; color: #fbbf24; border: 1px solid rgba(255, 255, 255, 0.15);">45</span>
                            <span style="color: #ffffff;">:</span>
                            <span id="fs-seconds" class="px-2.5 py-1 rounded-lg" style="background: #0c0a09; color: #f43f5e; border: 1px solid rgba(255, 255, 255, 0.15);">00</span>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($flashSaleProducts as $product)
                        @php
                            $discountPercent = $product->base_price > 0 ? round((($product->base_price - $product->sale_price) / $product->base_price) * 100) : 0;
                        @endphp
                        <article class="group rounded-2xl overflow-hidden transition duration-300 flex flex-col justify-between shadow-xl" style="background: #24201e !important; border: 1px solid rgba(255, 255, 255, 0.12) !important;">
                            <div class="relative aspect-square overflow-hidden" style="background: #141211;">
                                <img
                                    src="{{ $product->primary_image_url }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                                <span class="absolute top-3 left-3 font-black text-xs px-2.5 py-1 rounded-lg shadow" style="background: #e11d48; color: #ffffff;">
                                    -{{ $discountPercent }}%
                                </span>
                            </div>

                            <div class="p-5 flex flex-col flex-1 justify-between gap-3">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: #d6d3d1;">{{ $product->category->name }}</span>
                                    <h3 class="mt-1 font-display font-semibold text-base leading-snug truncate">
                                        <a href="{{ route('products.show', $product->slug) }}" class="transition" style="color: #ffffff;">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                </div>

                                <div class="space-y-2.5">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-lg font-extrabold" style="color: #fbbf24;">{{ number_format($product->sale_price, 0, ',', '.') }}₫</span>
                                        <span class="text-xs line-through" style="color: #a8a29e;">{{ number_format($product->base_price, 0, ',', '.') }}₫</span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div>
                                        <div class="w-full rounded-full h-2 overflow-hidden" style="background: rgba(255, 255, 255, 0.15);">
                                            <div class="h-2 rounded-full" style="width: 78%; background: linear-gradient(90deg, #f59e0b, #ef4444);"></div>
                                        </div>
                                        <span class="text-[10px] font-medium mt-1.5 block" style="color: #fde68a;">⚡ Sắp hết hàng · Đã bán 78%</span>
                                    </div>

                                    <a href="{{ route('products.show', $product->slug) }}" class="block w-full text-center py-2.5 rounded-xl font-bold text-xs transition shadow hover:brightness-110" style="background: #d97706; color: #ffffff;">
                                        Săn Ngay
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <!-- Real-time JS Countdown Script -->
            <script>
                (function() {
                    function updateTimer() {
                        const now = new Date();
                        const midnight = new Date(now);
                        midnight.setHours(23, 59, 59, 999);
                        const diff = Math.max(0, midnight - now);

                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                        const hElem = document.getElementById('fs-hours');
                        const mElem = document.getElementById('fs-minutes');
                        const sElem = document.getElementById('fs-seconds');

                        if (hElem) hElem.textContent = String(hours).padStart(2, '0');
                        if (mElem) mElem.textContent = String(minutes).padStart(2, '0');
                        if (sElem) sElem.textContent = String(seconds).padStart(2, '0');
                    }
                    setInterval(updateTimer, 1000);
                    updateTimer();
                })();
            </script>
        </section>
    @endif

    <section id="san-pham" class="section-space bg-surface">
        <div class="page-shell">
            <div class="section-heading reveal-on-scroll">
                <div>
                    <p class="eyebrow">Được yêu thích</p>
                    <h2 class="section-title">Sản phẩm nổi bật</h2>
                </div>
                <div class="hidden gap-2 sm:flex" aria-label="Điều khiển sản phẩm">
                    <button class="slider-button" type="button" aria-label="Sản phẩm trước">←</button>
                    <button class="slider-button" type="button" aria-label="Sản phẩm tiếp theo">→</button>
                </div>
            </div>

            <div class="mt-10 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($featuredProducts as $product)
                    <article class="product-card reveal-on-scroll group" data-delay="{{ ($loop->index % 4) * 80 }}">
                        <div class="product-media">
                            <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                <img
                                    src="{{ $product->primary_image_url }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80'"
                                >
                            </a>
                            @if ($product->is_on_sale && $product->discount_percent > 0)
                                <span class="absolute left-3 top-3 bg-primary px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-primary-foreground">-{{ $product->discount_percent }}%</span>
                            @elseif ($loop->iteration <= 2)
                                <span class="absolute left-3 top-3 bg-primary px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-primary-foreground">Mới</span>
                            @endif
                            <button class="wishlist-button" type="button" aria-label="Thêm {{ $product->name }} vào yêu thích">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                            </button>
                            <a href="{{ route('products.show', $product->slug) }}" class="quick-add">
                                Xem chi tiết
                            </a>
                        </div>
                        <div class="px-2 pt-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category->name }}</p>
                            <h3 class="mt-2 font-display text-[1.35rem] font-semibold leading-snug text-heading">
                                <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <div class="mt-3 flex items-center gap-2">
                                @if ($product->is_on_sale)
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->final_price, 0, ',', '.') }}₫</span>
                                    <span class="text-xs text-muted line-through">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                                @else
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-muted sm:col-span-2 lg:col-span-4">Chưa có sản phẩm đang hoạt động.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if (isset($bestSellers) && $bestSellers->isNotEmpty())
        <section id="ban-chay" class="section-space bg-surface-alt">
            <div class="page-shell">
                <div class="section-heading reveal-on-scroll">
                    <div>
                        <p class="eyebrow">Xu hướng lựa chọn</p>
                        <h2 class="section-title">Sản phẩm bán chạy</h2>
                    </div>
                    <a href="{{ route('products.index') }}" class="text-link">Xem tất cả <span aria-hidden="true">→</span></a>
                </div>

                <div class="mt-10 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($bestSellers as $product)
                        <article class="product-card reveal-on-scroll group" data-delay="{{ ($loop->index % 4) * 80 }}">
                            <div class="product-media">
                                <a href="{{ route('products.show', $product->slug) }}" class="block size-full">
                                    <img
                                        src="{{ $product->primary_image_url }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80'"
                                    >
                                </a>
                                <span class="absolute left-3 top-3 bg-accent px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-accent-foreground shadow-xs">Bán chạy</span>
                                <button class="wishlist-button" type="button" aria-label="Thêm {{ $product->name }} vào yêu thích">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                </button>
                                <a href="{{ route('products.show', $product->slug) }}" class="quick-add">
                                    Xem chi tiết
                                </a>
                            </div>
                            <div class="px-2 pt-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category?->name }}</p>
                                <h3 class="mt-2 font-display text-[1.35rem] font-semibold leading-snug text-heading">
                                    <a class="transition-colors hover:text-accent" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                </h3>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800">
                                        🔥 Đã bán {{ $product->total_sold ?? 85 }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="ve-chung-toi" class="section-space bg-surface-alt">
        <div class="page-shell grid items-center gap-14 lg:grid-cols-2 lg:gap-24">
            <div class="reveal-on-scroll relative min-h-[540px]">
                <img src="https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1100&q=85" alt="Nghệ nhân hoàn thiện sản phẩm nội thất" class="absolute inset-0 size-[88%] rounded-[2rem] object-cover shadow-xl shadow-black/10">
                <div class="absolute bottom-0 right-0 max-w-[240px] rounded-[1.5rem] bg-primary p-8 text-primary-foreground shadow-2xl shadow-primary/25">
                    <strong class="font-display text-4xl font-medium">10+</strong>
                    <p class="mt-2 text-sm leading-6 text-white/65">Năm theo đuổi nội thất bền vững và tinh giản.</p>
                </div>
            </div>
            <div class="reveal-on-scroll" data-delay="120">
                <p class="eyebrow">Câu chuyện Mộc An</p>
                <h2 class="mt-5 font-display text-4xl font-medium leading-tight text-heading sm:text-5xl">Vẻ đẹp bắt đầu từ những điều chân thật</h2>
                <p class="mt-7 text-base leading-8 text-body">Chúng tôi tin rằng một món đồ tốt không chỉ đẹp ở hình thức. Đó còn là chất liệu được chọn kỹ, tỷ lệ vừa vặn với không gian Việt và trải nghiệm bền bỉ qua năm tháng.</p>
                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="border-l-2 border-accent pl-5">
                        <h3 class="font-bold text-heading">Vật liệu có trách nhiệm</h3>
                        <p class="mt-2 text-sm leading-6 text-muted">Ưu tiên nguồn gỗ rõ ràng và vật liệu thân thiện.</p>
                    </div>
                    <div class="border-l-2 border-accent pl-5">
                        <h3 class="font-bold text-heading">Thiết kế cho người Việt</h3>
                        <p class="mt-2 text-sm leading-6 text-muted">Tinh gọn, linh hoạt và phù hợp nhiều diện tích.</p>
                    </div>
                </div>
                <a href="#" class="text-link mt-9 inline-flex">Tìm hiểu về chúng tôi <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="section-space bg-page">
        <div class="page-shell text-center">
            <div class="reveal-on-scroll">
                <p class="eyebrow">Không gian Mộc An</p>
                <h2 class="section-title mx-auto mt-5">Nhà là nơi câu chuyện bắt đầu</h2>
                <p class="mx-auto mt-6 max-w-2xl text-sm leading-7 text-muted">Theo dõi chúng tôi để cập nhật xu hướng nội thất, cách phối màu và những gợi ý bài trí dễ áp dụng.</p>
            </div>
            <div class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ([
                    'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=700&q=80',
                    'https://images.unsplash.com/photo-1600566753051-f0b89df2dd90?auto=format&fit=crop&w=700&q=80',
                ] as $image)
                    <a href="#" class="reveal-on-scroll group relative aspect-square overflow-hidden rounded-[1.4rem] bg-surface-alt shadow-sm transition-all duration-500 hover:-translate-y-1 hover:shadow-xl" data-delay="{{ $loop->index * 80 }}">
                        <img src="{{ $image }}" alt="Cảm hứng không gian sống Mộc An" class="size-full object-cover transition duration-700 group-hover:scale-105">
                        <span class="absolute inset-0 grid place-items-center bg-primary/0 text-white opacity-0 transition group-hover:bg-primary/45 group-hover:opacity-100">
                            <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[100] -translate-x-1/2 translate-y-8 rounded-full bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground opacity-0 shadow-xl transition duration-300">
        Đã thêm sản phẩm vào giỏ hàng
    </div>
@endsection
