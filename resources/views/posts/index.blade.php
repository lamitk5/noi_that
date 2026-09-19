@extends('layouts.app')

@section('title', 'Góc Cảm Hứng & Tin Tức | Mộc An')

@section('content')
<div class="min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Kiến thức & Phong cách sống</span>
            <h1 class="mt-2 font-display text-3xl font-semibold text-heading sm:text-4xl">Góc Cảm Hứng Mộc An</h1>
            <p class="mt-3 text-sm text-muted max-w-xl mx-auto">
                Chia sẻ kinh nghiệm bài trí không gian, bảo quản đồ gỗ tự nhiên và cập nhật xu hướng thiết kế nội thất hiện đại.
            </p>

            <!-- Search Bar -->
            <form method="GET" action="{{ route('posts.index') }}" class="mt-6 max-w-md mx-auto flex gap-2">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Tìm bài viết, mẹo bài trí..."
                        class="w-full rounded-xl border border-ui-border bg-surface px-4 py-2.5 pl-10 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary shadow-xs"
                    >
                    <svg viewBox="0 0 24 24" class="size-4 text-muted absolute left-3.5 top-3" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-sm font-semibold hover:opacity-95 transition cursor-pointer">
                    Tìm
                </button>
            </form>
        </div>

        <!-- Categories Filter -->
        @if($categories->isNotEmpty())
            <div class="flex flex-wrap items-center justify-center gap-2 mb-10">
                <a
                    href="{{ route('posts.index', array_filter(['q' => request('q')])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ !request('category') ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:border-primary' }}"
                >
                    Tất cả
                </a>
                @foreach($categories as $cat)
                    <a
                        href="{{ route('posts.index', array_filter(['category' => $cat->slug, 'q' => request('q')])) }}"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ request('category') === $cat->slug ? 'bg-primary text-primary-foreground' : 'bg-surface border border-ui-border text-heading hover:border-primary' }}"
                    >
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Articles Grid -->
        @if($posts->isEmpty())
            <div class="text-center py-16 rounded-3xl border border-dashed border-ui-border bg-surface p-8">
                <p class="text-sm font-medium text-heading">Chưa có bài viết nào trong danh mục này.</p>
                <p class="text-xs text-muted mt-1">Vui lòng quay lại sau để đón đọc những chia sẻ mới nhất từ Mộc An.</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($posts as $post)
                    <article class="rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs hover:shadow-md transition flex flex-col group">
                        <a href="{{ route('posts.show', $post->slug) }}" class="block aspect-16/10 overflow-hidden bg-surface-alt relative">
                            @if($post->featured_image)
                                <img
                                    src="{{ $post->featured_image }}"
                                    alt="{{ $post->title }}"
                                    class="size-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="size-full grid place-items-center text-muted bg-surface-alt">
                                    <svg viewBox="0 0 24 24" class="size-10" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                </div>
                            @endif
                            @if($post->category)
                                <span class="absolute top-3 left-3 rounded-lg bg-surface/90 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-accent border border-ui-border">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                        </a>

                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-[11px] text-muted mb-2">
                                    <span>{{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}</span>
                                    <span>·</span>
                                    <span>{{ $post->view_count }} lượt xem</span>
                                </div>
                                <h2 class="font-display text-lg font-bold text-heading group-hover:text-primary transition line-clamp-2">
                                    <a href="{{ route('posts.show', $post->slug) }}">
                                        {{ $post->title }}
                                    </a>
                                </h2>
                                @if($post->excerpt)
                                    <p class="text-xs text-muted mt-2 line-clamp-3 leading-relaxed">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif
                            </div>

                            <div class="pt-4 mt-4 border-t border-ui-border flex items-center justify-between text-xs">
                                <span class="text-muted font-medium">{{ $post->author?->name ?? 'Ban biên tập Mộc An' }}</span>
                                <a href="{{ route('posts.show', $post->slug) }}" class="font-bold text-primary group-hover:underline flex items-center gap-1">
                                    <span>Đọc tiếp</span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
