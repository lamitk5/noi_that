@php
    $review = $review ?? null;
    $canEdit = ! $review || (int) $review->order_id === (int) $order->id;
    $showName = $showName ?? true;
    $returnTo = $returnTo ?? 'orders';
    $currentStatus = $currentStatus ?? 'all';
    $showErrors = (int) old('form_product_id') === (int) $product->id
        && (string) old('form_order_code') === (string) $order->order_code;
    $selectedRating = (int) ($showErrors ? old('rating', $review?->rating ?? 5) : ($review?->rating ?? 5));
@endphp

<div id="review-{{ $order->order_code }}-{{ $product->id }}" class="rounded-xl border border-ui-border bg-surface-alt/50 p-3.5">
    @if($showName)
        <p class="mb-2 text-xs font-semibold text-heading truncate">{{ $product->name }}</p>
    @endif

    @if($canEdit)
        <form method="POST" action="{{ route('reviews.store', $product) }}" class="space-y-2.5" x-data="{ rating: {{ $selectedRating }}, hover: 0 }">
            @csrf
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <input type="hidden" name="status_filter" value="{{ $currentStatus }}">
            <input type="hidden" name="form_product_id" value="{{ $product->id }}">
            <input type="hidden" name="form_order_code" value="{{ $order->order_code }}">
            <input type="hidden" name="rating" value="{{ $selectedRating }}" :value="rating">

            <div class="flex items-center gap-1" @mouseleave="hover = 0">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" class="text-amber-500 transition hover:scale-110" aria-label="{{ $i }} sao">
                        <svg viewBox="0 0 20 20" class="size-5" stroke="currentColor" stroke-width="1.2" :class="(hover || rating) >= {{ $i }} ? 'fill-current' : 'fill-none'">
                            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/>
                        </svg>
                    </button>
                @endfor
                <span class="ml-1 text-[11px] text-muted" x-text="rating + '/5'"></span>
            </div>

            @if($showErrors)
                @error('rating') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
                @error('review') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
            @endif

            <textarea
                name="comment"
                rows="2"
                maxlength="1000"
                required
                placeholder="Chia sẻ cảm nhận về chất lượng, màu gỗ, giao hàng..."
                class="w-full text-sm rounded-xl border border-ui-border bg-surface px-3 py-2 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition"
            >{{ $showErrors ? old('comment', $review?->comment) : $review?->comment }}</textarea>

            @if($showErrors)
                @error('comment') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
            @endif

            <button type="submit" class="px-3.5 py-2 text-xs font-bold text-primary-foreground bg-primary hover:opacity-90 rounded-lg transition shadow-sm">
                {{ $review ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
            </button>
        </form>
    @else
        <div class="flex items-center gap-2">
            @include('partials.rating-stars', ['rating' => $review->rating, 'size' => 'size-4'])
            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Đã đánh giá</span>
        </div>
        @if($review->comment)
            <p class="mt-1.5 text-sm text-body whitespace-pre-line">{{ $review->comment }}</p>
        @endif
        <p class="mt-1 text-[11px] text-muted">Bạn đã đánh giá sản phẩm này ở một đơn hàng khác.</p>
    @endif
</div>
