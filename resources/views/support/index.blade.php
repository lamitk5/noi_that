@extends('layouts.app')

@section('title', 'Hoàn hàng & khiếu nại | Mộc An')

@section('content')
<div class="page-shell py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-heading">Hoàn hàng & khiếu nại</h1>
            <p class="text-xs text-muted mt-1">Theo dõi các yêu cầu hỗ trợ sau mua hàng. Mộc An phản hồi trong vòng 24 giờ làm việc.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('orders.index', ['status' => 'completed']) }}" class="text-xs font-bold px-4 py-2.5 rounded-xl bg-primary text-primary-foreground hover:opacity-90 transition shadow-sm">
                Yêu cầu hoàn hàng
            </a>
            <a href="{{ route('support.create', ['type' => 'complaint']) }}" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt text-heading transition">
                Gửi khiếu nại
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 text-sm rounded-xl">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach(['all' => 'Tất cả'] + \App\Models\SupportRequest::TYPES as $key => $label)
            @php $active = ($currentType ?? 'all') === $key || ($key === 'all' && !array_key_exists((string) $currentType, \App\Models\SupportRequest::TYPES)); @endphp
            <a href="{{ route('support.index', $key === 'all' ? [] : ['type' => $key]) }}"
               class="rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ $active ? 'bg-primary text-primary-foreground border-primary' : 'bg-surface border-ui-border text-body hover:bg-surface-alt' }}">
                {{ $label }} <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($requests as $req)
            <a href="{{ route('support.show', $req) }}" class="block bg-surface rounded-2xl border border-ui-border shadow-sm p-5 hover:border-primary transition">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $req->type === 'return' ? 'bg-primary/10 text-primary' : 'bg-rose-500/10 text-rose-600' }}">{{ $req->typeLabel() }}</span>
                            <span class="font-mono text-sm font-bold text-heading">{{ $req->code }}</span>
                            @if($req->order)
                                <span class="text-xs text-muted">· Đơn #{{ $req->order->order_code }}</span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm text-body">{{ $req->subject ?: $req->reasonLabel() }}</p>
                        <p class="mt-1 text-[11px] text-muted">Gửi lúc {{ $req->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="self-start sm:self-center shrink-0 rounded-full border px-3 py-1 text-[11px] font-bold {{ $req->statusTone() }}">{{ $req->statusLabel() }}</span>
                </div>
            </a>
        @empty
            <div class="bg-surface rounded-2xl border border-dashed border-ui-border p-10 text-center">
                <p class="text-sm font-semibold text-heading">Bạn chưa có yêu cầu nào</p>
                <p class="text-xs text-muted mt-1">Để hoàn hàng, mở đơn đã giao thành công và chọn “Yêu cầu hoàn hàng”.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $requests->links() }}</div>
</div>
@endsection
