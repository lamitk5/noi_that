@extends('layouts.admin')

@section('title', 'Đánh giá & Phản hồi | Mộc An Admin')
@section('page_title', 'Đánh giá & Phản hồi')

@section('content')
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Tổng đánh giá</span>
            <div class="mt-2 text-2xl font-bold font-display text-heading">{{ $stats['total'] }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Đã duyệt</span>
            <div class="mt-2 text-2xl font-bold font-display text-emerald-600">{{ $stats['approved'] }}</div>
        </div>
        <div class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Chờ duyệt / đã ẩn</span>
            <div class="mt-2 text-2xl font-bold font-display text-amber-600">{{ $stats['pending'] }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-ui-border bg-surface p-5 sm:p-6 shadow-xs">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Tìm sản phẩm / nội dung..."
                class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none focus:ring-1 focus:ring-primary w-full sm:w-56"
            >
            <select name="status" class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none">
                <option value="">Tất cả</option>
                <option value="approved" @selected(request('status')==='approved')>Đã duyệt</option>
                <option value="pending" @selected(request('status')==='pending')>Chờ / đã ẩn</option>
            </select>
            <select name="rating" class="rounded-xl border border-ui-border bg-page px-3 py-2 text-xs outline-none">
                <option value="">Mọi số sao</option>
                @foreach([5,4,3,2,1] as $r)
                    <option value="{{ $r }}" @selected(request('rating')==(string)$r)>{{ $r }} sao</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground hover:opacity-95 transition">Lọc</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($reviews as $review)
            <article class="rounded-2xl border border-ui-border bg-surface p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="font-bold text-sm text-heading">{{ $review->product?->name ?? 'Sản phẩm đã xóa' }}</span>
                            <span class="text-[10px] text-muted">•</span>
                            <span class="text-xs text-muted">{{ $review->user?->name ?? 'Khách' }}</span>
                            <div class="flex items-center text-amber-500">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="size-3.5 {{ $i <= $review->rating ? 'fill-amber-400' : 'fill-stone-200' }}" viewBox="0 0 20 20"><path d="M9.05 2.9c.3-.9 1.6-.9 1.9 0l1.1 3.3c.1.3.4.6.8.6h3.5c.9 0 1.3 1.2.6 1.8l-2.8 2c-.3.2-.4.6-.3.9l1.1 3.3c.3.9-.8 1.7-1.5 1.1L11 16.9c-.3-.2-.7-.2-1 0l-2.9 2c-.7.5-1.8-.2-1.5-1.1l1.1-3.3c.1-.3 0-.7-.3-.9l-2.8-2c-.8-.6-.4-1.8.6-1.8h3.5c.3 0 .6-.3.7-.6L9.05 2.9z"/></svg>
                                @endfor
                            </div>
                            @if($review->is_approved)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-600">Đã duyệt</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-600">Đã ẩn</span>
                            @endif
                        </div>
                        <p class="text-sm text-body leading-relaxed">{{ $review->comment }}</p>
                        <p class="mt-2 text-[11px] text-muted">{{ $review->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex sm:flex-col gap-2 shrink-0">
                        @unless($review->is_approved)
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full rounded-xl bg-emerald-500/10 px-3 py-2 text-[11px] font-bold text-emerald-700 hover:bg-emerald-500/20 transition">Duyệt</button>
                            </form>
                        @endunless
                        @if($review->is_approved)
                            <form action="{{ route('admin.reviews.hide', $review) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full rounded-xl bg-amber-500/10 px-3 py-2 text-[11px] font-bold text-amber-700 hover:bg-amber-500/20 transition">Ẩn</button>
                            </form>
                        @endif
                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl bg-rose-500/10 px-3 py-2 text-[11px] font-bold text-rose-700 hover:bg-rose-500/20 transition">Xóa</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-ui-border bg-surface p-10 text-center text-sm text-muted shadow-xs">
                Chưa có đánh giá nào.
            </div>
        @endforelse
    </div>

    <div>{{ $reviews->links() }}</div>
</div>
@endsection
