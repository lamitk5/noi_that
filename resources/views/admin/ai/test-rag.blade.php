@extends('layouts.admin')

@section('title', 'Kết quả kiểm thử RAG | Mộc An')
@section('header-title', 'Kết quả Kiểm thử RAG Tri thức')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.ai.index', ['tab' => 'knowledge']) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
            ← Quay lại Bảng điều khiển AI
        </a>
    </div>

    <!-- Query Box -->
    <div class="p-6 rounded-3xl bg-surface border border-ui-border shadow-xs space-y-4">
        <h2 class="text-base font-display font-semibold text-heading">Kiểm thử câu truy vấn:</h2>
        <form method="POST" action="{{ route('admin.ai.test-rag') }}" class="flex items-center gap-3">
            @csrf
            <input
                type="text"
                name="query"
                value="{{ $query }}"
                placeholder="Nhập câu truy vấn..."
                class="flex-1 rounded-2xl border border-ui-border bg-page px-4 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                required
            >
            <button
                type="submit"
                class="px-5 py-2.5 rounded-2xl bg-primary text-primary-foreground font-semibold text-xs hover:opacity-90 shadow-sm transition"
            >
                Chạy lại
            </button>
        </form>
    </div>

    <!-- Results List -->
    <div class="p-6 rounded-3xl bg-surface border border-ui-border shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-ui-border pb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted">
                Các khối tri thức trích xuất ({{ count($chunks) }} mẩu)
            </h3>
            <span class="text-[11px] text-muted">Sắp xếp theo độ liên quan giảm dần</span>
        </div>

        @forelse($chunks as $i => $chunk)
        <div class="p-4 rounded-2xl bg-surface-alt border border-ui-border space-y-2">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="grid size-6 place-items-center rounded-lg bg-primary text-primary-foreground text-xs font-bold">
                        #{{ $i + 1 }}
                    </span>
                    <span class="font-bold text-xs text-heading">{{ $chunk['title'] }}</span>
                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-primary/10 text-primary">
                        {{ $chunk['source'] }}
                    </span>
                </div>
                <span class="text-xs font-mono font-bold text-emerald-600">
                    Điểm khớp: {{ $chunk['score'] }}
                </span>
            </div>

            <p class="text-xs text-body leading-relaxed pl-8">
                {{ $chunk['content'] }}
            </p>
        </div>
        @empty
        <div class="py-8 text-center text-muted text-xs">
            Không tìm thấy thông tin nào phù hợp với câu hỏi "{{ $query }}".
        </div>
        @endforelse
    </div>
</div>
@endsection
