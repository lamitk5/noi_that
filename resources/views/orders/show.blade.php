@extends('layouts.app')

@section('title', 'Chi Tiết Đơn Hàng #' . $order->order_code . ' | Mộc An')

@section('content')
@include('partials.order-status-watch')
<div class="page-shell py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-2">
                <a href="{{ route('orders.index') }}" class="hover:text-primary transition">Lịch sử đơn hàng</a>
                <span>/</span>
                <span class="text-heading font-semibold">#{{ $order->order_code }}</span>
            </div>
            <h1 class="text-2xl font-bold text-heading">Chi Tiết Đơn Hàng #{{ $order->order_code }}</h1>
            <p class="text-xs text-muted mt-0.5">Đặt ngày {{ $order->created_at->format('d/m/Y') }} lúc {{ $order->created_at->format('H:i') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-heading bg-surface hover:bg-surface-alt px-3.5 py-2 rounded-lg border border-ui-border transition shadow-sm">
                ← Quay lại danh sách
            </a>
            @if($order->order_status === 'pending')
                <form action="{{ route('orders.cancel', $order->order_code) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 px-3.5 py-2 rounded-lg border border-rose-300 dark:border-rose-800 transition">
                        Hủy đơn hàng
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 text-sm rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Status Timeline -->
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 mb-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-muted mb-6">Trạng thái đơn hàng</h2>

        @if(in_array($order->order_status, ['cancelled', 'canceled']))
            <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-700 dark:text-rose-400 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400 font-bold flex-shrink-0">✕</div>
                <div>
                    <h4 class="text-sm font-bold">Đơn hàng này đã bị hủy</h4>
                    @if($order->note)
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">{{ $order->note }}</p>
                    @endif
                </div>
            </div>
        @else
            @php
                $step = match($order->order_status) {
                    'pending' => 1,
                    'confirmed' => 2,
                    'shipping' => 3,
                    'completed' => 4,
                    default => 1,
                };
            @endphp
            <div class="grid grid-cols-4 gap-2 text-center relative">
                <!-- Step 1 -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $step >= 1 ? 'bg-primary text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border' }}">
                        ✓
                    </div>
                    <span class="text-xs font-semibold {{ $step >= 1 ? 'text-heading' : 'text-muted' }}">Đặt hàng</span>
                    <span class="text-[10px] text-muted mt-0.5">{{ $order->created_at->format('d/m H:i') }}</span>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $step >= 2 ? 'bg-primary text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border' }}">
                        {{ $step >= 2 ? '✓' : '2' }}
                    </div>
                    <span class="text-xs font-semibold {{ $step >= 2 ? 'text-heading' : 'text-muted' }}">Đã xác nhận</span>
                    <span class="text-[10px] text-muted mt-0.5">{{ $step >= 2 ? 'Đang chuẩn bị hàng' : 'Chờ duyệt' }}</span>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $step >= 3 ? 'bg-primary text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border' }}">
                        {{ $step >= 3 ? '✓' : '3' }}
                    </div>
                    <span class="text-xs font-semibold {{ $step >= 3 ? 'text-heading' : 'text-muted' }}">Đang giao hàng</span>
                    <span class="text-[10px] text-muted mt-0.5">{{ $step >= 3 ? 'Đang giao đến bạn' : 'Chờ giao hàng' }}</span>
                </div>

                <!-- Step 4 -->
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm mb-2 {{ $step >= 4 ? 'bg-emerald-600 text-white' : 'bg-surface-alt text-muted border border-ui-border' }}">
                        {{ $step >= 4 ? '✓' : '4' }}
                    </div>
                    <span class="text-xs font-semibold {{ $step >= 4 ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted' }}">Đã hoàn thành</span>
                    <span class="text-[10px] text-muted mt-0.5">{{ $step >= 4 ? 'Giao thành công' : 'Chờ hoàn tất' }}</span>
                </div>
            </div>
        @endif
    </div>

    @php
        $supportRequests = $order->supportRequests()->latest()->get();
        $returnDeadline = $order->returnDeadline();
        $canReturn = $order->canRequestReturn();
    @endphp
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted">Hỗ trợ sau mua hàng</h2>
                <p class="text-xs text-body mt-1.5">
                    @if($canReturn)
                        Bạn có thể yêu cầu hoàn hàng đến hết ngày <strong class="text-heading">{{ $returnDeadline->format('d/m/Y') }}</strong> ({{ \App\Models\SupportRequest::RETURN_WINDOW_DAYS }} ngày kể từ khi nhận hàng).
                    @elseif($returnDeadline && now()->gt($returnDeadline))
                        Đơn hàng đã quá thời hạn {{ \App\Models\SupportRequest::RETURN_WINDOW_DAYS }} ngày hoàn hàng. Nếu có vấn đề, hãy gửi khiếu nại để được hỗ trợ.
                    @elseif($order->order_status === \App\Models\Order::STATUS_COMPLETED)
                        Đơn hàng đang có yêu cầu hoàn hàng được xử lý.
                    @else
                        Yêu cầu hoàn hàng khả dụng sau khi đơn được giao thành công. Bạn có thể gửi khiếu nại bất cứ lúc nào.
                    @endif
                    <a href="{{ route('pages.return') }}" class="text-primary font-semibold hover:underline">Xem chính sách đổi trả</a>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($canReturn)
                    <a href="{{ route('support.create', ['type' => 'return', 'order' => $order->order_code]) }}" class="text-xs font-bold px-4 py-2.5 rounded-xl bg-primary text-primary-foreground hover:opacity-90 transition shadow-sm">
                        Yêu cầu hoàn hàng
                    </a>
                @endif
                <a href="{{ route('support.create', ['type' => 'complaint', 'order' => $order->order_code]) }}" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-ui-border bg-surface hover:bg-surface-alt text-heading transition">
                    Gửi khiếu nại
                </a>
            </div>
        </div>

        @if($supportRequests->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-ui-border space-y-2">
                @foreach($supportRequests as $sr)
                    <a href="{{ route('support.show', $sr) }}" class="flex items-center justify-between gap-3 rounded-xl border border-ui-border bg-page px-4 py-3 hover:border-primary transition">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-heading">{{ $sr->typeLabel() }} · {{ $sr->code }}</span>
                            <span class="block text-[11px] text-muted truncate">{{ $sr->reasonLabel() }} · {{ $sr->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $sr->statusTone() }}">{{ $sr->statusLabel() }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Recipient Info -->
        <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Thông tin nhận hàng</h3>
            <div class="space-y-1.5 text-xs text-body">
                <p class="font-bold text-sm text-heading">{{ $order->customer_name }}</p>
                <p>📞 {{ $order->customer_phone }}</p>
                @if($order->customer_email)
                    <p>✉️ {{ $order->customer_email }}</p>
                @endif
                <p class="text-muted mt-2 pt-2 border-t border-ui-border">
                    📍 {{ $order->shipping_address }}
                    @if($order->ward_name), {{ $order->ward_name }}@endif
                    @if($order->district_name), {{ $order->district_name }}@endif
                    @if($order->province_name), {{ $order->province_name }}@endif
                </p>
                @if($order->note)
                    <p class="mt-2 text-heading bg-surface-alt p-2 rounded-lg border border-ui-border">
                        <strong>Ghi chú:</strong> {{ $order->note }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Shipping Partner Info -->
        <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Vận chuyển & Giao hàng</h3>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-muted block">Đơn vị vận chuyển:</span>
                    <span class="font-bold text-heading">Giao Hàng Nhanh (GHN)</span>
                </div>
                @if($order->ghn_order_code)
                    <div>
                        <span class="text-muted block">Mã vận đơn GHN:</span>
                        <span class="font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/20 inline-block">
                            {{ $order->ghn_order_code }}
                        </span>
                    </div>
                    <div>
                        <span class="text-muted block">Trạng thái GHN:</span>
                        <span class="font-semibold text-heading">{{ $order->ghn_status_label }}</span>
                    </div>
                @else
                    <p class="text-muted italic">Đơn hàng đang chờ shop đóng gói và tạo đơn vận chuyển với GHN.</p>
                @endif
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Hình thức thanh toán</h3>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-muted block">Phương thức:</span>
                    <span class="font-bold text-heading uppercase">{{ $order->payment_method }}</span>
                </div>
                <div>
                    <span class="text-muted block">Trạng thái thanh toán:</span>
                    <span class="font-semibold {{ $order->payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $order->payment_status_label }}
                    </span>
                </div>
                @if($order->payment_status === 'pending' && in_array($order->payment_method, ['vnpay', 'momo']))
                    <div class="mt-3">
                        <a href="{{ route('payments.' . $order->payment_method . '.create', $order->order_code) }}" class="inline-block w-full text-center px-3 py-2 bg-primary hover:opacity-90 text-primary-foreground font-bold rounded-lg transition shadow-sm">
                            Thanh toán ngay qua {{ strtoupper($order->payment_method) }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm overflow-hidden mb-6">
        <div class="p-6 border-b border-ui-border">
            <h3 class="text-sm font-bold text-heading">Danh Sách Sản Phẩm</h3>
        </div>

        <div class="divide-y divide-ui-border">
            @foreach($order->items as $item)
                <div class="p-6 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        @php
                            $img = $item->variant?->product?->primary_image_url ?? asset('images/placeholder.jpg');
                        @endphp
                        <img src="{{ $img }}" alt="{{ $item->product_name }}" class="w-16 h-16 object-cover rounded-xl border border-ui-border bg-surface-alt flex-shrink-0">
                        <div class="min-w-0">
                            <h4 class="font-semibold text-heading text-sm truncate">{{ $item->product_name }}</h4>
                            @if(!empty($item->variant_info))
                                <p class="text-xs text-muted mt-0.5">{{ $item->variant_info }}</p>
                            @endif
                            <p class="text-xs text-muted mt-1">Đơn giá: {{ number_format($item->price, 0, ',', '.') }}đ × {{ $item->quantity }}</p>
                        </div>
                    </div>

                    <div class="text-right flex-shrink-0">
                        <span class="text-base font-bold text-heading">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</span>
                        @if($order->order_status === \App\Models\Order::STATUS_COMPLETED && $item->variant?->product?->slug)
                            <a href="{{ route('products.show', $item->variant->product->slug) }}#reviews" class="mt-1.5 flex items-center justify-end gap-1 text-xs font-semibold text-primary hover:underline">
                                ★ Đánh giá
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Summary -->
        <div class="bg-surface-alt/70 p-6 border-t border-ui-border space-y-2 text-xs">
            <div class="flex justify-between text-muted">
                <span>Tạm tính sản phẩm:</span>
                <span class="font-semibold text-heading">{{ number_format($order->subtotal, 0, ',', '.') }}đ</span>
            </div>
            <div class="flex justify-between text-muted">
                <span>Phí vận chuyển (GHN):</span>
                <span class="font-semibold text-heading">{{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.').'đ' : 'Miễn phí' }}</span>
            </div>
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                    <span>Giảm giá khuyến mãi:</span>
                    <span class="font-semibold">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                </div>
            @endif
            <div class="pt-2 border-t border-ui-border flex justify-between text-sm">
                <span class="font-bold text-heading">Tổng thanh toán:</span>
                <span class="text-xl font-bold text-heading">{{ number_format($order->total_price, 0, ',', '.') }}đ</span>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-muted hover:text-heading">
            ← Quay lại lịch sử đơn hàng
        </a>

        <form action="{{ route('orders.reorder', $order->order_code) }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-primary hover:opacity-90 text-primary-foreground font-bold text-xs rounded-xl transition shadow-sm">
                Đặt lại đơn hàng này
            </button>
        </form>
    </div>
</div>
@endsection
