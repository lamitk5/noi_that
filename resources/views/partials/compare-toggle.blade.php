@php
    $variant = $variant ?? 'icon';
    $inCompare = in_array($product->id, $compareProductIds ?? []);
@endphp

@if ($variant === 'detail')
    <button
        type="button"
        class="compare-toggle compare-detail-btn {{ $inCompare ? 'is-active' : '' }}"
        data-compare-url="{{ route('compare.toggle', $product) }}"
        data-product-id="{{ $product->id }}"
        aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
        aria-label="{{ $inCompare ? 'Bỏ '.$product->name.' khỏi so sánh' : 'Thêm '.$product->name.' vào so sánh' }}"
    >
        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V5H4v14h5Zm11 0V5h-5v14h5ZM9 12h6"/>
        </svg>
        <span class="compare-btn-text">{{ $inCompare ? 'Đang trong bảng so sánh' : 'Thêm vào so sánh' }}</span>
    </button>
@elseif ($variant === 'chip')
    <button
        type="button"
        class="compare-toggle compare-chip {{ $inCompare ? 'is-active' : '' }}"
        data-compare-url="{{ route('compare.toggle', $product) }}"
        data-product-id="{{ $product->id }}"
        aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
        aria-label="{{ $inCompare ? 'Bỏ '.$product->name.' khỏi so sánh' : 'So sánh '.$product->name }}"
    >
        <span class="compare-btn-text">{{ $inCompare ? 'Đang so sánh' : 'So sánh' }}</span>
    </button>
@else
    <button
        type="button"
        class="compare-toggle compare-button {{ $inCompare ? 'is-active' : '' }}"
        data-compare-url="{{ route('compare.toggle', $product) }}"
        data-product-id="{{ $product->id }}"
        aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
        title="{{ $inCompare ? 'Bỏ khỏi so sánh' : 'Thêm vào so sánh' }}"
        aria-label="{{ $inCompare ? 'Bỏ '.$product->name.' khỏi so sánh' : 'Thêm '.$product->name.' vào so sánh' }}"
    >
        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V5H4v14h5Zm11 0V5h-5v14h5ZM9 12h6"/>
        </svg>
    </button>
@endif
