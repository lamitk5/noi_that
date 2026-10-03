@extends('layouts.admin')

@section('title', $req->typeLabel().' '.$req->code.' | Mộc An Admin')
@section('page_title', $req->typeLabel().': '.$req->code)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $req->type === 'return' ? 'bg-primary/10 text-primary' : 'bg-rose-500/10 text-rose-600' }}">{{ $req->typeLabel() }}</span>
                    <span class="font-mono text-sm font-bold text-heading">{{ $req->code }}</span>
                    <span class="text-xs text-muted">· {{ $req->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <span class="rounded-full border px-3 py-1 text-[11px] font-bold {{ $req->statusTone() }}">{{ $req->statusLabel() }}</span>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Khách hàng</dt>
                    <dd class="mt-1 font-semibold text-heading">{{ $req->user?->name ?? 'Khách' }}</dd>
                    <dd class="text-xs text-muted">{{ $req->user?->email }} @if($req->user?->phone) · {{ $req->user->phone }} @endif</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Đơn hàng</dt>
                    @if($req->order)
                        <dd class="mt-1"><a href="{{ route('admin.orders.show', $req->order) }}" class="font-semibold text-primary hover:underline">#{{ $req->order->order_code }}</a></dd>
                        <dd class="text-xs text-muted">{{ $req->order->order_status_label }} · {{ number_format($req->order->total_price, 0, ',', '.') }}đ · {{ strtoupper($req->order->payment_method) }} ({{ $req->order->payment_status_label }})</dd>
                    @else
                        <dd class="mt-1 text-muted">Không gắn với đơn hàng</dd>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $req->type === 'return' ? 'Lý do hoàn hàng' : 'Vấn đề' }}</dt>
                    <dd class="mt-1 font-semibold text-heading">{{ $req->reasonLabel() }}</dd>
                    @if($req->subject)
                        <dd class="text-body">{{ $req->subject }}</dd>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Mô tả của khách</dt>
                    <dd class="mt-1 text-body whitespace-pre-line rounded-xl bg-page border border-ui-border p-3">{{ $req->description }}</dd>
                </div>
            </dl>
        </div>

        @if($req->items)
            <div class="rounded-2xl border border-ui-border bg-surface shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-ui-border flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-heading">Sản phẩm yêu cầu hoàn</h3>
                    <span class="text-xs text-muted">{{ $req->resolutionLabel() }}</span>
                </div>
                <div class="divide-y divide-ui-border">
                    @foreach($req->items as $item)
                        <div class="px-6 py-3 flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-semibold text-heading truncate">{{ $item['product_name'] }}</p>
                                @if(!empty($item['variant_info']))
                                    <p class="text-[11px] text-muted">{{ $item['variant_info'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs text-muted">{{ number_format($item['price'], 0, ',', '.') }}đ × {{ $item['quantity'] }} = <strong class="text-heading">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</strong></span>
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-3 bg-surface-alt/60 border-t border-ui-border text-xs flex flex-wrap justify-between gap-2">
                    <span class="text-muted">
                        @if($req->refund_account) Tài khoản nhận hoàn: <strong class="text-heading">{{ $req->refund_account }}</strong> @endif
                    </span>
                    <span class="text-muted">Tổng giá trị: <strong class="text-heading">{{ number_format($req->itemsTotal(), 0, ',', '.') }}đ</strong></span>
                </div>
            </div>
        @endif

        @if($req->photos)
            <div class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs">
                <h3 class="text-sm font-bold text-heading mb-3">Ảnh khách gửi</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach($req->photoUrls() as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Ảnh đính kèm" class="size-28 rounded-xl object-cover border border-ui-border hover:opacity-90 transition"></a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <form method="POST" action="{{ route('admin.support.update', $req) }}" class="rounded-2xl border border-ui-border bg-surface p-6 shadow-xs space-y-4">
            @csrf
            @method('PUT')
            <h3 class="text-sm font-bold text-heading">Xử lý yêu cầu</h3>

            @if($errors->any())
                <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 p-3 text-xs text-rose-700">{{ $errors->first() }}</div>
            @endif

            <label class="block">
                <span class="text-xs font-semibold text-heading">Trạng thái</span>
                <select name="status" class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">
                    @foreach($req->allowedStatuses() as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $req->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            @if($req->type === 'return')
                <label class="block">
                    <span class="text-xs font-semibold text-heading">Số tiền hoàn (đ)</span>
                    <input type="number" name="refund_amount" min="0" step="1000"
                           value="{{ old('refund_amount', $req->refund_amount !== null ? (int) $req->refund_amount : ($req->resolution === 'refund' ? (int) $req->itemsTotal() : '')) }}"
                           class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">
                </label>
            @endif

            <label class="block">
                <span class="text-xs font-semibold text-heading">Phản hồi cho khách</span>
                <textarea name="admin_note" rows="5" maxlength="2000" placeholder="Nội dung khách sẽ thấy trong trang theo dõi yêu cầu (bắt buộc khi từ chối)"
                          class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">{{ old('admin_note', $req->admin_note) }}</textarea>
            </label>

            @if($req->type === 'return' && $req->resolution === 'refund')
                <p class="text-[11px] text-muted">
                    @if($req->restocked_at)
                        Đã cộng lại tồn kho lúc {{ $req->restocked_at->format('d/m/Y H:i') }}.
                    @else
                        Khi chuyển sang “Hoàn tất”, số lượng sản phẩm hoàn sẽ tự cộng lại vào tồn kho (một lần).
                    @endif
                </p>
            @endif

            <button type="submit" class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-primary-foreground hover:opacity-95 transition">Lưu cập nhật</button>

            @if($req->handler)
                <p class="text-[11px] text-muted">Người xử lý gần nhất: {{ $req->handler->name }} · {{ $req->updated_at->format('d/m/Y H:i') }}</p>
            @endif
        </form>

        <a href="{{ route('admin.support.index') }}" class="block text-center text-xs font-semibold text-muted hover:text-heading">← Về danh sách yêu cầu</a>
    </div>
</div>
@endsection
