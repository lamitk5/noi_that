@extends('layouts.admin')

@section('title', 'Quản lý Bài viết | Admin Mộc An')
@section('header', 'Bài viết & Tin tức')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Danh sách bài viết</h1>
            <p class="text-xs text-muted mt-0.5">Quản lý các bài viết cẩm nang, xu hướng nội thất và tin tức Mộc An.</p>
        </div>
        <a
            href="{{ route('admin.posts.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-95 shadow-xs"
        >
            + Viết bài mới
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="rounded-3xl border border-ui-border bg-surface p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.posts.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm theo tiêu đề bài viết..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                >
            </div>
            @if($categories->isNotEmpty())
                <div>
                    <select name="category_id" class="rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none">
                        <option value="">Tất cả chuyên mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 cursor-pointer">
                Lọc
            </button>
            @if(request()->hasAny(['q', 'category_id']))
                <a href="{{ route('admin.posts.index') }}" class="px-3 py-2 rounded-xl border border-ui-border text-xs text-muted hover:text-heading">
                    Đặt lại
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt border-b border-ui-border text-muted uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3 px-4 w-16">Ảnh</th>
                        <th class="py-3 px-4">Tiêu đề bài viết</th>
                        <th class="py-3 px-4">Chuyên mục</th>
                        <th class="py-3 px-4">Lượt xem</th>
                        <th class="py-3 px-4 text-center">Trạng thái</th>
                        <th class="py-3 px-4">Ngày đăng</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-heading">
                    @forelse($posts as $post)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3.5 px-4">
                                <div class="size-12 rounded-xl overflow-hidden bg-surface-alt border border-ui-border shrink-0">
                                    @if($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="size-full object-cover">
                                    @else
                                        <div class="size-full grid place-items-center text-muted">
                                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-medium">
                                <a href="{{ route('admin.posts.edit', $post) }}" class="font-bold hover:text-primary transition line-clamp-1">
                                    {{ $post->title }}
                                </a>
                                <span class="font-mono text-muted text-[11px]">/{{ $post->slug }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($post->category)
                                    <span class="rounded bg-accent/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-accent">
                                        {{ $post->category->name }}
                                    </span>
                                @else
                                    <span class="text-muted">Chung</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium">
                                {{ number_format($post->view_count) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($post->is_published)
                                    <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">Xuất bản</span>
                                @else
                                    <span class="rounded-full bg-stone-500/10 border border-stone-500/20 px-2 py-0.5 text-[10px] font-bold text-muted">Bản nháp</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-muted text-[11px]">
                                {{ $post->published_at?->format('d/m/Y') ?? $post->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a href="{{ route('posts.show', $post->slug) }}" target="_blank" class="font-semibold text-muted hover:text-heading">Xem</a>
                                <a href="{{ route('admin.posts.edit', $post) }}" class="font-semibold text-primary hover:underline">Sửa</a>
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-500 hover:underline cursor-pointer">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-muted">Chưa có bài viết nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
