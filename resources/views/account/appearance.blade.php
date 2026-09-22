@extends('layouts.app')

@section('title', 'Cài đặt giao diện cá nhân | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div
        class="max-w-4xl mx-auto space-y-8"
        x-data="{
            theme: '{{ $settings['theme'] }}',
            font_scale: '{{ $settings['font_scale'] }}',
            density: '{{ $settings['density'] }}',
            reduced_motion: '{{ $settings['reduced_motion'] }}',

            applyLiveTheme(newTheme) {
                this.theme = newTheme;
                document.documentElement.dataset.theme = newTheme;
                localStorage.setItem('moc-an-theme', newTheme);
            },
            applyLiveFontScale(newScale) {
                this.font_scale = newScale;
                localStorage.setItem('moc-an-font-scale', newScale);
            },
            applyLiveDensity(newDensity) {
                this.density = newDensity;
                document.documentElement.dataset.density = newDensity;
                localStorage.setItem('moc-an-density', newDensity);
            },
            toggleReducedMotion() {
                this.reduced_motion = this.reduced_motion === '1' ? '0' : '1';
                localStorage.setItem('moc-an-reduced-motion', this.reduced_motion);
            }
        }"
    >
        <!-- Breadcrumb & Title -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="mb-2 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
                    <span>/</span>
                    <a href="{{ route('account.index') }}" class="hover:text-heading transition">Tài khoản</a>
                    <span>/</span>
                    <span class="text-heading font-medium" aria-current="page">Cài đặt giao diện</span>
                </nav>
                <h1 class="font-display text-2xl sm:text-3xl font-semibold text-heading">Cài đặt giao diện cá nhân</h1>
                <p class="mt-1 text-sm text-muted">Tùy chỉnh chủ đề màu sắc, kích cỡ chữ và trải nghiệm hiển thị phù hợp với thiết bị của bạn.</p>
            </div>

            <form method="POST" action="{{ route('account.appearance.reset') }}" onsubmit="return confirm('Bạn có muốn đặt lại toàn bộ giao diện về mặc định?');">
                @csrf
                <button
                    type="submit"
                    class="px-4 py-2.5 rounded-xl border border-ui-border bg-surface text-xs font-semibold text-muted hover:text-red-500 hover:border-red-300 transition cursor-pointer"
                >
                    Khôi phục mặc định
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-2.5">
                <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl bg-rose-500/10 border border-rose-500/20 p-4 text-xs font-medium text-rose-600 dark:text-rose-400">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Settings Form -->
        <form method="POST" action="{{ route('account.appearance.update') }}" class="space-y-6">
            @csrf

            <!-- Section 1: Theme Presets -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm space-y-4">
                <div>
                    <h2 class="font-display text-lg font-bold text-heading">1. Bảng màu chủ đề (Theme Presets)</h2>
                    <p class="text-xs text-muted mt-1">Chọn tông màu cảm hứng mang lại cảm giác thoải mái nhất cho đôi mắt của bạn.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2">
                    <!-- Wood -->
                    <button
                        type="button"
                        @click="applyLiveTheme('wood')"
                        class="p-4 rounded-2xl border text-left flex flex-col justify-between gap-3 transition cursor-pointer"
                        :class="theme === 'wood' ? 'border-primary bg-surface-alt ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div class="size-8 rounded-full shadow-sm" style="background-color: #7a4f35;"></div>
                        <div>
                            <div class="text-xs font-bold text-heading">Nâu gỗ (Wood)</div>
                            <div class="text-[10px] text-muted">Ấm cúng, mộc mạc</div>
                        </div>
                    </button>

                    <!-- Moss -->
                    <button
                        type="button"
                        @click="applyLiveTheme('moss')"
                        class="p-4 rounded-2xl border text-left flex flex-col justify-between gap-3 transition cursor-pointer"
                        :class="theme === 'moss' ? 'border-primary bg-surface-alt ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div class="size-8 rounded-full shadow-sm" style="background-color: #2b3b33;"></div>
                        <div>
                            <div class="text-xs font-bold text-heading">Xanh rêu (Moss)</div>
                            <div class="text-[10px] text-muted">Tĩnh lặng, thiên nhiên</div>
                        </div>
                    </button>

                    <!-- Cream -->
                    <button
                        type="button"
                        @click="applyLiveTheme('cream')"
                        class="p-4 rounded-2xl border text-left flex flex-col justify-between gap-3 transition cursor-pointer"
                        :class="theme === 'cream' ? 'border-primary bg-surface-alt ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div class="size-8 rounded-full shadow-sm border border-ui-border" style="background-color: #d8c7a8;"></div>
                        <div>
                            <div class="text-xs font-bold text-heading">Kem (Cream)</div>
                            <div class="text-[10px] text-muted">Thanh nhã, tinh khiết</div>
                        </div>
                    </button>

                    <!-- Blue -->
                    <button
                        type="button"
                        @click="applyLiveTheme('blue')"
                        class="p-4 rounded-2xl border text-left flex flex-col justify-between gap-3 transition cursor-pointer"
                        :class="theme === 'blue' ? 'border-primary bg-surface-alt ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div class="size-8 rounded-full shadow-sm" style="background-color: #365f78;"></div>
                        <div>
                            <div class="text-xs font-bold text-heading">Xanh dương (Blue)</div>
                            <div class="text-[10px] text-muted">Trầm tĩnh, hiện đại</div>
                        </div>
                    </button>

                    <!-- Black -->
                    <button
                        type="button"
                        @click="applyLiveTheme('black')"
                        class="p-4 rounded-2xl border text-left flex flex-col justify-between gap-3 transition cursor-pointer"
                        :class="theme === 'black' ? 'border-primary bg-surface-alt ring-2 ring-primary/20 scale-102' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div class="size-8 rounded-full shadow-sm" style="background-color: #202020;"></div>
                        <div>
                            <div class="text-xs font-bold text-heading">Đen (Black)</div>
                            <div class="text-[10px] text-muted">Tối giản, huyền bí</div>
                        </div>
                    </button>
                </div>
                <input type="hidden" name="theme" :value="theme">
            </div>

            <!-- Section 2: Font Scaling -->
            <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm space-y-4">
                <div>
                    <h2 class="font-display text-lg font-bold text-heading">2. Kích thước chữ hiển thị (Font Scaling)</h2>
                    <p class="text-xs text-muted mt-1">Điều chỉnh cỡ chữ toàn bộ trang để việc đọc mô tả sản phẩm được rõ ràng nhất.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <button
                        type="button"
                        @click="applyLiveFontScale('sm')"
                        class="p-4 rounded-2xl border text-left flex items-center justify-between transition cursor-pointer"
                        :class="font_scale === 'sm' ? 'border-primary bg-surface-alt ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div>
                            <div class="text-xs font-bold text-heading">Nhỏ gọn (Compact text)</div>
                            <div class="text-[11px] text-muted mt-0.5">Khoảng 90% cỡ chuẩn</div>
                        </div>
                        <span class="text-xs font-bold text-muted">Aa</span>
                    </button>

                    <button
                        type="button"
                        @click="applyLiveFontScale('base')"
                        class="p-4 rounded-2xl border text-left flex items-center justify-between transition cursor-pointer"
                        :class="font_scale === 'base' ? 'border-primary bg-surface-alt ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div>
                            <div class="text-xs font-bold text-heading">Tiêu chuẩn (Default)</div>
                            <div class="text-[11px] text-muted mt-0.5">Chuẩn giao diện Mộc An</div>
                        </div>
                        <span class="text-sm font-bold text-heading">Aa</span>
                    </button>

                    <button
                        type="button"
                        @click="applyLiveFontScale('lg')"
                        class="p-4 rounded-2xl border text-left flex items-center justify-between transition cursor-pointer"
                        :class="font_scale === 'lg' ? 'border-primary bg-surface-alt ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40'"
                    >
                        <div>
                            <div class="text-xs font-bold text-heading">Lớn dễ đọc (Large text)</div>
                            <div class="text-[11px] text-muted mt-0.5">Khoảng 115% cỡ chuẩn</div>
                        </div>
                        <span class="text-base font-bold text-primary">Aa</span>
                    </button>
                </div>
                <input type="hidden" name="font_scale" :value="font_scale">
            </div>

            <!-- Section 3: Density & Motion -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Density -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm space-y-4">
                    <div>
                        <h2 class="font-display text-base font-bold text-heading">3. Mật độ khoảng cách (Layout Density)</h2>
                        <p class="text-xs text-muted mt-1">Điều chỉnh độ rộng các padding và khoảng cách phân mục.</p>
                    </div>

                    <div class="space-y-2.5 pt-1">
                        <button
                            type="button"
                            @click="applyLiveDensity('comfortable')"
                            class="w-full p-3.5 rounded-xl border text-left flex items-center justify-between transition cursor-pointer"
                            :class="density === 'comfortable' ? 'border-primary bg-surface-alt ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40'"
                        >
                            <span class="text-xs font-semibold text-heading">Thoải mái (Comfortable)</span>
                            <span class="text-[10px] text-muted font-mono">Chuẩn Showroom</span>
                        </button>

                        <button
                            type="button"
                            @click="applyLiveDensity('compact')"
                            class="w-full p-3.5 rounded-xl border text-left flex items-center justify-between transition cursor-pointer"
                            :class="density === 'compact' ? 'border-primary bg-surface-alt ring-2 ring-primary/20' : 'border-ui-border hover:border-heading/40'"
                        >
                            <span class="text-xs font-semibold text-heading">Gọn gàng (Compact)</span>
                            <span class="text-[10px] text-muted font-mono">Xem nhiều hơn</span>
                        </button>
                    </div>
                    <input type="hidden" name="density" :value="density">
                </div>

                <!-- Reduced Motion -->
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-sm space-y-4">
                    <div>
                        <h2 class="font-display text-base font-bold text-heading">4. Giảm hiệu ứng chuyển động</h2>
                        <p class="text-xs text-muted mt-1">Hạn chế các hiệu ứng zoom, trượt mượt mà nếu bạn nhạy cảm với chuyển động.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-surface-alt border border-ui-border flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-heading block">Chế độ Reduced Motion</span>
                            <span class="text-[11px] text-muted">Tối ưu tốc độ & thân thiện thị giác</span>
                        </div>
                        <button
                            type="button"
                            @click="toggleReducedMotion()"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="reduced_motion === '1' ? 'bg-primary' : 'bg-stone-300 dark:bg-stone-700'"
                            role="switch"
                        >
                            <span
                                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="reduced_motion === '1' ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>
                    <input type="hidden" name="reduced_motion" :value="reduced_motion">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end gap-4">
                <a href="{{ route('account.index') }}" class="px-5 py-3 rounded-xl border border-ui-border bg-surface text-xs font-semibold text-heading hover:bg-surface-alt transition">
                    Quay lại tài khoản
                </a>
                <button
                    type="submit"
                    class="px-7 py-3 rounded-xl bg-primary text-primary-foreground text-xs font-bold shadow-lg shadow-primary/20 hover:opacity-95 transition cursor-pointer"
                >
                    Lưu cài đặt giao diện
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
