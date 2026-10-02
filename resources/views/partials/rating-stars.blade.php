@php
    $ratingValue = (float) ($rating ?? 0);
    $starSize = $size ?? 'size-3.5';
@endphp
<span class="inline-flex items-center gap-0.5 text-amber-500" aria-label="{{ number_format($ratingValue, 1) }} trên 5 sao">
    @for ($i = 1; $i <= 5; $i++)
        <svg viewBox="0 0 20 20" class="{{ $starSize }} {{ $ratingValue >= $i - 0.25 ? 'fill-current' : ($ratingValue >= $i - 0.75 ? 'fill-current opacity-60' : 'fill-none stroke-current opacity-50') }}" stroke-width="1.2">
            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/>
        </svg>
    @endfor
</span>
