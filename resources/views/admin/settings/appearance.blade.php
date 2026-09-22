@extends('layouts.admin')

@section('title', 'Tùy biến giao diện | Quản trị Mộc An')
@section('header-title', 'Tùy biến giao diện website')

@section('content')
<div
    class="space-y-6"
    x-data="{
        site_name: '{{ addslashes($settings['site_name']) }}',
        site_tagline: '{{ addslashes($settings['site_tagline']) }}',
        site_hotline: '{{ addslashes($settings['site_hotline']) }}',
        site_email: '{{ addslashes($settings['site_email']) }}',
        site_address: '{{ addslashes($settings['site_address']) }}',
        promo_bar_enabled: {{ $settings['promo_bar_enabled'] ? 'true' : 'false' }},
        promo_bar_text: '{{ addslashes($settings['promo_bar_text']) }}',
        default_theme: '{{ $settings['default_theme'] }}',
        font_primary: '{{ addslashes($settings['font_primary']) }}',
        font_heading: '{{ addslashes($settings['font_heading']) }}',
        hero_title: '{{ addslashes($settings['hero_title']) }}',
        hero_subtitle: '{{ addslashes($settings['hero_subtitle']) }}',
        show_hero: {{ $settings['show_hero'] ? 'true' : 'false' }},
        show_trust_badges: {{ $settings['show_trust_badges'] ? 'true' : 'false' }},
        show_categories: {{ $settings['show_categories'] ? 'true' : 'false' }},
        show_best_sellers: {{ $settings['show_best_sellers'] ? 'true' : 'false' }},
        show_testimonials: {{ $settings['show_testimonials'] ? 'true' : 'false' }},
        footer_copyright: '{{ addslashes($settings['footer_copyright']) }}',
        activeTab: 'branding',
        previewDevice: 'desktop'
    }"
>
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-heading">Tùy biến diện mạo website</h1>
            <p class="text-xs text-muted mt-1">Tùy chỉnh thông tin thương hiệu, bố cục trang chủ, chủ đề màu sắc và xem trước trực tiếp.</p>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.appearance.reset') }}" onsubmit="return confirm('Bạn có chắc chắn muốn khôi phục toàn bộ cài đặt về mặc định?');">
                @csrf
                <button
                    type="submit"
                    class="px-4 py-2.5 rounded-xl border border-ui-border bg-surface text-xs font-semibold text-muted hover:text-red-600 hover:border-red-300 transition cursor-pointer"
                >
                    Khôi phục mặc định
                </button>
            </form>

            <button
                type="submit"
                form="appearance-form"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold shadow-sm hover:opacity-95 transition cursor-pointer"
            >
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Lưu thay đổi</span>
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-2.5">
            <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Split-pane Customizer -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Controls Column (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <!-- Navigation Tabs -->
            <div class="flex items-center gap-1.5 p-1.5 rounded-2xl bg-surface border border-ui-border overflow-x-auto text-xs">
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl font-semibold transition shrink-0 cursor-pointer"
                    :class="activeTab === 'branding' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
                    @click="activeTab = 'branding'"
                >
                    Thương hiệu
                </button>
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl font-semibold transition shrink-0 cursor-pointer"
                    :class="activeTab === 'header' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
                    @click="activeTab = 'header'"
                >
                    Header & Promo
                </button>
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl font-semibold transition shrink-0 cursor-pointer"
                    :class="activeTab === 'theme' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
                    @click="activeTab = 'theme'"
                >
                    Màu & Font
                </button>
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl font-semibold transition shrink-0 cursor-pointer"
                    :class="activeTab === 'homepage' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
                    @click="activeTab = 'homepage'"
                >
                    Trang chủ
                </button>
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl font-semibold transition shrink-0 cursor-pointer"
                    :class="activeTab === 'footer' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted hover:text-heading hover:bg-surface-alt'"
                    @click="activeTab = 'footer'"
                >
                    Chân trang
                </button>
            </div>

            <form id="appearance-form" method="POST" action="{{ route('admin.appearance.update') }}">
                @csrf

                <!-- Tab 1: Branding -->
                <div x-show="activeTab === 'branding'" class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs space-y-4 text-xs">
                    <h3 class="font-display text-base font-bold text-heading pb-2 border-b border-ui-border">Nhận diện thương hiệu</h3>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Tên thương hiệu</label>
                        <input
                            type="text"
                            name="site_name"
                            x-model="site_name"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Khẩu hiệu (Tagline)</label>
                        <input
                            type="text"
                            name="site_tagline"
                            x-model="site_tagline"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Hotline hỗ trợ</label>
                            <input
                                type="text"
                                name="site_hotline"
                                x-model="site_hotline"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                        </div>
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Email liên hệ</label>
                            <input
                                type="email"
                                name="site_email"
                                x-model="site_email"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Địa chỉ showroom / xưởng</label>
                        <input
                            type="text"
                            name="site_address"
                            x-model="site_address"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>
                </div>

                <!-- Tab 2: Header & Promo -->
                <div x-show="activeTab === 'header'" x-cloak class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs space-y-4 text-xs">
                    <h3 class="font-display text-base font-bold text-heading pb-2 border-b border-ui-border">Thanh thông báo & Header</h3>

                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-surface-alt/60 border border-ui-border">
                        <div>
                            <span class="font-bold text-heading block">Hiển thị thanh Promo Bar</span>
                            <span class="text-[11px] text-muted">Dải thông báo ưu đãi ở vị trí cao nhất trên trang</span>
                        </div>
                        <input type="hidden" name="promo_bar_enabled" :value="promo_bar_enabled ? '1' : '0'">
                        <button
                            type="button"
                            @click="promo_bar_enabled = !promo_bar_enabled"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="promo_bar_enabled ? 'bg-primary' : 'bg-stone-300 dark:bg-stone-700'"
                            role="switch"
                        >
                            <span
                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="promo_bar_enabled ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Nội dung thông báo ưu đãi</label>
                        <textarea
                            name="promo_bar_text"
                            x-model="promo_bar_text"
                            rows="2"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        ></textarea>
                    </div>
                </div>

                <!-- Tab 3: Theme & Typography -->
                <div x-show="activeTab === 'theme'" x-cloak class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs space-y-4 text-xs">
                    <h3 class="font-display text-base font-bold text-heading pb-2 border-b border-ui-border">Chủ đề giao diện & Phông chữ</h3>

                    <div>
                        <label class="block font-semibold text-heading mb-2">Bảng màu chủ đạo mặc định</label>
                        <div class="grid grid-cols-5 gap-2">
                            <button
                                type="button"
                                @click="default_theme = 'wood'"
                                class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 transition text-center cursor-pointer"
                                :class="default_theme === 'wood' ? 'border-primary ring-2 ring-primary/20 bg-surface-alt' : 'border-ui-border hover:border-heading/40'"
                            >
                                <span class="size-5 rounded-full bg-[#8B5A2B]"></span>
                                <span class="text-[10px] font-bold">Wood</span>
                            </button>
                            <button
                                type="button"
                                @click="default_theme = 'moss'"
                                class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 transition text-center cursor-pointer"
                                :class="default_theme === 'moss' ? 'border-primary ring-2 ring-primary/20 bg-surface-alt' : 'border-ui-border hover:border-heading/40'"
                            >
                                <span class="size-5 rounded-full bg-[#4A5D4E]"></span>
                                <span class="text-[10px] font-bold">Moss</span>
                            </button>
                            <button
                                type="button"
                                @click="default_theme = 'cream'"
                                class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 transition text-center cursor-pointer"
                                :class="default_theme === 'cream' ? 'border-primary ring-2 ring-primary/20 bg-surface-alt' : 'border-ui-border hover:border-heading/40'"
                            >
                                <span class="size-5 rounded-full bg-[#D4C3A3]"></span>
                                <span class="text-[10px] font-bold">Cream</span>
                            </button>
                            <button
                                type="button"
                                @click="default_theme = 'blue'"
                                class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 transition text-center cursor-pointer"
                                :class="default_theme === 'blue' ? 'border-primary ring-2 ring-primary/20 bg-surface-alt' : 'border-ui-border hover:border-heading/40'"
                            >
                                <span class="size-5 rounded-full bg-[#2C4A6F]"></span>
                                <span class="text-[10px] font-bold">Blue</span>
                            </button>
                            <button
                                type="button"
                                @click="default_theme = 'black'"
                                class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 transition text-center cursor-pointer"
                                :class="default_theme === 'black' ? 'border-primary ring-2 ring-primary/20 bg-surface-alt' : 'border-ui-border hover:border-heading/40'"
                            >
                                <span class="size-5 rounded-full bg-[#1C1917]"></span>
                                <span class="text-[10px] font-bold">Black</span>
                            </button>
                        </div>
                        <input type="hidden" name="default_theme" :value="default_theme">
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Phông chữ tiêu đề</label>
                            <select
                                name="font_heading"
                                x-model="font_heading"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                                <option value="Playfair Display">Playfair Display (Thanh lịch)</option>
                                <option value="Merriweather">Merriweather (Cổ điển)</option>
                                <option value="Be Vietnam Pro">Be Vietnam Pro (Đương đại)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Phông chữ nội dung</label>
                            <select
                                name="font_primary"
                                x-model="font_primary"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                                <option value="Be Vietnam Pro">Be Vietnam Pro (Chuẩn Việt)</option>
                                <option value="Inter">Inter (Hiện đại)</option>
                                <option value="Roboto">Roboto (Phổ thông)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Homepage Sections -->
                <div x-show="activeTab === 'homepage'" x-cloak class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs space-y-4 text-xs">
                    <h3 class="font-display text-base font-bold text-heading pb-2 border-b border-ui-border">Bố cục khối trang chủ</h3>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Tiêu đề Hero Banner</label>
                        <input
                            type="text"
                            name="hero_title"
                            x-model="hero_title"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Mô tả phụ Hero Banner</label>
                        <textarea
                            name="hero_subtitle"
                            x-model="hero_subtitle"
                            rows="2"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        ></textarea>
                    </div>

                    <div class="space-y-2.5 pt-2">
                        <label class="block font-bold text-heading uppercase tracking-wider text-[11px]">Bật / Tắt các phân đoạn trang chủ</label>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-alt border border-ui-border">
                            <span class="font-medium text-heading">Khối Hero Banner chào mừng</span>
                            <input type="hidden" name="show_hero" :value="show_hero ? '1' : '0'">
                            <input type="checkbox" x-model="show_hero" class="size-4 rounded text-primary focus:ring-primary">
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-alt border border-ui-border">
                            <span class="font-medium text-heading">Khối 4 cam kết chất lượng (Trust Badges)</span>
                            <input type="hidden" name="show_trust_badges" :value="show_trust_badges ? '1' : '0'">
                            <input type="checkbox" x-model="show_trust_badges" class="size-4 rounded text-primary focus:ring-primary">
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-alt border border-ui-border">
                            <span class="font-medium text-heading">Khối Danh mục nổi bật</span>
                            <input type="hidden" name="show_categories" :value="show_categories ? '1' : '0'">
                            <input type="checkbox" x-model="show_categories" class="size-4 rounded text-primary focus:ring-primary">
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-alt border border-ui-border">
                            <span class="font-medium text-heading">Khối Sản phẩm bán chạy (Best Sellers)</span>
                            <input type="hidden" name="show_best_sellers" :value="show_best_sellers ? '1' : '0'">
                            <input type="checkbox" x-model="show_best_sellers" class="size-4 rounded text-primary focus:ring-primary">
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface-alt border border-ui-border">
                            <span class="font-medium text-heading">Khối Đánh giá khách hàng (Testimonials)</span>
                            <input type="hidden" name="show_testimonials" :value="show_testimonials ? '1' : '0'">
                            <input type="checkbox" x-model="show_testimonials" class="size-4 rounded text-primary focus:ring-primary">
                        </div>
                    </div>
                </div>

                <!-- Tab 5: Footer & Social -->
                <div x-show="activeTab === 'footer'" x-cloak class="rounded-3xl border border-ui-border bg-surface p-6 shadow-xs space-y-4 text-xs">
                    <h3 class="font-display text-base font-bold text-heading pb-2 border-b border-ui-border">Chân trang & Mạng xã hội</h3>

                    <div>
                        <label class="block font-semibold text-heading mb-1.5">Dòng chữ bản quyền Footer</label>
                        <input
                            type="text"
                            name="footer_copyright"
                            x-model="footer_copyright"
                            class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Liên kết Facebook</label>
                            <input
                                type="url"
                                name="social_facebook"
                                value="{{ $settings['social_facebook'] }}"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Liên kết Instagram</label>
                            <input
                                type="url"
                                name="social_instagram"
                                value="{{ $settings['social_instagram'] }}"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                        </div>
                        <div>
                            <label class="block font-semibold text-heading mb-1.5">Liên kết YouTube</label>
                            <input
                                type="url"
                                name="social_youtube"
                                value="{{ $settings['social_youtube'] }}"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Live Preview Column (7 cols) -->
        <div class="lg:col-span-7 sticky top-6">
            <div class="rounded-3xl border border-ui-border bg-surface shadow-md overflow-hidden flex flex-col">
                <!-- Preview Device Toolbar -->
                <div class="px-5 py-3 border-b border-ui-border bg-surface-alt flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-red-400 inline-block"></span>
                        <span class="size-3 rounded-full bg-amber-400 inline-block"></span>
                        <span class="size-3 rounded-full bg-emerald-400 inline-block"></span>
                        <span class="text-xs font-bold text-heading ml-2">Xem trước trực tiếp (Live Storefront Preview)</span>
                    </div>

                    <div class="flex items-center gap-1 bg-surface rounded-xl p-1 border border-ui-border text-xs">
                        <button
                            type="button"
                            @click="previewDevice = 'desktop'"
                            class="px-2.5 py-1 rounded-lg font-medium transition cursor-pointer"
                            :class="previewDevice === 'desktop' ? 'bg-primary text-primary-foreground font-bold' : 'text-muted hover:text-heading'"
                        >
                            Desktop
                        </button>
                        <button
                            type="button"
                            @click="previewDevice = 'mobile'"
                            class="px-2.5 py-1 rounded-lg font-medium transition cursor-pointer"
                            :class="previewDevice === 'mobile' ? 'bg-primary text-primary-foreground font-bold' : 'text-muted hover:text-heading'"
                        >
                            Mobile
                        </button>
                    </div>
                </div>

                <!-- Preview Window Wrapper -->
                <div class="p-4 sm:p-6 bg-stone-100 dark:bg-stone-900/50 flex justify-center min-h-[580px] max-h-[750px] overflow-y-auto">
                    <div
                        class="bg-surface rounded-2xl border border-ui-border shadow-xl overflow-hidden transition-all duration-300 flex flex-col"
                        :class="previewDevice === 'desktop' ? 'w-full' : 'w-[375px]'"
                    >
                        <!-- Live Simulated Promo Bar -->
                        <div
                            x-show="promo_bar_enabled"
                            x-transition
                            class="bg-primary text-primary-foreground px-4 py-2 text-[11px] text-center font-medium truncate flex items-center justify-between"
                        >
                            <span class="truncate flex-1 text-center" x-text="promo_bar_text"></span>
                            <span class="hidden sm:inline font-mono font-bold text-[10px]" x-text="site_hotline"></span>
                        </div>

                        <!-- Live Simulated Header -->
                        <div class="px-5 py-3.5 border-b border-ui-border flex items-center justify-between bg-surface/90">
                            <div class="flex items-center gap-2.5">
                                <span class="size-8 rounded-lg bg-primary text-primary-foreground font-display font-bold text-sm grid place-items-center">M</span>
                                <div>
                                    <div class="font-display font-bold text-sm text-heading leading-tight" x-text="site_name || 'Mộc An'"></div>
                                    <div class="text-[9px] uppercase tracking-wider text-accent leading-none" x-text="site_tagline || 'Nội thất'"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-muted">
                                <span class="hidden sm:inline">Trang chủ</span>
                                <span class="hidden sm:inline">Sản phẩm</span>
                                <span class="hidden sm:inline">Bộ sưu tập</span>
                                <span class="size-7 rounded-full bg-surface-alt border border-ui-border grid place-items-center font-bold text-heading">🛒</span>
                            </div>
                        </div>

                        <!-- Live Simulated Hero Section -->
                        <div x-show="show_hero" x-transition class="p-6 sm:p-8 bg-surface-alt/40 border-b border-ui-border text-center relative overflow-hidden">
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-accent">Bộ sưu tập chế tác thủ công</span>
                            <h2 class="font-display text-lg sm:text-xl font-bold text-heading mt-2 max-w-md mx-auto" x-text="hero_title"></h2>
                            <p class="text-xs text-muted mt-2 max-w-sm mx-auto line-clamp-2" x-text="hero_subtitle"></p>
                            <div class="mt-4 flex items-center justify-center gap-2">
                                <span class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-[11px] font-bold shadow-xs">Khám phá ngay</span>
                                <span class="px-4 py-2 rounded-xl border border-ui-border bg-surface text-[11px] font-medium text-heading">Xem danh mục</span>
                            </div>
                        </div>

                        <!-- Live Simulated Trust Badges -->
                        <div x-show="show_trust_badges" x-transition class="p-4 border-b border-ui-border grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-[10px] text-muted">
                            <div class="p-2 rounded-lg bg-surface-alt/50 border border-ui-border">🚚 Giao tận nơi</div>
                            <div class="p-2 rounded-lg bg-surface-alt/50 border border-ui-border">🛡️ Bảo hành 24T</div>
                            <div class="p-2 rounded-lg bg-surface-alt/50 border border-ui-border">🌿 100% Gỗ tự nhiên</div>
                            <div class="p-2 rounded-lg bg-surface-alt/50 border border-ui-border">🔄 Đổi trả 7 ngày</div>
                        </div>

                        <!-- Live Simulated Categories / Best Sellers -->
                        <div class="p-5 space-y-4 flex-1">
                            <div x-show="show_categories" x-transition>
                                <div class="text-[11px] font-bold uppercase tracking-wider text-heading mb-2">Danh mục nổi bật</div>
                                <div class="grid grid-cols-3 gap-2 text-[10px] text-center">
                                    <div class="p-3 rounded-xl bg-surface-alt border border-ui-border font-semibold">Phòng khách</div>
                                    <div class="p-3 rounded-xl bg-surface-alt border border-ui-border font-semibold">Phòng ăn</div>
                                    <div class="p-3 rounded-xl bg-surface-alt border border-ui-border font-semibold">Phòng ngủ</div>
                                </div>
                            </div>

                            <div x-show="show_best_sellers" x-transition class="pt-2">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-heading mb-2">Sản phẩm bán chạy</div>
                                <div class="grid grid-cols-2 gap-2 text-[10px]">
                                    <div class="p-2.5 rounded-xl border border-ui-border bg-surface">
                                        <div class="aspect-4/3 rounded-lg bg-stone-200 dark:bg-stone-800 mb-2"></div>
                                        <div class="font-bold text-heading truncate">Bàn ăn Osaka Gỗ Sồi</div>
                                        <div class="text-primary font-bold mt-0.5">8.500.000₫</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl border border-ui-border bg-surface">
                                        <div class="aspect-4/3 rounded-lg bg-stone-200 dark:bg-stone-800 mb-2"></div>
                                        <div class="font-bold text-heading truncate">Ghế Sofa Kyoto Tự Nhiên</div>
                                        <div class="text-primary font-bold mt-0.5">14.200.000₫</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Simulated Testimonials -->
                            <div x-show="show_testimonials" x-transition class="pt-2">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-heading mb-2">Nhận xét khách hàng</div>
                                <div class="p-3 rounded-xl bg-surface-alt/70 border border-ui-border text-[11px] text-muted italic">
                                    "Bàn ghế hoàn thiện mịn màng, gỗ thơm tự nhiên. Đội ngũ Mộc An giao lắp rất tận tình."
                                </div>
                            </div>
                        </div>

                        <!-- Live Simulated Footer -->
                        <div class="mt-auto px-5 py-3 border-t border-ui-border bg-surface-alt/60 text-[10px] text-muted flex items-center justify-between">
                            <span x-text="footer_copyright || '© 2026 Mộc An Woodworks'"></span>
                            <span class="font-mono font-semibold" x-text="site_hotline"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
