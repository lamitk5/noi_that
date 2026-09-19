@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title) . ' | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="mb-6 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
            <span>/</span>
            <span class="text-heading font-medium" aria-current="page">{{ $page->title }}</span>
        </nav>

        <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-12 shadow-xs">
            <header class="border-b border-ui-border pb-6 mb-8">
                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Mộc An Furniture</span>
                <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">{{ $page->title }}</h1>
                @if($page->updated_at)
                    <p class="text-xs text-muted mt-2">Cập nhật lần cuối: {{ $page->updated_at->format('d/m/Y') }}</p>
                @endif
            </header>

            <article class="prose prose-stone dark:prose-invert max-w-none text-body leading-relaxed space-y-4 text-sm sm:text-base">
                {!! nl2br(e($page->content)) !!}
            </article>

            <!-- Support Footer -->
            <div class="mt-12 pt-6 border-t border-ui-border flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-muted">
                <span>Bạn cần thêm thông tin? Liên hệ chuyên viên hỗ trợ của chúng tôi.</span>
                <a href="{{ route('pages.contact') }}" class="font-bold text-primary hover:underline">
                    Gửi thắc mắc trực tiếp →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
