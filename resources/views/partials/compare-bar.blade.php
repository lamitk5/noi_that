<div
    id="compare-bar"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-ui-border bg-surface/95 shadow-[0_-8px_30px_rgba(0,0,0,0.08)] backdrop-blur-xl"
    @if (($compareCount ?? 0) === 0) hidden @endif
>
    <div class="page-shell flex items-center gap-3 py-3 sm:gap-4">
        <div class="min-w-0 shrink-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-muted">So sánh</p>
            <p class="text-sm font-semibold text-heading"><span id="compare-bar-count">{{ $compareCount ?? 0 }}</span>/{{ \App\Services\CompareService::MAX }}</p>
        </div>

        <div id="compare-bar-items" class="flex min-w-0 flex-1 items-center gap-2 overflow-x-auto">
            @foreach ($compareItems ?? [] as $item)
                <div class="compare-bar-item relative flex w-40 shrink-0 items-center gap-2 rounded-xl border border-ui-border bg-page p-1.5 pr-6" data-product-id="{{ $item->id }}">
                    <img src="{{ $item->primary_image_url }}" alt="" class="size-12 shrink-0 rounded-lg object-cover">
                    <p class="truncate text-[11px] font-semibold leading-snug text-heading">{{ $item->name }}</p>
                    <button
                        type="button"
                        class="compare-toggle absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-heading text-[11px] font-bold leading-none text-page"
                        data-compare-url="{{ route('compare.toggle', $item) }}"
                        data-product-id="{{ $item->id }}"
                        aria-label="Bỏ {{ $item->name }} khỏi so sánh"
                    >×</button>
                </div>
            @endforeach
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <a
                id="compare-bar-go"
                href="{{ route('compare.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-xs font-bold text-primary-foreground shadow-sm transition hover:opacity-90 sm:px-4 {{ ($compareCount ?? 0) < 2 ? 'pointer-events-none opacity-40' : '' }}"
                @if (($compareCount ?? 0) < 2) aria-disabled="true" @endif
            >Xem bảng</a>
            <form method="POST" action="{{ route('compare.clear') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-ui-border px-3 py-2.5 text-xs font-semibold text-muted transition hover:text-heading">Xóa</button>
            </form>
        </div>
    </div>
</div>
