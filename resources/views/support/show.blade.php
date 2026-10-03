@extends('layouts.app')

@section('title', $req->typeLabel().' '.$req->code.' | Mộc An')

@section('content')
@php
    $steps = [
        'pending' => 'Đã gửi',
        'reviewing' => 'Đang xử lý',
        $req->type === 'return' ? 'approved' : 'completed' => $req->type === 'return' ? 'Chấp nhận' : 'Đã giải quyết',
    ];
    if ($req->type === 'return') {
        $steps['completed'] = 'Hoàn tất';
    }
    $order = array_keys($steps);
    $current = array_search($req->status, $order, true);
    $rejected = $req->status === 'rejected';
@endphp
<div class="page-shell py-8 max-w-3xl">
    <div class="flex items-center gap-2 text-xs text-muted mb-2">
        <a href="{{ route('support.index') }}" class="hover:text-primary transition">Hoàn hàng & khiếu nại</a>
        <span>/</span>
        <span class="text-heading font-semibold">{{ $req->code }}</span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-heading">{{ $req->typeLabel() }} {{ $req->code }}</h1>
            <p class="text-xs text-muted mt-0.5">
                Gửi lúc {{ $req->created_at->format('d/m/Y H:i') }}
                @if($req->order)
                    · Đơn <a href="{{ route('orders.show', $req->order->order_code) }}" class="text-primary font-semibold hover:underline">#{{ $req->order->order_code }}</a>
                @endif
            </p>
        </div>
        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $req->statusTone() }}">{{ $req->statusLabel() }}</span>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 text-sm rounded-xl">{{ session('success') }}</div>
    @endif

    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 mb-6">
        @if($rejected)
            <div class="flex items-center gap-3 text-rose-700">
                <div class="size-10 rounded-full bg-rose-500/15 flex items-center justify-center font-bold">✕</div>
                <div>
                    <p class="text-sm font-bold">Yêu cầu không được chấp nhận</p>
                    <p class="text-xs text-rose-600">Xem phản hồi của Mộc An bên dưới. Bạn có thể gửi khiếu nại nếu chưa đồng ý.</p>
                </div>
            </div>
        @else
            <div class="grid gap-2 text-center" style="grid-template-columns: repeat({{ count($steps) }}, minmax(0, 1fr))">
                @foreach($steps as $key => $label)
                    @php $done = $current !== false && $loop->index <= $current; @endphp
                    <div class="flex flex-col items-center">
                        <div class="size-9 rounded-full flex items-center justify-center text-sm font-bold mb-1.5 {{ $done ? 'bg-primary text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border' }}">{{ $done ? '✓' : $loop->iteration }}</div>
                        <span class="text-xs font-semibold {{ $done ? 'text-heading' : 'text-muted' }}">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($req->admin_note || $req->refund_amount)
        <div class="mb-6 rounded-2xl border border-primary/30 bg-primary/5 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-primary mb-1.5">Phản hồi từ Mộc An</p>
            @if($req->admin_note)
                <p class="text-sm text-body whitespace-pre-line">{{ $req->admin_note }}</p>
            @endif
            @if($req->refund_amount)
                <p class="mt-2 text-sm text-heading">Số tiền hoàn: <strong>{{ number_format($req->refund_amount, 0, ',', '.') }}đ</strong></p>
            @endif
            @if($req->resolved_at)
                <p class="mt-2 text-[11px] text-muted">Cập nhật {{ $req->resolved_at->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    @endif

    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 space-y-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-muted mb-1">{{ $req->type === 'return' ? 'Lý do hoàn hàng' : 'Vấn đề' }}</p>
            <p class="text-sm font-semibold text-heading">{{ $req->reasonLabel() }}</p>
            @if($req->subject)
                <p class="text-sm text-body mt-0.5">{{ $req->subject }}</p>
            @endif
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-muted mb-1">Mô tả</p>
            <p class="text-sm text-body whitespace-pre-line">{{ $req->description }}</p>
        </div>

        @if($req->items)
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Sản phẩm hoàn</p>
                <div class="divide-y divide-ui-border rounded-xl border border-ui-border">
                    @foreach($req->items as $item)
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-semibold text-heading truncate">{{ $item['product_name'] }}</p>
                                @if(!empty($item['variant_info']))
                                    <p class="text-[11px] text-muted">{{ $item['variant_info'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs text-muted">{{ number_format($item['price'], 0, ',', '.') }}đ × {{ $item['quantity'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-right text-xs text-muted">Giá trị sản phẩm hoàn: <strong class="text-heading">{{ number_format($req->itemsTotal(), 0, ',', '.') }}đ</strong></p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-muted mb-1">Hình thức xử lý</p>
                    <p class="text-sm text-heading">{{ $req->resolutionLabel() }}</p>
                </div>
                @if($req->refund_account)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-muted mb-1">Tài khoản nhận hoàn</p>
                        <p class="text-sm text-heading">{{ $req->refund_account }}</p>
                    </div>
                @endif
            </div>
        @endif

        @if($req->photos)
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Ảnh đính kèm</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($req->photoUrls() as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Ảnh đính kèm" class="size-24 rounded-xl object-cover border border-ui-border hover:opacity-90 transition"></a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="mt-6 flex items-center justify-between">
        <a href="{{ route('support.index') }}" class="text-xs font-semibold text-muted hover:text-heading">← Danh sách yêu cầu</a>
        @if($req->order && $req->type === 'return' && $rejected)
            <a href="{{ route('support.create', ['type' => 'complaint', 'order' => $req->order->order_code]) }}" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt text-heading transition">Gửi khiếu nại</a>
        @endif
    </div>
</div>
@endsection
