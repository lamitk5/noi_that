<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mộc An - Nội thất hiện đại cho không gian sống Việt.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mộc An | Nội thất hiện đại')</title>
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('moc-an-theme');
                var validThemes = ['moss', 'wood', 'cream', 'blue', 'black'];
                if (theme && validThemes.indexOf(theme) !== -1) {
                    document.documentElement.dataset.theme = theme;
                } else {
                    document.documentElement.dataset.theme = 'moss';
                }
            } catch (e) {
                document.documentElement.dataset.theme = 'moss';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-page text-body antialiased transition-colors duration-300">
    @if (\App\Models\SiteSetting::get('promo_bar_enabled', '1') == '1')
    <div class="bg-primary px-4 py-2 text-center text-xs font-medium tracking-wide text-primary-foreground sm:text-xs flex items-center justify-center gap-3">
        <span class="inline-flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px] bg-white/20 px-2 py-0.5 rounded-full">Ưu đãi</span>
        <span>{{ \App\Models\SiteSetting::get('promo_bar_text', 'Miễn phí giao hàng & lắp đặt tận phòng cho đơn từ 5.000.000₫') }}</span>
        <span class="hidden md:inline text-white/60">|</span>
        <span class="hidden md:inline text-white/80">Hotline: <strong class="text-white">{{ \App\Models\SiteSetting::get('site_hotline', '1900 6868') }}</strong></span>
    </div>
    @endif

    <header class="site-header sticky top-0 z-50 border-b border-ui-border bg-header/95 backdrop-blur-xl">
        <div class="page-shell flex h-[76px] items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3" aria-label="Mộc An - Trang chủ">
                <span class="grid size-10 place-items-center rounded-full bg-primary text-primary-foreground transition-transform group-hover:-rotate-6">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 21V8m0 0c-1.4-3.1-4-4.4-7-4 .1 3.3 2 5.6 7 6m0-2c1.4-3.1 4-4.4 7-4-.1 3.3-2 5.6-7 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>
                    <span class="block font-display text-2xl font-semibold leading-none tracking-tight text-primary">Mộc An</span>
                    <span class="mt-1 block text-[9px] font-bold uppercase tracking-[0.3em] text-muted">Living & Home</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 lg:flex" aria-label="Điều hướng chính">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Trang chủ</a>
                <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'is-active' : '' }}">Sản phẩm</a>
                <a href="{{ route('posts.index') }}" class="nav-link {{ request()->routeIs('posts.*') ? 'is-active' : '' }}">Tin tức & Mẹo</a>
                <a href="{{ route('faq.index') }}" class="nav-link {{ request()->routeIs('faq.*') ? 'is-active' : '' }}">Hỏi đáp (FAQ)</a>
                <a href="{{ route('pages.about') }}" class="nav-link {{ request()->routeIs('pages.about') ? 'is-active' : '' }}">Về Mộc An</a>
                <a href="{{ route('pages.contact') }}" class="nav-link {{ request()->routeIs('pages.contact*') ? 'is-active' : '' }}">Liên hệ</a>
            </nav>

            <div class="flex items-center gap-1 sm:gap-2">
                <button
                    class="icon-button"
                    type="button"
                    aria-label="Tìm kiếm sản phẩm"
                    title="Tìm kiếm"
                    @click="$dispatch('open-header-search')"
                >
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </button>

                <a
                    href="{{ route('products.compare') }}"
                    class="icon-button relative hidden sm:grid"
                    aria-label="So sánh sản phẩm"
                    title="So sánh sản phẩm"
                    x-data="{ count: 0 }"
                    x-init="
                        const updateCount = () => {
                            try {
                                const list = JSON.parse(localStorage.getItem('moc-an-compare') || '[]');
                                count = list.length;
                            } catch(e) { count = 0; }
                        };
                        updateCount();
                        window.addEventListener('compare-updated', updateCount);
                        window.addEventListener('storage', updateCount);
                    "
                >
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18m-7 6h7M7 15l-4 3 4 3"/></svg>
                    <template x-if="count > 0">
                        <span class="absolute right-0.5 top-0.5 grid size-4 place-items-center rounded-full bg-primary text-[9px] font-bold text-primary-foreground" x-text="count"></span>
                    </template>
                </a>
                <div
                    class="relative hidden sm:block"
                    x-data="headerSettings"
                    @keydown.escape.window="settingsOpen = false"
                >
                    <button
                        class="icon-button"
                        type="button"
                        aria-label="Cài đặt giao diện và tài khoản"
                        title="Cài đặt"
                        @click="settingsOpen = !settingsOpen"
                        :aria-expanded="settingsOpen.toString()"
                    >
                        <svg viewBox="0 0 24 24" class="size-5 transition-transform duration-300" :class="settingsOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.6 6.6 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="settingsOpen"
                        @click.outside="settingsOpen = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        class="absolute right-0 top-full mt-2 w-72 rounded-2xl border border-ui-border bg-surface p-4 shadow-xl ring-1 ring-black/5 z-50"
                    >
                        <div class="mb-3">
                            <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Tài khoản</span>
                            <div class="mt-2 flex flex-col gap-1 text-sm font-medium text-body">
                                @guest
                                    @if (Route::has('login'))
                                        <a href="{{ route('login') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                            <span>Đăng nhập</span>
                                        </a>
                                    @else
                                        <span class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-muted/60 cursor-not-allowed">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted/40" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                            <span>Đăng nhập</span>
                                        </span>
                                    @endif

                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z"/></svg>
                                            <span>Đăng ký</span>
                                        </a>
                                    @else
                                        <span class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-muted/60 cursor-not-allowed">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted/40" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.765Z"/></svg>
                                            <span>Đăng ký</span>
                                        </span>
                                    @endif
                                @endguest

                                @auth
                                    @if (Route::has('profile.edit'))
                                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                                            <span>Tài khoản của tôi</span>
                                        </a>
                                    @elseif (Route::has('account.index'))
                                        <a href="{{ route('account.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                                            <span>Tài khoản của tôi</span>
                                        </a>
                                    @else
                                        <span class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-muted/60 cursor-not-allowed">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted/40" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                                            <span>Tài khoản của tôi</span>
                                        </span>
                                    @endif

                                    @if (Route::has('orders.index'))
                                        <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                            <span>Lịch sử đơn hàng</span>
                                        </a>
                                    @endif

                                    @if (Route::has('account.addresses.index'))
                                        <a href="{{ route('account.addresses.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                            <span>Sổ địa chỉ nhận hàng</span>
                                        </a>
                                    @endif

                                    @if (Route::has('account.wishlist'))
                                        <a href="{{ route('account.wishlist') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                            <span>Sản phẩm yêu thích</span>
                                        </a>
                                    @endif

                                    @if (Route::has('account.loyalty'))
                                        <a href="{{ route('account.loyalty') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 6v12M9 9h6"/></svg>
                                            <span>Điểm thưởng & Hạng TV</span>
                                        </a>
                                    @endif

                                    @if (Route::has('account.tickets.index'))
                                        <a href="{{ route('account.tickets.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                            <span>Phiếu hỗ trợ (Tickets)</span>
                                        </a>
                                    @endif

                                    @if (Route::has('account.notifications'))
                                        <a href="{{ route('account.notifications') }}" class="flex items-center justify-between rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                            <div class="flex items-center gap-2.5">
                                                <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                                                <span>Thông báo</span>
                                            </div>
                                            @if (auth()->user()->unreadNotifications->count() > 0)
                                                <span class="size-2 rounded-full bg-rose-500"></span>
                                            @endif
                                        </a>
                                    @endif

                                    @if (auth()->user()->isAdmin() && Route::has('admin.dashboard'))
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-accent font-semibold hover:bg-surface-alt transition-colors">
                                            <svg viewBox="0 0 24 24" class="size-4 text-accent" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                                            <span>Trang quản trị</span>
                                        </a>
                                    @endif

                                    @if (Route::has('logout'))
                                        <form method="POST" action="{{ route('logout') }}" class="pt-1">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-red-500 hover:bg-red-500/10 transition-colors">
                                                <svg viewBox="0 0 24 24" class="size-4 text-red-500" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                                                <span>Đăng xuất</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-muted/60 cursor-not-allowed">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted/40" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                                            <span>Đăng xuất</span>
                                        </span>
                                    @endif
                                @endauth
                            </div>
                        </div>

                        <div class="border-t border-ui-border my-3"></div>

                        <div>
                            <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Màu sắc giao diện</span>
                            <div class="mt-3 flex items-center justify-between px-1">
                                <button
                                    type="button"
                                    aria-label="Xanh rêu"
                                    title="Xanh rêu"
                                    @click="setTheme('moss')"
                                    :aria-pressed="theme === 'moss'"
                                    :class="theme === 'moss' ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface scale-105' : 'hover:scale-105 opacity-80 hover:opacity-100'"
                                    class="size-7 rounded-full transition-all duration-200 shadow-sm"
                                    style="background-color: #263a2f;"
                                ></button>
                                <button
                                    type="button"
                                    aria-label="Nâu gỗ"
                                    title="Nâu gỗ"
                                    @click="setTheme('wood')"
                                    :aria-pressed="theme === 'wood'"
                                    :class="theme === 'wood' ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface scale-105' : 'hover:scale-105 opacity-80 hover:opacity-100'"
                                    class="size-7 rounded-full transition-all duration-200 shadow-sm"
                                    style="background-color: #7a4f35;"
                                ></button>
                                <button
                                    type="button"
                                    aria-label="Kem"
                                    title="Kem"
                                    @click="setTheme('cream')"
                                    :aria-pressed="theme === 'cream'"
                                    :class="theme === 'cream' ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface scale-105' : 'hover:scale-105 opacity-80 hover:opacity-100'"
                                    class="size-7 rounded-full transition-all duration-200 shadow-sm border border-ui-border"
                                    style="background-color: #d8c7a8;"
                                ></button>
                                <button
                                    type="button"
                                    aria-label="Xanh dương"
                                    title="Xanh dương"
                                    @click="setTheme('blue')"
                                    :aria-pressed="theme === 'blue'"
                                    :class="theme === 'blue' ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface scale-105' : 'hover:scale-105 opacity-80 hover:opacity-100'"
                                    class="size-7 rounded-full transition-all duration-200 shadow-sm"
                                    style="background-color: #365f78;"
                                ></button>
                                <button
                                    type="button"
                                    aria-label="Đen tối giản"
                                    title="Đen tối giản"
                                    @click="setTheme('black')"
                                    :aria-pressed="theme === 'black'"
                                    :class="theme === 'black' ? 'ring-2 ring-primary ring-offset-2 ring-offset-surface scale-105' : 'hover:scale-105 opacity-80 hover:opacity-100'"
                                    class="size-7 rounded-full transition-all duration-200 shadow-sm"
                                    style="background-color: #202020;"
                                ></button>
                            </div>
                            <div class="mt-2.5 text-center text-xs text-muted font-medium">
                                Đang chọn:
                                <span class="font-semibold text-heading" x-text="{
                                    'moss': 'Xanh rêu',
                                    'wood': 'Nâu gỗ',
                                    'cream': 'Kem',
                                    'blue': 'Xanh dương',
                                    'black': 'Đen tối giản'
                                }[theme] || 'Xanh rêu'">Xanh rêu</span>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('cart.index') }}" class="icon-button relative" aria-label="Giỏ hàng">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2 11h10l2-8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                    <span class="cart-count-badge absolute right-0.5 top-0.5 grid size-4 place-items-center rounded-full bg-accent text-[9px] font-bold text-accent-foreground">{{ $cartCount ?? 0 }}</span>
                </a>
                <button id="menu-toggle" class="icon-button lg:hidden" type="button" aria-label="Mở menu" aria-expanded="false">
                    <svg id="menu-open-icon" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                    <svg id="menu-close-icon" viewBox="0 0 24 24" class="hidden size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" class="hidden border-t border-ui-border bg-header px-5 py-4 lg:hidden" aria-label="Điều hướng di động">
            <div class="mx-auto flex max-w-7xl flex-col">
                <a href="{{ route('home') }}" class="mobile-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Trang chủ</a>
                <a href="{{ route('products.index') }}" class="mobile-nav-link {{ request()->routeIs('products.*') ? 'is-active' : '' }}">Sản phẩm</a>
                <a href="{{ route('posts.index') }}" class="mobile-nav-link {{ request()->routeIs('posts.*') ? 'is-active' : '' }}">Tin tức & Mẹo</a>
                <a href="{{ route('faq.index') }}" class="mobile-nav-link {{ request()->routeIs('faq.*') ? 'is-active' : '' }}">Hỏi đáp (FAQ)</a>
                <a href="{{ route('pages.about') }}" class="mobile-nav-link {{ request()->routeIs('pages.about') ? 'is-active' : '' }}">Về Mộc An</a>
                <a href="{{ route('pages.contact') }}" class="mobile-nav-link {{ request()->routeIs('pages.contact*') ? 'is-active' : '' }}">Liên hệ</a>
                <a href="{{ route('products.compare') }}" class="mobile-nav-link flex items-center justify-between">
                    <span>So sánh sản phẩm</span>
                    <span class="text-xs text-muted">Tối đa 4</span>
                </a>
                <a href="{{ route('cart.index') }}" class="mobile-nav-link flex items-center justify-between">
                    <span>Giỏ hàng</span>
                    <span class="cart-count-badge rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-accent-foreground">{{ $cartCount ?? 0 }}</span>
                </a>
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer id="lien-he" class="bg-footer text-white">
        <div class="page-shell grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:pr-8">
                <div class="font-display text-3xl font-semibold">Mộc An</div>
                <p class="mt-4 text-sm leading-7 text-white/65">Mang vẻ đẹp tự nhiên, tinh giản và ấm áp vào từng không gian sống Việt.</p>
            </div>
            <div>
                <h3 class="footer-title">Khám phá</h3>
                <div class="mt-5 flex flex-col gap-3 text-sm text-white/65">
                    <a href="#san-pham" class="hover:text-white">Sản phẩm mới</a>
                    <a href="#bo-suu-tap" class="hover:text-white">Bộ sưu tập</a>
                    <a href="#" class="hover:text-white">Không gian đẹp</a>
                </div>
            </div>
            <div>
                <h3 class="footer-title">Hỗ trợ & Thông tin</h3>
                <div class="mt-5 flex flex-col gap-3 text-sm text-white/65">
                    <a href="{{ route('pages.purchase-policy') }}" class="hover:text-white">Chính sách mua hàng</a>
                    <a href="{{ route('pages.warranty-policy') }}" class="hover:text-white">Bảo hành 24 tháng</a>
                    <a href="{{ route('pages.return-policy') }}" class="hover:text-white">Chính sách đổi trả</a>
                    <a href="{{ route('faq.index') }}" class="hover:text-white">Câu hỏi thường gặp (FAQ)</a>
                    <a href="{{ route('pages.contact') }}" class="hover:text-white">Liên hệ & Góp ý</a>
                </div>
            </div>
            <div>
                <h3 class="footer-title">Nhận cảm hứng mỗi tuần</h3>
                <p class="mt-5 text-sm leading-6 text-white/65">Ý tưởng bài trí và ưu đãi mới gửi thẳng đến bạn.</p>
                <form class="mt-4 flex border-b border-white/30 pb-2" action="#" method="post">
                    <input class="min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-white/40" type="email" placeholder="Email của bạn">
                    <button class="text-xs font-bold uppercase tracking-widest text-[#d8b184]" type="submit">Đăng ký</button>
                </form>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="page-shell flex flex-col gap-2 py-5 text-xs text-white/45 sm:flex-row sm:items-center sm:justify-between">
                <p>{{ \App\Models\SiteSetting::get('footer_copyright', '© ' . date('Y') . ' Mộc An. All rights reserved.') }}</p>
                <p>Đồ án Phát triển hệ thống thương mại điện tử</p>
            </div>
        </div>
    </footer>

    <!-- Live Search Overlay / Modal -->
    <div
        x-data="headerSearch"
        @open-header-search.window="openSearch()"
        @keydown.escape.window="closeSearch()"
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-50 flex flex-col items-center p-4 sm:p-6 md:p-20 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeSearch()"></div>

        <div
            class="relative w-full max-w-2xl rounded-3xl border border-ui-border bg-surface shadow-2xl overflow-hidden z-10 my-auto"
            @click.outside="closeSearch()"
        >
            <div class="flex items-center border-b border-ui-border px-4 sm:px-6 py-4">
                <svg viewBox="0 0 24 24" class="size-5 text-muted shrink-0 mr-3" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                <input
                    id="header-search-input"
                    type="text"
                    x-model="query"
                    @input="onInput()"
                    placeholder="Tìm kiếm bàn, ghế, sofa, giường, tủ, SKU..."
                    class="w-full bg-transparent text-base sm:text-lg text-heading placeholder:text-muted focus:outline-none"
                >
                <template x-if="loading">
                    <svg class="animate-spin size-5 text-primary shrink-0 ml-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </template>
                <button type="button" @click="closeSearch()" class="p-1 rounded-lg text-muted hover:text-heading ml-2">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-4 sm:p-6 space-y-5">
                <template x-if="results.categories && results.categories.length > 0">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2 block">Danh mục phù hợp</span>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="cat in results.categories" :key="cat.id">
                                <a :href="cat.url" class="px-3 py-1.5 rounded-xl border border-ui-border bg-surface-alt hover:border-primary/50 text-xs font-semibold text-heading flex items-center gap-1.5 transition">
                                    <span x-text="cat.name"></span>
                                    <span class="text-muted text-[10px]" x-text="'(' + cat.products_count + ')'"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="results.products && results.products.length > 0">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2 block">Sản phẩm tìm thấy</span>
                        <div class="divide-y divide-ui-border">
                            <template x-for="p in results.products" :key="p.id">
                                <a :href="p.url" class="flex items-center gap-3.5 py-2.5 hover:bg-surface-alt rounded-xl px-2 transition">
                                    <img :src="p.image || '/images/placeholder.png'" class="size-12 rounded-lg object-cover border border-ui-border shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-sm text-heading truncate" x-text="p.name"></div>
                                        <div class="text-xs text-muted truncate" x-text="p.category"></div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-sm text-primary" x-text="p.formatted_price"></span>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="(!results.products || results.products.length === 0) && results.popular && results.popular.length > 0">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2 block">Gợi ý sản phẩm nổi bật</span>
                        <div class="divide-y divide-ui-border">
                            <template x-for="p in results.popular" :key="p.id">
                                <a :href="p.url" class="flex items-center gap-3.5 py-2.5 hover:bg-surface-alt rounded-xl px-2 transition">
                                    <img :src="p.image || '/images/placeholder.png'" class="size-12 rounded-lg object-cover border border-ui-border shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-sm text-heading truncate" x-text="p.name"></div>
                                        <div class="text-xs text-muted truncate" x-text="p.category"></div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-sm text-primary" x-text="p.formatted_price"></span>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="query.trim() !== '' && (!results.products || results.products.length === 0) && (!results.categories || results.categories.length === 0)">
                    <div class="text-center py-6 text-muted text-sm">
                        Không tìm thấy sản phẩm hoặc danh mục nào phù hợp với "<span class="font-semibold text-heading" x-text="query"></span>".
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Global Quick View Modal -->
    <div
        x-data="quickViewModal"
        @open-quick-view.window="openModal($event.detail)"
        @keydown.escape.window="closeModal()"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>

        <div
            class="relative w-full max-w-3xl rounded-3xl border border-ui-border bg-surface shadow-2xl overflow-hidden z-10 my-auto"
            @click.outside="closeModal()"
        >
            <button type="button" @click="closeModal()" class="absolute top-4 right-4 z-20 p-2 rounded-full bg-surface-alt/80 hover:bg-surface-alt text-muted hover:text-heading transition">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>

            <template x-if="loading">
                <div class="p-16 flex flex-col items-center justify-center gap-3">
                    <svg class="animate-spin size-8 text-primary" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-xs text-muted">Đang tải thông tin sản phẩm...</span>
                </div>
            </template>

            <template x-if="!loading && product">
                <div class="grid md:grid-cols-2">
                    <div class="aspect-square bg-surface-alt border-b md:border-b-0 md:border-r border-ui-border relative flex items-center justify-center overflow-hidden">
                        <img :src="product.primary_image || '/images/placeholder.png'" :alt="product.name" class="size-full object-cover">
                    </div>

                    <div class="p-6 sm:p-8 flex flex-col justify-between space-y-6">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-accent font-bold" x-text="product.category?.name || 'Mộc An'"></div>
                            <h2 class="font-display text-2xl font-bold text-heading mt-1" x-text="product.name"></h2>
                            <div class="mt-3 flex items-baseline gap-3">
                                <span class="font-display text-2xl font-bold text-primary" x-text="selectedVariant ? selectedVariant.formatted_price : product.formatted_min_price"></span>
                            </div>
                            <p class="text-xs text-muted mt-3 line-clamp-3" x-text="product.description"></p>

                            <template x-if="product.variants && product.variants.length > 0">
                                <div class="mt-4">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-2">Phân loại / Kích thước</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="v in product.variants" :key="v.id">
                                            <button
                                                type="button"
                                                @click="selectVariant(v)"
                                                :class="selectedVariant && selectedVariant.id === v.id ? 'border-primary bg-primary/5 text-primary font-bold' : 'border-ui-border text-muted hover:border-heading'"
                                                class="px-3 py-1.5 rounded-xl border text-xs transition"
                                            >
                                                <span x-text="v.variant_name || (v.material + (v.color ? ' - ' + v.color : ''))"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div class="mt-4 flex items-center gap-2 text-xs">
                                <template x-if="selectedVariant && selectedVariant.stock > 0">
                                    <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                        <span class="size-2 rounded-full bg-emerald-500 inline-block"></span>
                                        Còn hàng (<span x-text="selectedVariant.stock"></span> sản phẩm)
                                    </span>
                                </template>
                                <template x-if="selectedVariant && selectedVariant.stock <= 0">
                                    <span class="text-rose-600 font-semibold flex items-center gap-1">
                                        <span class="size-2 rounded-full bg-rose-500 inline-block"></span>
                                        Hết hàng tạm thời
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3 pt-4 border-t border-ui-border">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="addToCart()"
                                    :disabled="submitting || !selectedVariant || selectedVariant.stock <= 0"
                                    class="flex-1 py-3 px-6 rounded-xl bg-primary text-primary-foreground font-bold text-sm shadow-sm hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition text-center"
                                >
                                    <span x-show="!submitting">Thêm vào giỏ hàng</span>
                                    <span x-show="submitting">Đang thêm...</span>
                                </button>
                                <a :href="'/san-pham/' + product.slug" class="px-4 py-3 rounded-xl border border-ui-border hover:bg-surface-alt text-heading font-semibold text-xs transition">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Global Toast Notifications -->
    <div
        x-data="toastManager"
        @show-toast.window="addToast($event.detail)"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                :class="{
                    'bg-emerald-800 text-white border-emerald-700': toast.type === 'success',
                    'bg-rose-800 text-white border-rose-700': toast.type === 'error',
                    'bg-surface text-heading border-ui-border shadow-lg': !toast.type || toast.type === 'info'
                }"
                class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl border text-sm shadow-xl font-medium"
            >
                <span x-text="toast.message"></span>
                <button @click="removeToast(toast.id)" class="opacity-70 hover:opacity-100 text-xs ml-auto">✕</button>
            </div>
        </template>
    </div>

    <!-- Back to Top Floating Button -->
    <div
        x-data="{ show: false }"
        @scroll.window="show = (window.pageYOffset > 400)"
        class="fixed bottom-6 left-6 z-40"
    >
        <button
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            type="button"
            aria-label="Về đầu trang"
            title="Về đầu trang"
            class="grid size-11 place-items-center rounded-full border border-ui-border bg-surface/90 text-heading shadow-lg backdrop-blur-sm transition hover:bg-primary hover:text-primary-foreground hover:scale-110 active:scale-95"
        >
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
        </button>
    </div>

    <!-- Mộc An AI Shopping Assistant Widget -->
    <x-ai-chat-widget />
</body>
</html>

