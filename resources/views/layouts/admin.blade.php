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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                            <span class="font-display font-bold text-base text-heading leading-none">Quản trị Mộc An</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-accent mt-0.5">Bảng điều khiển</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5 text-xs font-semibold" aria-label="Menu quản trị">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ (request()->routeIs('admin.dashboard') || request()->routeIs('admin.analytics.*')) ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                        <span>Báo cáo & Phân tích</span>
                    </a>

                    <div class="pt-4 pb-1 px-3.5 text-[10px] font-bold uppercase tracking-widest text-muted">Quản lý cửa hàng</div>

                    <a
                        href="{{ route('admin.products.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.products.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                        <span>Quản lý Sản phẩm</span>
                    </a>

                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.categories.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                        <span>Quản lý Danh mục</span>
                    </a>

                    <a
                        href="{{ route('admin.products.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition text-body hover:bg-surface-alt hover:text-heading"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                        <span>Quản lý Kho hàng</span>
                    </a>

                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.orders.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                        <span>Quản lý Đơn hàng</span>
                    </a>

                    <a
                        href="{{ route('admin.coupons.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.coupons.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                        <span>Mã giảm giá</span>
                    </a>

                    <a
                        href="{{ route('admin.customers.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.customers.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        <span>Khách hàng</span>
                    </a>

                    <a
                        href="{{ route('admin.reviews.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.reviews.*') ? 'bg-primary text-primary-foreground shadow-xs' : 'text-body hover:bg-surface-alt hover:text-heading' }}"
                    >
                        <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                        <span>Đánh giá</span>
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
                    <h2 class="text-sm font-bold text-heading hidden md:block">{!! html_entity_decode(View::yieldContent('header-title', View::yieldContent('page_title', 'Bảng điều khiển quản trị'))) !!}</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="size-8 rounded-full bg-primary text-primary-foreground text-xs font-bold grid place-items-center">
                            {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden sm:flex flex-col text-left">
                            <span class="text-xs font-bold text-heading leading-tight">{{ auth()->user()?->name ?? 'Quản trị viên' }}</span>
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

    @include('partials.admin-toast')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if (session('success'))
                window.AdminToast?.success(@js(session('success')));
            @endif
            @if (session('error'))
                window.AdminToast?.error(@js(session('error')));
            @endif
            @if (isset($errors) && $errors->any())
                window.AdminToast?.error(@js($errors->first()));
            @endif
        });
    </script>
</body>
</html>
