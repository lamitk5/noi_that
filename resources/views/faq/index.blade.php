@extends('layouts.app')

@section('title', 'Câu hỏi thường gặp (FAQ) | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Trung tâm trợ giúp</span>
            <h1 class="mt-2 font-display text-3xl font-semibold text-heading sm:text-4xl">Câu hỏi thường gặp</h1>
            <p class="mt-3 text-sm text-muted max-w-xl mx-auto">
                Giải đáp nhanh chóng các thắc mắc phổ biến về sản phẩm, chính sách đặt hàng, vận chuyển và bảo hành tại Mộc An.
            </p>

            <!-- Search Bar -->
            <form method="GET" action="{{ route('faq.index') }}" class="mt-6 max-w-md mx-auto flex gap-2">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Tìm kiếm câu hỏi hoặc từ khóa..."
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 pl-10 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary shadow-xs"
                    >
                    <svg viewBox="0 0 24 24" class="size-4 text-muted absolute left-3.5 top-3" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:opacity-95 transition cursor-pointer">
                    Tìm
                </button>
            </form>
        </div>

        <!-- Category Tabs -->
        @if($categories->isNotEmpty())
            <div class="flex flex-wrap items-center justify-center gap-2 mb-8">
                <a
                    href="{{ route('faq.index', array_filter(['q' => request('q')])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ !request('category') ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:border-primary' }}"
                >
                    Tất cả
                </a>
                @foreach($categories as $cat)
                    <a
                        href="{{ route('faq.index', array_filter(['category' => $cat, 'q' => request('q')])) }}"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request('category') === $cat ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:border-primary' }}"
                    >
                        {{ $cat }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- FAQ Accordion -->
        <div class="space-y-4" x-data="{ active: null }">
            @forelse($faqs as $faq)
                <div class="rounded-2xl border border-ui-border bg-surface overflow-hidden shadow-xs">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between p-5 text-left transition cursor-pointer hover:bg-surface-alt/40"
                        @click="active = (active === {{ $faq->id }} ? null : {{ $faq->id }})"
                    >
                        <div class="flex items-center gap-3">
                            <span class="rounded bg-accent/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-accent shrink-0">
                                {{ $faq->category }}
                            </span>
                            <span class="font-display font-semibold text-heading text-sm sm:text-base">
                                {{ $faq->question }}
                            </span>
                        </div>
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5 text-muted transition-transform shrink-0 ml-3"
                            :class="active === {{ $faq->id }} ? 'rotate-180 text-primary' : ''"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div
                        x-show="active === {{ $faq->id }}"
                        x-collapse
                        class="px-5 pb-5 pt-1 text-sm text-body leading-relaxed border-t border-ui-border/60 bg-surface-alt/20"
                    >
                        {!! nl2br(e($faq->answer)) !!}
                    </div>
                </div>
            @empty
                <div class="text-center py-12 rounded-3xl border border-dashed border-ui-border bg-surface p-8">
                    <p class="text-sm font-medium text-heading">Không tìm thấy câu hỏi phù hợp.</p>
                    <p class="text-xs text-muted mt-1">Vui lòng thử tìm với từ khóa khác hoặc liên hệ bộ phận hỗ trợ của Mộc An.</p>
                    <a href="{{ route('pages.contact') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-95">
                        Gửi câu hỏi hỗ trợ →
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Contact Support CTA -->
        <div class="mt-12 rounded-3xl border border-ui-border bg-gradient-to-br from-surface to-surface-alt p-6 sm:p-8 text-center">
            <h2 class="font-display text-lg font-bold text-heading">Vẫn chưa tìm thấy câu trả lời?</h2>
            <p class="text-xs text-muted mt-1 max-w-md mx-auto">Đội ngũ chuyên viên tư vấn của Mộc An luôn sẵn sàng hỗ trợ bạn mọi lúc.</p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('pages.contact') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-primary-foreground hover:opacity-95">
                    Gửi yêu cầu hỗ trợ
                </a>
                <a href="tel:0912345678" class="inline-flex items-center gap-2 rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs font-bold text-heading hover:border-primary">
                    Hotline: 0912.345.678
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
