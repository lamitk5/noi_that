@extends('layouts.admin')

@section('title', 'Quản lý Trang tĩnh (CMS) | Admin Mộc An')
@section('header', 'Trang nội dung (CMS)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold font-display text-heading">Danh sách trang tĩnh</h1>
            <p class="text-xs text-muted mt-0.5">Quản lý các trang giới thiệu, chính sách mua hàng, bảo hành & đổi trả.</p>
        </div>
        <a
            href="{{ route('admin.pages.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-primary-foreground hover:opacity-95 shadow-xs"
        >
            + Tạo trang mới
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 text-xs text-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-3xl border border-ui-border bg-surface overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-alt border-b border-ui-border text-muted uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3 px-4">Tiêu đề trang</th>
                        <th class="py-3 px-4">Đường dẫn (Slug)</th>
                        <th class="py-3 px-4 text-center">Trạng thái</th>
                        <th class="py-3 px-4">Cập nhật lần cuối</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-heading">
                    @forelse($pages as $page)
                        <tr class="hover:bg-surface-alt/30 transition">
                            <td class="py-3.5 px-4 font-bold">
                                {{ $page->title }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-accent">
                                /{{ $page->slug }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($page->is_active)
                                    <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">Hiển thị</span>
                                @else
                                    <span class="rounded-full bg-stone-500/10 border border-stone-500/20 px-2 py-0.5 text-[10px] font-bold text-muted">Ẩn</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-muted text-[11px]">
                                {{ $page->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="font-semibold text-muted hover:text-heading">Xem</a>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold text-primary hover:underline">Sửa</a>
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa trang này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-500 hover:underline cursor-pointer">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-muted">Chưa có trang tĩnh tùy chỉnh nào trong cơ sở dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pages->hasPages())
            <div class="p-4 border-t border-ui-border">
                {{ $pages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
