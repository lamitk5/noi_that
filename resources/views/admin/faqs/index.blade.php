@extends('layouts.admin')

@section('title', 'Quản lý FAQ | Admin Mộc An')
@section('header', 'Câu hỏi thường gặp (FAQ)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Danh sách câu hỏi thường gặp</h1>
            <p class="text-xs text-muted mt-0.5">Quản lý câu hỏi và giải đáp hỗ trợ khách hàng mua sắm.</p>
        </div>
        <a
            href="{{ route('admin.faqs.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-95 shadow-xs"
        >
            + Thêm câu hỏi mới
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter & Search -->
    <div class="rounded-3xl border border-ui-border bg-surface p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.faqs.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tìm câu hỏi hoặc câu trả lời..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading placeholder:text-muted focus:border-primary focus:outline-none"
                >
            </div>
            @if($categories->isNotEmpty())
                <div>
                    <select name="category" class="rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 cursor-pointer">
                Lọc
            </button>
            @if(request()->hasAny(['q', 'category']))
                <a href="{{ route('admin.faqs.index') }}" class="px-3 py-2 rounded-xl border border-ui-border text-xs text-muted hover:text-heading">
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
                        <th class="py-3 px-4 w-12 text-center">STT</th>
                        <th class="py-3 px-4">Danh mục</th>
                        <th class="py-3 px-4">Câu hỏi</th>
                        <th class="py-3 px-4 w-28 text-center">Trạng thái</th>
                        <th class="py-3 px-4 w-32 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-heading">
                    @forelse($faqs as $faq)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3.5 px-4 text-center text-muted font-mono">{{ $faq->sort_order }}</td>
                            <td class="py-3.5 px-4">
                                <span class="rounded bg-accent/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-accent">
                                    {{ $faq->category }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-medium">
                                <span class="block font-bold">{{ $faq->question }}</span>
                                <span class="text-muted line-clamp-1 mt-0.5">{{ $faq->answer }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($faq->is_active)
                                    <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">Hiển thị</span>
                                @else
                                    <span class="rounded-full bg-stone-500/10 border border-stone-500/20 px-2 py-0.5 text-[10px] font-bold text-muted">Ẩn</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="font-semibold text-primary hover:underline">Sửa</a>
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa câu hỏi này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-500 hover:underline cursor-pointer">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-muted">Chưa có câu hỏi thường gặp nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($faqs->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $faqs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
