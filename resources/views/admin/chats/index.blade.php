@extends('layouts.admin')

@section('title', 'Trò chuyện khách hàng')
@section('page_title', 'Trò chuyện khách hàng')

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-surface rounded-xl border border-ui-border p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Đang mở</p>
                <h3 class="text-2xl font-extrabold text-heading mt-1">{{ $stats['open'] }}</h3>
            </div>
            <div class="size-10 rounded-full bg-emerald-100 text-emerald-700 grid place-items-center">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
            </div>
        </div>
        <div class="bg-surface rounded-xl border border-ui-border p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Đã đóng</p>
                <h3 class="text-2xl font-extrabold text-heading mt-1">{{ $stats['closed'] }}</h3>
            </div>
            <div class="size-10 rounded-full bg-gray-100 text-gray-600 grid place-items-center">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>
        <div class="bg-surface rounded-xl border border-ui-border p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Chưa đọc</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1">{{ $stats['unread'] }}</h3>
            </div>
            <div class="size-10 rounded-full bg-rose-100 text-rose-700 grid place-items-center">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-surface rounded-xl border border-ui-border p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <form action="{{ route('admin.chats.index') }}" method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên, email, sđt..." class="text-xs border border-ui-border rounded-lg p-2 outline-none w-56 bg-page text-heading">
                <select name="status" class="text-xs border border-ui-border rounded-lg p-2 outline-none bg-page text-heading">
                    <option value="">Tất cả trạng thái</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Đang chờ</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Đã đóng</option>
                </select>
                <button type="submit" class="bg-primary text-primary-foreground text-xs px-3 py-2 rounded-lg hover:opacity-90">
                    Lọc
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-page uppercase text-muted font-semibold border-b border-ui-border">
                    <tr>
                        <th class="py-3 px-3">Khách hàng</th>
                        <th class="py-3 px-3">Tin nhắn cuối</th>
                        <th class="py-3 px-3">Nhân viên</th>
                        <th class="py-3 px-3">Trạng thái</th>
                        <th class="py-3 px-3">Thời gian</th>
                        <th class="py-3 px-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ui-border text-body">
                    @forelse($chats as $chat)
                        <tr class="hover:bg-page">
                            <td class="py-3 px-3">
                                <div class="font-semibold text-heading">{{ $chat->user?->name ?? 'N/A' }}</div>
                                <span class="text-muted text-[11px]">{{ $chat->user?->email }}</span>
                            </td>
                            <td class="py-3 px-3 max-w-xs">
                                <span class="line-clamp-1 text-muted">{{ $chat->latestMessage?->message ?? $chat->messages->first()?->message ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-3">{{ $chat->staff?->name ?? '—' }}</td>
                            <td class="py-3 px-3">
                                @if($chat->status === 'open')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Đang chờ</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600">Đã đọc</span>
                                @endif
                                @if($chat->unread_count > 0)
                                    <span class="ml-1 px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-bold">{{ $chat->unread_count }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-muted whitespace-nowrap">{{ $chat->last_message_at?->format('d/m H:i') ?? $chat->created_at->format('d/m H:i') }}</td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('admin.chats.show', $chat) }}" class="text-primary font-bold hover:underline">Mở</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-muted">Chưa có cuộc trò chuyện nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $chats->links() }}</div>
    </div>
</div>
@endsection
