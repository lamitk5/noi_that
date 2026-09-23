<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mộc An - Nội thất hiện đại cho không gian sống Việt.">
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
                <a href="{{ route('orders.track') }}" class="nav-link {{ request()->routeIs('orders.track') ? 'is-active' : '' }}">Tra cứu đơn hàng</a>
                <a href="{{ route('home') }}#bo-suu-tap" class="nav-link">Bộ sưu tập</a>
                <a href="{{ route('home') }}#ve-chung-toi" class="nav-link">Về Mộc An</a>
            </nav>

            <div class="flex items-center gap-1 sm:gap-2">
                <button class="icon-button hidden sm:grid" type="button" aria-label="Tìm kiếm">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </button>
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
                                    <div class="px-2.5 py-1.5 mb-1 flex items-center gap-2 border-b border-ui-border pb-2">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="size-6 rounded-full object-cover">
                                        @else
                                            <div class="size-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">
                                                {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <span class="block text-xs font-bold text-heading truncate">{{ auth()->user()->name }}</span>
                                            @if(auth()->user()->provider)
                                                <span class="block text-[10px] text-muted capitalize">{{ auth()->user()->provider }}</span>
                                            @endif
                                        </div>
                                    </div>
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

                                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-surface-alt hover:text-heading transition-colors">
                                        <svg viewBox="0 0 24 24" class="size-4 text-muted" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                        <span>Sản phẩm yêu thích</span>
                                    </a>

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
                @auth
                    <a href="{{ route('wishlist.index') }}" class="icon-button relative" aria-label="Sản phẩm yêu thích" title="Yêu thích">
                        <svg viewBox="0 0 24 24" class="size-5 text-muted hover:text-rose-500 transition-colors" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                    </a>
                @endauth
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
                <a href="{{ route('home') }}#bo-suu-tap" class="mobile-nav-link">Bộ sưu tập</a>
                <a href="{{ route('home') }}#ve-chung-toi" class="mobile-nav-link">Về Mộc An</a>
                <a href="#lien-he" class="mobile-nav-link">Liên hệ</a>
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
                <h3 class="footer-title">Hỗ trợ</h3>
                <div class="mt-5 flex flex-col gap-3 text-sm text-white/65">
                    <a href="{{ route('pages.warranty') }}" class="hover:text-white transition-colors">Chính sách bảo hành</a>
                    <a href="{{ route('pages.return') }}" class="hover:text-white transition-colors">Đổi trả & hoàn tiền</a>
                    <a href="{{ route('pages.faq') }}" class="hover:text-white transition-colors">Câu hỏi thường gặp</a>
                    <a href="{{ route('orders.track') }}" class="hover:text-white transition-colors">Tra cứu đơn hàng</a>
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
                <p>© {{ date('Y') }} Mộc An. All rights reserved.</p>
                <p>Đồ án Phát triển hệ thống thương mại điện tử</p>
            </div>
        </div>
    </footer>

    <!-- Quick Support Floating Widget -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3" x-data="{ open: false }">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="min-w-[230px] space-y-2 rounded-3xl border border-ui-border bg-surface/95 p-4 shadow-2xl backdrop-blur-md"
            @click.outside="open = false"
            x-cloak
        >
            <div class="px-2 pt-1 text-[11px] font-bold uppercase tracking-wider text-muted">Hỗ trợ khách hàng</div>
            <a href="tel:0901234567" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-body transition hover:bg-surface-alt hover:text-heading">
                <span class="grid size-7 place-items-center rounded-full bg-emerald-500/10 text-sm text-emerald-600">📞</span>
                <span>Hotline: 0901 234 567</span>
            </a>
            <a href="{{ route('pages.faq') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-body transition hover:bg-surface-alt hover:text-heading">
                <span class="grid size-7 place-items-center rounded-full bg-primary/10 text-sm text-primary">❓</span>
                <span>Câu hỏi thường gặp</span>
            </a>
            <a href="{{ route('pages.contact') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-body transition hover:bg-surface-alt hover:text-heading">
                <span class="grid size-7 place-items-center rounded-full bg-amber-500/10 text-sm text-accent">💬</span>
                <span>Gửi yêu cầu tư vấn</span>
            </a>
        </div>
        <button
            type="button"
            @click="open = !open"
            class="grid h-14 w-14 cursor-pointer place-items-center rounded-full bg-primary text-primary-foreground shadow-xl shadow-primary/35 ring-4 ring-white/20 transition-all duration-300 hover:scale-105 hover:shadow-2xl active:scale-95 sm:h-16 sm:w-16"
            aria-label="Mở bảng hỗ trợ nhanh"
            title="Hỗ trợ Mộc An"
        >
            <svg viewBox="0 0 24 24" class="size-7 transition-transform duration-300 sm:size-8" :class="open ? 'rotate-90 scale-90' : ''" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.774-.75 4.966 4.966 0 0 1 1.01-2.738C4.162 16.035 3 14.137 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
            </svg>
        </button>
    </div>
</body>
</html>
