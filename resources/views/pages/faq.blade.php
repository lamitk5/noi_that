@extends('layouts.app')

@section('title', 'Câu hỏi thường gặp (FAQ) | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-16 bg-page">
    <div class="page-shell max-w-4xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Câu hỏi thường gặp</span>
        </nav>

        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Trung tâm hỗ trợ</span>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Câu hỏi thường gặp</h1>
            <p class="mt-3 text-sm text-muted">Tổng hợp giải đáp các thắc mắc phổ biến về sản phẩm gỗ, chính sách vận chuyển, bảo hành và thanh toán tại Mộc An.</p>
        </div>

        <div class="space-y-4" x-data="{ active: null }">
            @foreach ($faqs as $index => $faq)
                <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden transition">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between gap-4 p-5 sm:p-6 text-left font-semibold text-heading hover:text-accent transition"
                        @click="active = active === {{ $index }} ? null : {{ $index }}"
                        :aria-expanded="active === {{ $index }}"
                    >
                        <span class="text-sm sm:text-base">{{ $faq['question'] }}</span>
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5 shrink-0 text-muted transition-transform duration-300"
                            :class="{ 'rotate-180': active === {{ $index }} }"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div
                        x-show="active === {{ $index }}"
                        x-collapse
                        class="px-5 sm:px-6 pb-6 text-xs sm:text-sm text-body leading-relaxed border-t border-ui-border pt-4"
                    >
                        {{ $faq['answer'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 rounded-3xl border border-ui-border bg-surface-alt p-8 text-center">
            <h3 class="font-display text-lg font-semibold text-heading">Bạn vẫn còn thắc mắc khác?</h3>
            <p class="mt-1 text-xs text-muted">Đội ngũ chuyên viên tư vấn nội thất Mộc An luôn sẵn sàng hỗ trợ bạn.</p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('pages.contact') }}" class="rounded-xl bg-primary px-6 py-2.5 text-xs font-bold text-primary-foreground hover:opacity-95 transition">
                    Gửi yêu cầu hỗ trợ
                </a>
                <a href="tel:0901234567" class="rounded-xl border border-ui-border bg-surface px-6 py-2.5 text-xs font-bold text-heading hover:bg-surface-alt transition">
                    Hotline: 0901 234 567
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
