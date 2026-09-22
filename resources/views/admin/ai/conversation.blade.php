@extends('layouts.admin')

@section('title', 'Chi tiết cuộc trò chuyện AI | Mộc An')
@section('header-title', 'Chi tiết Hội thoại AI')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back button & Meta -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.ai.index', ['tab' => 'conversations']) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
            ← Quay lại danh sách hội thoại
        </a>
        <form
            action="{{ route('admin.ai.destroy-conversation', $conversation->uuid) }}"
            method="POST"
            onsubmit="return confirm('Bạn có chắc chắn muốn xóa cuộc trò chuyện này?');"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 rounded-xl border border-rose-300 text-rose-600 hover:bg-rose-50 text-xs font-semibold transition">
                Xóa cuộc trò chuyện này
            </button>
        </form>
    </div>

    <!-- Conversation Header Card -->
    <div class="p-6 rounded-3xl bg-surface border border-ui-border shadow-xs">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-display font-semibold text-heading">{{ $conversation->title }}</h2>
                <p class="font-mono text-xs text-muted mt-1">UUID: {{ $conversation->uuid }}</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                {{ $conversation->status }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-4 border-t border-ui-border text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-muted">Khách hàng:</span>
                <p class="font-semibold text-heading mt-0.5">
                    {{ $conversation->user ? $conversation->user->name : 'Khách vãng lai' }}
                </p>
                @if($conversation->user)
                <p class="text-[10px] text-muted">{{ $conversation->user->email }}</p>
                @endif
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-muted">Số tin nhắn:</span>
                <p class="font-semibold text-heading mt-0.5">{{ $conversation->messages->count() }} tin</p>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-muted">Bắt đầu:</span>
                <p class="font-semibold text-heading mt-0.5">{{ $conversation->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-muted">Cập nhật cuối:</span>
                <p class="font-semibold text-heading mt-0.5">{{ $conversation->last_message_at ? $conversation->last_message_at->format('d/m/Y H:i') : '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Messages Timeline -->
    <div class="p-6 rounded-3xl bg-surface border border-ui-border shadow-xs space-y-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-muted border-b border-ui-border pb-3">
            Toàn bộ diễn biến trao đổi
        </h3>

        <div class="space-y-4">
            @forelse($conversation->messages as $msg)
            <div class="p-4 rounded-2xl border text-xs leading-relaxed {{ $msg->role === 'user' ? 'bg-primary/5 border-primary/20 ml-6' : 'bg-surface-alt border-ui-border mr-6' }}">
                <div class="flex items-center justify-between text-[10px] text-muted mb-2">
                    <span class="font-bold uppercase tracking-wider {{ $msg->role === 'user' ? 'text-primary' : 'text-accent' }}">
                        {{ $msg->role === 'user' ? '👤 Khách hàng' : ($msg->role === 'assistant' ? '🤖 Trợ lý Mộc An' : '⚙️ System / Tool') }}
                    </span>
                    <span>{{ $msg->created_at->format('H:i:s d/m/Y') }}</span>
                </div>

                <div class="text-heading whitespace-pre-wrap">{{ $msg->content }}</div>

                <!-- Metadata / Tool calls / Sources -->
                @if(!empty($msg->metadata))
                <div class="mt-3 pt-2 border-t border-ui-border/60 text-[11px] text-muted space-y-1">
                    @if(!empty($msg->metadata['sources']))
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-bold text-[10px] uppercase">RAG Sources:</span>
                        @foreach($msg->metadata['sources'] as $src)
                        <span class="px-2 py-0.5 rounded bg-surface border border-ui-border text-primary text-[10px]">
                            {{ $src['title'] ?? '' }} (Score: {{ $src['relevance'] ?? 'N/A' }})
                        </span>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($msg->metadata['cards']))
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-bold text-[10px] uppercase">Rendered Cards:</span>
                        @foreach($msg->metadata['cards'] as $card)
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px]">
                            {{ $card['type'] ?? 'card' }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <p class="text-center text-muted text-xs py-8">Chưa có tin nhắn nào trong cuộc trò chuyện này.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
