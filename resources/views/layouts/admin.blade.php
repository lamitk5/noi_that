<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Quản trị Mộc An')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:300,400,500,600,700|playfair-display:500,600,700&display=swap" rel="stylesheet" />

    <script>
        (function () {
            const savedTheme = localStorage.getItem('moc-an-theme') || 'wood';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-page font-sans text-body antialiased flex flex-col">
    <div class="flex-1 flex min-h-screen">
        <!-- Admin Sidebar -->
        <aside class="w-64 shrink-0 border-r border-ui-border bg-surface flex flex-col justify-between hidden md:flex">
            <div>
                <!-- Brand / Logo -->
                <div class="h-16 flex items-center px-6 border-b border-ui-border">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <span class="size-8 rounded-lg bg-primary text-primary-foreground font-display font-bold text-sm grid place-items-center">M</span>
                        <div class="flex flex-col">
                            <span class="font-display font-bold text-base text-heading leading-none">Mộc An</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-accent mt-0.5">Hệ thống Quản trị</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5 text-xs font-semibold" aria-label="Menu quản trị">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>Bảng điều khiển</span>
                    </a>

                    <div class="pt-4 pb-1 px-3.5 text-[10px] font-bold uppercase tracking-widest text-muted">Quản lý cửa hàng</div>

                    <a
                        href="{{ route('admin.products.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.products.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                        <span>Sản phẩm</span>
                    </a>

                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.categories.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                        <span>Danh mục</span>
                    </a>

                    <a
                        href="{{ route('admin.inventory.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.inventory.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                        <span>Tồn kho & Cảnh báo</span>
                    </a>

                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.orders.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                        <span>Đơn hàng</span>
                    </a>

                    <a
                        href="{{ route('admin.vouchers.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.vouchers.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-3.493V10.5a2.25 2.25 0 0 1-2.25 2.25h-1.5a2.25 2.25 0 0 0-2.25 2.25v1.5a2.25 2.25 0 0 1-2.25 2.25H4.875A2.25 2.25 0 0 1 2.625 16.5v-9A2.25 2.25 0 0 1 4.875 5.25h12.75a2.25 2.25 0 0 1 2.25 2.25v.757Z"/></svg>
                        <span>Khuyến mãi & Mã giảm</span>
                    </a>

                    <div class="pt-4 pb-1 px-3.5 text-[10px] font-bold uppercase tracking-widest text-muted">Báo cáo & Phân tích</div>

                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.reports.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                        <span>Báo cáo doanh thu</span>
                    </a>

                    <div class="pt-4 pb-1 px-3.5 text-[10px] font-bold uppercase tracking-widest text-muted">Nội dung & Hỗ trợ</div>

                    <a
                        href="{{ route('admin.tickets.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tickets.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <span>Phiếu hỗ trợ</span>
                    </a>

                    <a
                        href="{{ route('admin.faqs.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.faqs.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.451 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                        <span>Hỏi đáp (FAQ)</span>
                    </a>

                    <a
                        href="{{ route('admin.posts.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.posts.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        <span>Tin tức / Bài viết</span>
                    </a>

                    <a
                        href="{{ route('admin.pages.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.pages.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <span>Trang tĩnh (CMS)</span>
                    </a>
                </nav>
            </div>

            <!-- Bottom Actions -->
            <div class="p-4 border-t border-ui-border space-y-2">
                <a
                    href="{{ route('home') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-muted hover:text-heading hover:bg-surface-alt transition"
                >
                    <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 0V21m0 0H2.36m0 0L12 3l9.64 8.349"/></svg>
                    <span>Về trang bán hàng</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-500 hover:bg-red-500/10 transition"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        <span>Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 border-b border-ui-border bg-surface px-6 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted md:hidden">Mộc An Admin</span>
                    <h2 class="text-sm font-bold text-heading hidden md:block">@yield('header-title', 'Bảng điều khiển quản trị')</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="size-8 rounded-full bg-primary text-primary-foreground text-xs font-bold grid place-items-center">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden sm:flex flex-col text-left">
                            <span class="text-xs font-bold text-heading leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] font-semibold text-accent uppercase">Quản trị viên</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 sm:p-8 lg:p-10 bg-page">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
