@extends('layouts.admin')

@section('title', 'Trò chuyện #' . $chat->id)
@section('page_title', 'Trò chuyện với ' . ($chat->user?->name ?? 'khách hàng'))

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Chat Window -->
    <div class="lg:col-span-3 bg-surface rounded-xl border border-ui-border flex flex-col h-[70vh]">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-ui-border">
            <div class="flex items-center gap-3">
                <div class="size-9 rounded-full bg-primary text-primary-foreground grid place-items-center font-bold text-sm">
                    {{ mb_strtoupper(mb_substr($chat->user?->name ?? 'K', 0, 1)) }}
                </div>
                <div>
                    <p class="font-bold text-sm text-heading">{{ $chat->user?->name ?? 'Khách hàng' }}</p>
                    <p class="text-[11px] text-muted">{{ $chat->user?->email }} {{ $chat->user?->phone ? '• ' . $chat->user?->phone : '' }}</p>
                </div>
                @if($chat->status === 'open')
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Đang chờ</span>
                @else
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600">Đã đóng</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($chat->status === 'open')
                    <form action="{{ route('admin.chats.close', $chat) }}" method="POST" onsubmit="return confirm('Đóng cuộc trò chuyện này?')">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold px-3 py-1.5 rounded-lg border border-ui-border text-muted hover:text-heading transition">Đóng</button>
                    </form>
                @else
                    <form action="{{ route('admin.chats.reopen', $chat) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">Mở lại</button>
                    </form>
                @endif
                <a href="{{ route('admin.chats.index') }}" class="text-[11px] font-semibold px-3 py-1.5 rounded-lg border border-ui-border text-muted hover:text-heading transition">← Danh sách</a>
            </div>
        </div>

        <!-- Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-4 bg-page/40">
            @forelse($messages as $msg)
                <div class="flex {{ $msg['is_mine'] ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] space-y-1">
                        <div class="text-[10px] text-muted px-1 flex {{ $msg['is_mine'] ? 'justify-end' : 'justify-start' }} gap-1.5">
                            <span class="font-semibold">{{ $msg['sender_name'] }}</span>
                            <span>•</span>
                            <span>{{ $msg['created_at'] }}</span>
                        </div>
                        <div class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-sm
                            {{ $msg['is_mine']
                                ? 'bg-primary text-primary-foreground rounded-tr-none'
                                : 'bg-surface text-body border border-ui-border rounded-tl-none' }}">
                            {!! nl2br(e($msg['message'])) !!}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted text-sm py-10">Chưa có tin nhắn nào.</p>
            @endforelse
        </div>

        <!-- Reply Form -->
        @if($chat->status === 'open')
            <div class="p-4 border-t border-ui-border space-y-3">
                @if($chat->staff_id && $chat->staff_id !== auth()->id())
                    <div id="collision-alert" class="flex items-center justify-between gap-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs">
                        <div class="flex items-center gap-2">
                            <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span>
                                <strong id="assigned-staff-name">{{ $chat->staff?->name ?? 'Nhân viên khác' }}</strong> đang phụ trách khách hàng này. Vui lòng kiểm tra để tránh trả lời trùng!
                            </span>
                        </div>
                        <form action="{{ route('admin.chats.claim', $chat) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition">
                                Tiếp nhận thay
                            </button>
                        </form>
                    </div>
                @endif

                <form action="{{ route('admin.chats.reply', $chat) }}" method="POST" class="flex items-end gap-2">
                    @csrf
                    <textarea name="message" rows="2" required placeholder="Nhập phản hồi..." class="flex-1 resize-none rounded-xl border border-ui-border bg-page px-3.5 py-2.5 text-sm text-heading placeholder:text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                    <button type="submit" class="shrink-0 h-10 px-5 rounded-xl bg-primary text-primary-foreground font-semibold text-sm hover:opacity-90 transition">Gửi</button>
                </form>
            </div>
        @else
            <div class="p-4 border-t border-ui-border text-center text-xs text-muted">
                Cuộc trò chuyện đã đóng. Mở lại để tiếp tục phản hồi.
            </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-4">
        <div class="bg-surface rounded-xl border border-ui-border p-5">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-3">Thông tin khách hàng</h3>
            <div class="space-y-2 text-sm">
                <div><span class="text-muted text-xs">Họ tên:</span><p class="font-semibold text-heading">{{ $chat->user?->name ?? '—' }}</p></div>
                <div><span class="text-muted text-xs">Email:</span><p class="text-heading break-all">{{ $chat->user?->email ?? '—' }}</p></div>
                <div><span class="text-muted text-xs">SĐT:</span><p class="text-heading">{{ $chat->user?->phone ?? '—' }}</p></div>
                <div><span class="text-muted text-xs">Tạo lúc:</span><p class="text-heading">{{ $chat->created_at->format('d/m/Y H:i') }}</p></div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-ui-border p-5">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-3">Nhân viên tiếp nhận</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="size-8 rounded-full bg-primary/10 text-primary grid place-items-center font-bold text-xs shrink-0">
                        {{ $chat->staff ? mb_strtoupper(mb_substr($chat->staff->name, 0, 1)) : '?' }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-xs text-heading truncate">
                            {{ $chat->staff?->name ?? 'Chưa có người tiếp nhận' }}
                        </p>
                        <p class="text-[11px] text-muted">
                            @if($chat->staff_id === auth()->id())
                                <span class="text-emerald-600 font-medium">Bạn đang phụ trách</span>
                            @elseif($chat->staff)
                                <span>Đang xử lý</span>
                            @else
                                <span>Tự động nhận khi trả lời</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($chat->staff_id !== auth()->id() && $chat->status === 'open')
                    <form action="{{ route('admin.chats.claim', $chat) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-xs font-semibold px-3 py-2 rounded-lg bg-surface-alt border border-ui-border text-heading hover:bg-primary hover:text-primary-foreground transition">
                            {{ $chat->staff_id ? 'Chuyển quyền tiếp nhận cho tôi' : 'Tiếp nhận cuộc trò chuyện' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-ui-border p-5">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-3">Thống kê</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-muted">Tổng tin nhắn:</span><span class="font-bold text-heading">{{ $messages->count() }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Trạng thái:</span><span class="font-semibold">{{ $chat->status_label }}</span></div>
            </div>
        </div>
    </div>
</div>

<script>
    const el = document.getElementById('chat-messages');
    if (el) el.scrollTop = el.scrollHeight;

    // Tự động đồng bộ tin nhắn để phát hiện ngay khi có nhân viên khác hoặc khách nhắn tin
    let lastCount = {{ $messages->count() }};
    const pollInterval = setInterval(async () => {
        try {
            const res = await fetch("{{ route('admin.chats.show', $chat) }}", {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && data.messages && data.messages.length > lastCount) {
                // Có tin nhắn mới từ đồng nghiệp hoặc khách hàng -> tải lại để đồng bộ tức thì
                window.location.reload();
            }
        } catch (e) {}
    }, 4000);
</script>
@endsection
