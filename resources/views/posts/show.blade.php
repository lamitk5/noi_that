@extends('layouts.app')

@section('title', $post->title . ' | Cảm Hứng Mộc An')

@section('seo')
@php
    $postImage = $post->featured_image ?: 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80';
    $postDescription = $post->excerpt ?: str($post->content)->limit(160);

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'image' => [$postImage],
        'description' => $postDescription,
        'datePublished' => ($post->published_at ?? $post->created_at)->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $post->author?->name ?? 'Ban biên tập Mộc An'
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Mộc An',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/logo.png')
            ]
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => route('posts.show', $post->slug)
        ]
    ];
@endphp
<x-seo-meta
    :title="$post->title . ' | Cảm Hứng Mộc An'"
    :description="$postDescription"
    :image="$postImage"
    :url="route('posts.show', $post->slug)"
    type="article"
    :schema="$articleSchema"
/>
@endsection

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="mb-6 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('posts.index') }}" class="hover:text-heading transition">Góc cảm hứng</a>
            <span>/</span>
            @if($post->category)
                <a href="{{ route('posts.index', ['category' => $post->category->slug]) }}" class="hover:text-heading transition">{{ $post->category->name }}</a>
                <span>/</span>
            @endif
            <span class="text-heading font-medium truncate max-w-xs" aria-current="page">{{ $post->title }}</span>
        </nav>

        <article class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-12 shadow-xs">
            <header class="border-b border-ui-border pb-6 mb-8">
                @if($post->category)
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">{{ $post->category->name }}</span>
                @endif
                <h1 class="mt-2 font-display text-2xl sm:text-4xl font-bold text-heading leading-snug">{{ $post->title }}</h1>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted mt-4">
                    <span>Tác giả: <strong class="text-heading">{{ $post->author?->name ?? 'Ban biên tập Mộc An' }}</strong></span>
                    <span>·</span>
                    <span>Ngày đăng: {{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}</span>
                    <span>·</span>
                    <span>{{ $post->view_count }} lượt xem</span>
                </div>
            </header>

            @if($post->featured_image)
                <div class="mb-8 rounded-2xl overflow-hidden aspect-16/9 bg-surface-alt border border-ui-border">
                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="size-full object-cover">
                </div>
            @endif

            @if($post->excerpt)
                <div class="p-4 rounded-2xl bg-surface-alt/60 border border-ui-border text-sm italic text-body leading-relaxed mb-8">
                    {{ $post->excerpt }}
                </div>
            @endif

            <div class="prose prose-stone dark:prose-invert max-w-none text-body leading-relaxed space-y-4 text-sm sm:text-base">
                {!! nl2br(e($post->content)) !!}
            </div>

            <!-- Share & Return CTA -->
            <div class="mt-12 pt-6 border-t border-ui-border flex items-center justify-between">
                <a href="{{ route('posts.index') }}" class="text-xs font-semibold text-muted hover:text-heading flex items-center gap-1">
                    <span aria-hidden="true">←</span>
                    <span>Quay lại danh sách bài viết</span>
                </a>
            </div>
        </article>

        <!-- Related Posts -->
        @if($relatedPosts->isNotEmpty())
            <div class="mt-16">
                <div class="mb-8">
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Đón đọc thêm</span>
                    <h2 class="mt-1 font-display text-2xl font-bold text-heading">Bài viết liên quan</h2>
                </div>

                <div class="grid sm:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $related)
                        <div class="rounded-2xl border border-ui-border bg-surface p-4 shadow-xs flex flex-col justify-between group">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-accent">{{ $related->category?->name }}</span>
                                <h3 class="font-display font-bold text-sm text-heading mt-1 group-hover:text-primary transition line-clamp-2">
                                    <a href="{{ route('posts.show', $related->slug) }}">{{ $related->title }}</a>
                                </h3>
                            </div>
                            <span class="text-[11px] text-muted mt-3 block">{{ $related->published_at?->format('d/m/Y') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
