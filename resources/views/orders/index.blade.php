@extends('layouts.app')

@section('title', 'Lịch Sử Đơn Hàng | Mộc An')

@section('content')
<div class="page-shell py-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-heading">Lịch Sử Đơn Hàng</h1>
            <p class="text-xs text-muted mt-1">Theo dõi quá trình vận chuyển và quản lý đơn hàng của bạn</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.index') }}" class="text-xs font-semibold text-primary-foreground bg-primary hover:opacity-90 px-4 py-2 rounded-lg transition shadow-sm">
                + Mua sắm thêm
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm rounded-xl flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 text-sm rounded-xl flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Status Filter Tabs -->
    <div class="bg-surface rounded-2xl border border-ui-border shadow-sm mb-6 overflow-hidden">
        <div class="flex overflow-x-auto border-b border-ui-border scrollbar-none text-sm font-semibold">
            @php
                $tabs = [
                    'all' => ['label' => 'Tất cả', 'key' => 'all'],
                    'pending' => ['label' => 'Chờ xử lý', 'key' => 'pending'],
                    'confirmed' => ['label' => 'Đã xác nhận', 'key' => 'confirmed'],
                    'shipping' => ['label' => 'Đang giao hàng', 'key' => 'shipping'],
                    'completed' => ['label' => 'Hoàn thành', 'key' => 'completed'],
                    'cancelled' => ['label' => 'Đã hủy', 'key' => 'cancelled'],
                ];
            @endphp

            @foreach($tabs as $tabKey => $tab)
                @php
                    $isActive = ($currentStatus === $tabKey);
                    $count = $counts[$tabKey] ?? 0;
                @endphp
                <a
                    href="{{ route('orders.index', ['status' => $tabKey]) }}"
                    class="flex-shrink-0 px-5 py-3.5 border-b-2 transition flex items-center gap-2 {{ $isActive ? 'border-primary text-primary bg-primary/10 font-bold' : 'border-transparent text-muted hover:text-heading hover:bg-surface-alt' }}"
                >
                    <span>{{ $tab['label'] }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $isActive ? 'bg-primary text-primary-foreground' : 'bg-surface-alt text-muted border border-ui-border' }}">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Orders List -->
    @if($orders->count() > 0)
        <div class="space-y-4">
            @foreach($orders as $order)
                <div id="order-{{ $order->order_code }}" class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6 transition hover:shadow-md">
                    <!-- Order Header -->
                    <div class="flex flex-wrap items-center justify-between pb-4 border-b border-ui-border gap-3">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-heading text-base">#{{ $order->order_code }}</span>
                            <span class="text-xs text-muted">·</span>
                            <span class="text-xs text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Payment Status Badge -->
                            @if($order->payment_status === 'paid')
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                    ✓ Đã thanh toán ({{ strtoupper($order->payment_method) }})
                                </span>
                            @else
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-surface-alt text-muted border border-ui-border">
                                    {{ $order->payment_status_label }}
                                </span>
                            @endif

                            <!-- Order Status Badge -->
                            @php
                                $statusClasses = match($order->order_status) {
                                    'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
                                    'confirmed' => 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
                                    'shipping' => 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/20',
                                    'completed' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
                                    'cancelled', 'canceled' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20',
                                    default => 'bg-surface-alt text-muted border-ui-border',
                                };
                            @endphp
                            <span class="text-xs px-3 py-1 rounded-full font-bold border {{ $statusClasses }}">
                                {{ $order->order_status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="py-4 space-y-3">
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    @php
                                        $image = $item->variant?->product?->primary_image_url ?? asset('images/placeholder.jpg');
                                    @endphp
                                    <img src="{{ $image }}" alt="{{ $item->product_name }}" class="w-14 h-14 object-cover rounded-lg border border-ui-border flex-shrink-0 bg-surface-alt">
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-semibold text-heading truncate">{{ $item->product_name }}</h4>
                                        @if(!empty($item->variant_info))
                                            <p class="text-xs text-muted mt-0.5">{{ $item->variant_info }}</p>
                                        @endif
                                        <p class="text-xs text-muted mt-0.5">Số lượng: x{{ $item->quantity }}</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-sm font-bold text-heading">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</span>
                                    <span class="text-xs text-muted block">{{ number_format($item->price, 0, ',', '.') }}đ / sp</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($order->order_status === 'completed' && (int) $order->user_id === (int) auth()->id())
                        @php
                            $reviewProducts = $order->items
                                ->map(fn ($item) => $item->variant?->product)
                                ->filter()
                                ->unique('id')
                                ->values();
                        @endphp
                        @if($reviewProducts->isNotEmpty())
                            <div class="pb-4 space-y-3">
                                <p class="text-xs font-bold text-heading">Đánh giá sản phẩm đã nhận</p>
                                @foreach($reviewProducts as $product)
                                    @include('orders.partials.item-review', [
                                        'order' => $order,
                                        'product' => $product,
                                        'review' => $reviewsByProduct->get($product->id),
                                        'currentStatus' => $currentStatus,
                                        'returnTo' => 'orders',
                                    ])
                                @endforeach
                            </div>
                        @endif
                    @endif

                    <!-- Order Footer -->
                    <div class="pt-4 border-t border-ui-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm">
                            <span class="text-muted">Tổng thanh toán:</span>
                            <span class="text-lg font-bold text-heading ml-1">{{ number_format($order->total_price, 0, ',', '.') }}đ</span>
                            @if($order->shipping_fee > 0)
                                <span class="text-xs text-muted block">(Đã gồm {{ number_format($order->shipping_fee, 0, ',', '.') }}đ phí ship)</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Cancel Button (Only if pending) -->
                            @if($order->order_status === 'pending')
                                <form action="{{ route('orders.cancel', $order->order_code) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 border border-rose-300 dark:border-rose-800 rounded-lg transition">
                                        Hủy đơn hàng
                                    </button>
                                </form>
                            @endif

                            <!-- Reorder Button -->
                            <form action="{{ route('orders.reorder', $order->order_code) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 text-xs font-semibold text-primary hover:bg-primary/10 border border-primary/30 rounded-lg transition">
                                    Mua lại
                                </button>
                            </form>

                            <!-- View Details Button -->
                            <a href="{{ route('orders.show', $order->order_code) }}" class="px-4 py-2 text-xs font-bold text-primary-foreground bg-primary hover:opacity-90 rounded-lg transition shadow-sm">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-surface rounded-2xl border border-ui-border shadow-sm p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface-alt border border-ui-border flex items-center justify-center text-muted">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 class="text-base font-bold text-heading mb-1">Chưa có đơn hàng nào trong mục này</h3>
            <p class="text-xs text-muted mb-6">Bạn chưa có đơn hàng nào ở trạng thái này hoặc chưa đặt hàng.</p>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:opacity-90 text-primary-foreground font-semibold text-xs rounded-lg transition shadow-sm">
                Khám phá sản phẩm Mộc An
            </a>
        </div>
    @endif
</div>
@endsection
