@extends('layouts.app')

@section('title', 'Mộc An | Nội thất cho tổ ấm Việt')

@section('content')
    <section class="relative isolate min-h-[690px] overflow-hidden bg-[#ded8cb]">
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(24,33,28,.82)_0%,rgba(24,33,28,.56)_39%,rgba(24,33,28,.08)_70%)]"></div>
        <img
            src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=2000&q=88"
            alt="Phòng khách hiện đại với nội thất gỗ tự nhiên"
            class="absolute inset-0 -z-10 size-full object-cover object-center"
        >

        <div class="page-shell relative flex min-h-[690px] items-center py-20">
            <div class="max-w-2xl text-white">
                <p class="eyebrow hero-reveal text-[#e0bd91]">Bộ sưu tập Thu Đông 2026</p>
                <h1 class="hero-reveal hero-reveal-delay mt-7 font-display text-5xl font-medium leading-[1.06] tracking-[-0.035em] sm:text-6xl lg:text-7xl">
                    Chạm vào sự<br><span class="italic text-[#e9d3b5]">an nhiên</span> trong tổ ấm
                </h1>
                <p class="hero-reveal hero-reveal-late mt-8 max-w-xl text-base leading-8 text-white/78 sm:text-lg">Nội thất tinh giản từ vật liệu tự nhiên, được tuyển chọn để mỗi góc nhà đều mang cảm giác ấm áp và riêng biệt.</p>
                <div class="hero-reveal hero-reveal-late mt-10 flex flex-wrap gap-4">
                    <a href="{{ route('products.index') }}" class="button-primary">Khám phá sản phẩm <span aria-hidden="true">→</span></a>
                    <a href="#bo-suu-tap" class="button-ghost">Xem bộ sưu tập <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 right-0 hidden border-t border-l border-ui-border bg-surface px-8 py-5 text-heading md:block">
            <div class="flex items-center gap-7">
                <div><strong class="font-display text-2xl font-semibold">02</strong><span class="ml-2 text-xs uppercase tracking-widest text-muted">Showroom</span></div>
                <span class="h-8 w-px bg-ui-border"></span>
                <div><strong class="font-display text-2xl font-semibold">5 năm</strong><span class="ml-2 text-xs uppercase tracking-widest text-muted">Bảo hành</span></div>
            </div>
        </div>
    </section>

    <section class="border-b border-ui-border bg-surface">
        <div class="page-shell grid divide-y divide-ui-border py-4 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ([
                ['truck', 'Giao hàng tận nơi', 'Nhanh chóng, an toàn'],
                ['shield', 'Bảo hành chính hãng', 'Cam kết đến 5 năm'],
                ['chat', 'Tư vấn không gian', 'Hỗ trợ hoàn toàn miễn phí'],
            ] as [$icon, $title, $description])
                <div class="reveal-on-scroll flex items-center gap-5 px-4 py-7 sm:px-6 lg:px-10">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                        @if ($icon === 'truck')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                        @elseif ($icon === 'shield')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 5 6v5c0 4.7 2.8 8.1 7 10 4.2-1.9 7-5.3 7-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                        @endif
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-heading">{{ $title }}</h3>
                        <p class="mt-1 text-xs text-muted">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="bo-suu-tap" class="section-space bg-page">
        <div class="page-shell">
            <div class="section-heading reveal-on-scroll">
                <div>
                    <p class="eyebrow">Danh mục nổi bật</p>
                    <h2 class="section-title">Tìm cảm hứng cho từng không gian</h2>
                </div>
                <a href="{{ route('products.index') }}" class="text-link">Xem tất cả <span aria-hidden="true">→</span></a>
            </div>

            @php
                $categoryImages = [
                    'phong-khach' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=85',
                    'phong-ngu' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1000&q=85',
                    'phong-an' => 'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=1000&q=85',
                    'phong-lam-viec' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1000&q=85',
                ];
            @endphp

            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-12">
                @forelse ($categories->take(3) as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-card reveal-on-scroll group min-h-[360px] {{ $loop->first ? 'md:col-span-2 lg:col-span-7 lg:row-span-2 lg:min-h-[560px]' : 'lg:col-span-5 lg:min-h-[270px]' }}" data-delay="{{ $loop->index * 90 }}">
                        <img
                            src="{{ $categoryImages[$category->slug] ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1000&q=85' }}"
                            alt="Nội thất {{ $category->name }}"
                            class="category-image"
                        >
                        <span class="category-overlay"></span>
                        <span class="category-content">
                            <span class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">{{ $category->products_count }} sản phẩm</span>
                            <span class="mt-2 block font-display {{ $loop->first ? 'text-4xl' : 'text-3xl' }} font-medium">{{ $category->name }}</span>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold">Khám phá <span class="transition-transform group-hover:translate-x-1">→</span></span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-muted md:col-span-2 lg:col-span-12">Chưa có danh mục đang hoạt động.</p>
                @endforelse
            </div>
        </div>
    </section>

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
                            <img
                                src="{{ $product->primaryImage?->image_path ?? 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80' }}"
                                alt="{{ $product->name }}"
                                class="size-full object-cover transition duration-700 group-hover:scale-105"
                            >
                            @if ($loop->iteration <= 2)
                                <span class="absolute left-3 top-3 bg-primary px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-primary-foreground">Mới</span>
                            @endif
                            <button class="wishlist-button" type="button" aria-label="Thêm {{ $product->name }} vào yêu thích">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                            </button>
                            <button class="quick-add" type="button">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                                Thêm vào giỏ
                            </button>
                        </div>
                        <div class="px-2 pt-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $product->category->name }}</p>
                            <h3 class="mt-2 font-display text-[1.35rem] font-semibold leading-snug text-heading"><a class="transition-colors hover:text-accent" href="#">{{ $product->name }}</a></h3>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="text-[15px] font-bold tracking-tight text-body">{{ number_format((float) $product->base_price, 0, ',', '.') }}₫</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-muted sm:col-span-2 lg:col-span-4">Chưa có sản phẩm đang hoạt động.</p>
                @endforelse
            </div>
        </div>
    </section>

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
