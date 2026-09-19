@extends('layouts.app')

@section('title', 'Liên hệ | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Kết nối cùng Mộc An</span>
            <h1 class="mt-2 font-display text-3xl font-semibold text-heading sm:text-4xl">Liên hệ với chúng tôi</h1>
            <p class="mt-3 text-sm text-muted max-w-xl mx-auto">
                Chúng tôi luôn sẵn sàng lắng nghe mọi ý kiến đóng góp, thắc mắc về sản phẩm hoặc nhu cầu đặt làm nội thất theo yêu cầu.
            </p>
        </div>

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <!-- Contact Info Column -->
            <div class="lg:col-span-5 space-y-6">
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs space-y-6">
                    <div>
                        <h2 class="font-display text-lg font-bold text-heading">Thông tin liên hệ</h2>
                        <p class="text-xs text-muted mt-1">Showroom & Xưởng sản xuất Mộc An Furniture</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="flex items-start gap-3">
                            <div class="size-8 rounded-xl bg-primary/10 text-primary grid place-items-center shrink-0 mt-0.5">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <span class="font-bold text-heading block">Địa chỉ showroom:</span>
                                <span class="text-muted leading-relaxed">Số 123 Đường Cầu Giấy, Phường Quan Hoa, Quận Cầu Giấy, Hà Nội</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="size-8 rounded-xl bg-primary/10 text-primary grid place-items-center shrink-0 mt-0.5">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <span class="font-bold text-heading block">Hotline tư vấn:</span>
                                <span class="text-muted">0912.345.678 (8:00 - 21:00 hàng ngày)</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="size-8 rounded-xl bg-primary/10 text-primary grid place-items-center shrink-0 mt-0.5">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <div>
                                <span class="font-bold text-heading block">Email hỗ trợ:</span>
                                <span class="text-muted">cskh@mocan.vn</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="size-8 rounded-xl bg-primary/10 text-primary grid place-items-center shrink-0 mt-0.5">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <span class="font-bold text-heading block">Giờ mở cửa:</span>
                                <span class="text-muted">Thứ 2 - Chủ Nhật: 8:30 - 21:30</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Column -->
            <div class="lg:col-span-7">
                <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
                    <h2 class="font-display text-lg font-bold text-heading mb-1">Gửi lời nhắn trực tuyến</h2>
                    <p class="text-xs text-muted mb-6">Chúng tôi sẽ phản hồi qua email hoặc số điện thoại trong vòng 24 giờ làm việc.</p>

                    @if(session('success'))
                        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('pages.contact.store') }}" class="space-y-4">
                        @csrf

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Họ và tên <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', auth()->user()?->name) }}"
                                    required
                                    placeholder="Nguyễn Văn A"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                                >
                                @error('name')
                                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                    Số điện thoại
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone', auth()->user()?->phone) }}"
                                    placeholder="0912345678"
                                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Địa chỉ Email <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                required
                                placeholder="example@email.com"
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                            @error('email')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Chủ đề liên hệ <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                value="{{ old('subject') }}"
                                required
                                placeholder="Ví dụ: Tư vấn thiết kế nội thất phòng khách..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                            >
                            @error('subject')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                                Lời nhắn <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                required
                                placeholder="Nhập nội dung bạn muốn gửi tới Mộc An..."
                                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
                        >
                            Gửi tin nhắn ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
