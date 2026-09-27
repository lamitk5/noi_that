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
    <div class="bg-primary px-4 py-2.5 text-center text-xs font-semibold tracking-[0.12em] text-primary-foreground sm:text-sm">
        MIỄN PHÍ GIAO HÀNG TOÀN QUỐC CHO ĐƠN TỪ 5.000.000₫
    </div>

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
                <a href="{{ route('home') }}#bo-suu-tap" class="nav-link">Bộ sưu tập</a>
                <a href="{{ route('home') }}#ve-chung-toi" class="nav-link">Về Mộc An</a>
                <a href="{{ route('home') }}#lien-he" class="nav-link">Liên hệ</a>
            </nav>

            <div class="flex items-center gap-1 sm:gap-2">
                <a
                    href="{{ route('orders.index') }}"
                    class="icon-button {{ request()->routeIs('orders.*') ? 'bg-surface-alt text-primary font-bold' : '' }}"
                    aria-label="Lịch sử đơn hàng"
                    title="Lịch sử đơn hàng"
                >
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
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
                                    @else
                                        <span class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-muted/60 cursor-not-allowed">
                                            <svg viewBox="0 0 24 24" class="size-4 text-muted/40" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                            <span>Lịch sử đơn hàng</span>
                                        </span>
                                    @endif

                                    @if ((auth()->user()->isAdmin() || auth()->user()->isStaff()) && Route::has('admin.dashboard'))
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
                    <span class="absolute right-0.5 top-0.5 grid size-4 place-items-center rounded-full bg-accent text-[9px] font-bold text-accent-foreground">{{ $cartCount ?? 0 }}</span>
                </a>
                <button id="menu-toggle" class="icon-button lg:hidden" type="button" aria-label="Mở menu" aria-expanded="false">
                    <svg id="menu-open-icon" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                    <svg id="menu-close-icon" viewBox="0 0 24 24" class="hidden size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" class="hidden border-t border-ui-border bg-header px-5 py-4 lg:hidden" aria-label="Điều hướng di động">
            <div class="mx-auto flex max-w-7xl flex-col">
                <a href="{{ route('home') }}" class="mobile-nav-link">Trang chủ</a>
                <a href="{{ route('products.index') }}" class="mobile-nav-link">Sản phẩm</a>
                <a href="{{ route('cart.index') }}" class="mobile-nav-link flex items-center justify-between">
                    <span>Giỏ hàng</span>
                    <span class="rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-accent-foreground">{{ $cartCount ?? 0 }}</span>
                </a>
                <a href="{{ route('orders.index') }}" class="mobile-nav-link">Lịch sử đơn hàng</a>
                <a href="{{ route('home') }}#bo-suu-tap" class="mobile-nav-link">Bộ sưu tập</a>
                <a href="{{ route('home') }}#ve-chung-toi" class="mobile-nav-link">Về Mộc An</a>
                <a href="{{ route('home') }}#lien-he" class="mobile-nav-link">Liên hệ</a>
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- Chân trang cửa hàng (Footer) --}}
    <footer class="border-t border-ui-border bg-surface text-body mt-12 sm:mt-16">
        <div class="page-shell py-12 lg:py-16">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Mục 1: Thông tin -->
                <div>
                    <h3 class="font-display text-base font-bold uppercase tracking-wider text-heading">Thông tin</h3>
                    <ul class="mt-4 space-y-2.5 text-xs text-muted">
                        <li><a href="{{ route('home') }}#ve-chung-toi" class="hover:text-primary transition-colors">Về thương hiệu Mộc An</a></li>
                        <li><a href="{{ route('products.index') }}" class="hover:text-primary transition-colors">Bộ sưu tập nội thất tự nhiên</a></li>
                        <li><a href="{{ route('home') }}#bo-suu-tap" class="hover:text-primary transition-colors">Hệ thống showroom & nhà xưởng</a></li>
                        <li><a href="{{ route('home') }}#ve-chung-toi" class="hover:text-primary transition-colors">Tiêu chuẩn vật liệu bền vững</a></li>
                        <li><a href="{{ route('home') }}#ve-chung-toi" class="hover:text-primary transition-colors">Điều khoản dịch vụ & bảo mật</a></li>
                    </ul>
                </div>

                <!-- Mục 2: Hỗ trợ -->
                <div>
                    <h3 class="font-display text-base font-bold uppercase tracking-wider text-heading">Hỗ trợ</h3>
                    <ul class="mt-4 space-y-2.5 text-xs text-muted">
                        <li><a href="{{ route('home') }}#lien-he" class="hover:text-primary transition-colors">Câu hỏi thường gặp (FAQ)</a></li>
                        <li><a href="{{ route('home') }}#lien-he" class="hover:text-primary transition-colors">Hướng dẫn đặt hàng & thanh toán</a></li>
                        <li><a href="{{ route('home') }}#lien-he" class="hover:text-primary transition-colors">Chính sách bảo hành 5 năm</a></li>
                        <li><a href="{{ route('home') }}#lien-he" class="hover:text-primary transition-colors">Chính sách giao nhận & lắp đặt</a></li>
                        <li><a href="{{ route('home') }}#lien-he" class="hover:text-primary transition-colors">Chính sách đổi trả & hoàn tiền</a></li>
                    </ul>
                </div>

                <!-- Mục 3: Liên hệ hotline -->
                <div>
                    <h3 class="font-display text-base font-bold uppercase tracking-wider text-heading">Liên hệ hotline</h3>
                    <div class="mt-4 space-y-3.5 text-xs text-muted">
                        <div class="flex items-start gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                            </span>
                            <div>
                                <a href="tel:19006868" class="font-bold text-heading text-sm hover:text-primary transition-colors">1900 6868 - 0912 345 678</a>
                                <p class="text-[11px] text-muted mt-0.5">Hotline tư vấn & hỗ trợ đặt hàng (Miễn cước)</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            </span>
                            <div>
                                <a href="mailto:hotro@mocan.vn" class="font-medium text-heading hover:text-primary transition-colors">hotro@mocan.vn</a>
                                <p class="text-[11px] text-muted mt-0.5">Email phản hồi trong 24 giờ</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <div>
                                <p class="font-medium text-heading">25 Hoàng Đạo Thúy, Cầu Giấy, Hà Nội</p>
                                <p class="text-[11px] text-muted mt-0.5">Giờ mở cửa: 08:00 - 21:30 hàng ngày</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mục 4: Bản quyền thuộc về Nhom6 -->
            <div class="mt-12 pt-6 border-t border-ui-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted">
                <p>© 2026 Bản quyền thuộc về Nhom6.</p>
                <div class="flex items-center gap-6">
                    <span>Nội thất hiện đại cho tổ ấm Việt</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Floating chat với nhân viên --}}
    <div class="fixed bottom-5 right-5 z-50" x-data="chatWidget" data-auth="{{ auth()->check() && !auth()->user()->isAdmin() && !auth()->user()->isStaff() ? '1' : '0' }}">
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="mb-3 w-[min(360px,calc(100vw-2.5rem))] overflow-hidden rounded-2xl border border-ui-border bg-surface shadow-2xl ring-1 ring-black/5 flex flex-col h-[min(480px,70vh)]"
        >
            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-primary text-primary-foreground">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-8 place-items-center rounded-full bg-white/15">
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold leading-tight">Tư vấn trực tuyến</p>
                        <p class="text-[11px] opacity-80">Mộc An • Phản hồi trong vài phút</p>
                    </div>
                </div>
                <button type="button" @click="open = false; stopPolling()" aria-label="Đóng khung chat" class="rounded-full p-1.5 hover:bg-white/15 transition">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>

            @guest
                <div class="flex-1 flex flex-col items-center justify-center gap-3 p-6 text-center">
                    <svg viewBox="0 0 24 24" class="size-10 text-muted" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    <p class="text-sm text-body">Đăng nhập để bắt đầu trò chuyện với nhân viên Mộc An.</p>
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}" class="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:opacity-90 transition">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="rounded-lg border border-ui-border px-4 py-2 text-xs font-semibold text-heading hover:bg-surface-alt transition">Đăng ký</a>
                    </div>
                </div>
            @else
                @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                    <div class="flex-1 flex flex-col items-center justify-center gap-3 p-6 text-center">
                        <p class="text-sm text-body">Bạn đang dùng tài khoản quản trị/nhân viên.</p>
                        <a href="{{ route('admin.chats.index') }}" class="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:opacity-90 transition">Mở trang quản lý trò chuyện</a>
                    </div>
                @else
                    <div x-ref="list" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-page/40">
                        <div x-show="loading" class="text-center text-xs text-muted py-4">Đang kết nối...</div>
                        <div x-show="!loading && messages.length === 0" class="text-center text-xs text-muted py-4 leading-relaxed">
                            Xin chào 👋 Hãy để lại câu hỏi, nhân viên Mộc An sẽ hỗ trợ bạn ngay.
                        </div>
                        <template x-for="m in messages" :key="m.id">
                            <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed break-words shadow-sm"
                                         :class="m.role === 'user'
                                            ? 'bg-primary text-primary-foreground rounded-tr-sm'
                                            : 'bg-surface text-body border border-ui-border rounded-tl-sm'"
                                         x-text="m.content"></div>
                                    <div class="mt-0.5 px-1 text-[10px] text-muted" :class="m.role === 'user' ? 'text-right' : 'text-left'">
                                        <span x-text="m.sender"></span> • <span x-text="m.created_at"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-ui-border p-3">
                        <p x-show="error" x-cloak class="mb-2 rounded-lg bg-rose-50 px-3 py-2 text-[11px] text-rose-600" x-text="error"></p>
                        <form @submit.prevent="send" class="flex items-end gap-2">
                            <textarea
                                x-model="draft"
                                @keydown.enter.exact.prevent="send"
                                rows="1"
                                placeholder="Nhập tin nhắn..."
                                class="max-h-24 flex-1 resize-none rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="sending || !draft.trim()"
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground transition hover:opacity-90 disabled:opacity-40"
                                aria-label="Gửi tin nhắn"
                            >
                                <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/></svg>
                            </button>
                        </form>
                    </div>
                @endif
            @endguest
        </div>

        <button
            type="button"
            @click="toggle()"
            class="group relative grid size-14 place-items-center rounded-full bg-primary text-primary-foreground shadow-xl ring-1 ring-black/10 transition hover:scale-105"
            aria-label="Mở khung chat"
        >
            <svg x-show="!open" viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
            <svg x-show="open" x-cloak viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
            <span class="absolute -top-1 -right-1 size-3 rounded-full bg-accent ring-2 ring-page"></span>
        </button>
    </div>

</body>
</html>
