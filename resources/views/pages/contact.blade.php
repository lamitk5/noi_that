@extends('layouts.app')

@section('title', 'Liên hệ & Showroom | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-16 bg-page">
    <div class="page-shell max-w-5xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Liên hệ</span>
        </nav>

        <div class="mb-12 text-center max-w-2xl mx-auto">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Kết nối cùng chúng tôi</span>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Showroom & Trụ sở Mộc An</h1>
            <p class="mt-3 text-sm text-muted">Kính mời quý khách ghé thăm không gian trải nghiệm thực tế hoặc để lại lời nhắn để được đội ngũ thiết kế tư vấn chi tiết.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Contact Info (5 cols) -->
            <div class="lg:col-span-5 rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs space-y-6">
                <div>
                    <h2 class="font-display text-lg font-semibold text-heading">Thông tin liên hệ</h2>
                    <p class="mt-1 text-xs text-muted">Mở cửa từ 8:30 - 21:00 (Tất cả các ngày trong tuần)</p>
                </div>

                <div class="space-y-4 text-xs text-body">
                    <div class="flex items-start gap-3">
                        <div class="size-8 rounded-lg bg-primary/10 text-primary grid place-items-center shrink-0">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        </div>
                        <div>
                            <strong class="text-heading block">Showroom Hà Nội:</strong>
                            <p class="text-muted mt-0.5">Số 18 Phố Triệu Việt Vương, Quận Hai Bà Trưng, TP. Hà Nội</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="size-8 rounded-lg bg-primary/10 text-primary grid place-items-center shrink-0">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                        </div>
                        <div>
                            <strong class="text-heading block">Hotline tư vấn:</strong>
                            <a href="tel:0901234567" class="text-accent font-semibold hover:underline">0901 234 567</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="size-8 rounded-lg bg-primary/10 text-primary grid place-items-center shrink-0">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        </div>
                        <div>
                            <strong class="text-heading block">Email hỗ trợ:</strong>
                            <a href="mailto:cskh@mocan.vn" class="text-accent font-semibold hover:underline">cskh@mocan.vn</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Message Form (7 cols) -->
            <div class="lg:col-span-7 rounded-3xl border border-ui-border bg-surface p-6 sm:p-8 shadow-xs">
                <h2 class="font-display text-lg font-semibold text-heading mb-1">Gửi lời nhắn trực tiếp</h2>
                <p class="text-xs text-muted mb-6">Mộc An sẽ phản hồi tư vấn trong vòng 30 phút làm việc.</p>

                <form action="#" method="POST" class="space-y-4" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã gửi lời nhắn! Chuyên viên Mộc An sẽ liên hệ với bạn trong thời gian sớm nhất.'); this.reset();">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-heading mb-1.5">Họ và tên *</label>
                            <input type="text" required placeholder="Nguyễn Văn A" class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-heading mb-1.5">Số điện thoại *</label>
                            <input type="tel" required placeholder="0901 234 567" class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-heading mb-1.5">Nội dung cần tư vấn *</label>
                        <textarea rows="4" required placeholder="Mô tả nhu cầu thiết kế, kích thước phòng khách hoặc món đồ bạn quan tâm..." class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"></textarea>
                    </div>

                    <button type="submit" class="rounded-xl bg-primary px-7 py-3 text-xs font-bold text-primary-foreground hover:opacity-95 transition">
                        Gửi thông tin tư vấn
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
