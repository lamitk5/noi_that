@extends('layouts.app')

@section('title', 'So sánh sản phẩm | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10 sm:py-14" data-compare-page="1">
    <div class="page-shell">
        <nav class="mb-3 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-heading transition-colors">Sản phẩm</a>
            <span>/</span>
            <span class="font-medium text-heading">So sánh</span>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Đặt cạnh nhau</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-heading sm:text-4xl">So sánh sản phẩm</h1>
                <p class="mt-3 max-w-2xl text-sm text-muted sm:text-base">Chọn từ 2 đến {{ $max }} món để xem giá, kích thước, chất liệu và tồn kho trên cùng một bảng.</p>
            </div>
            @if ($products->isNotEmpty())
                <form method="POST" action="{{ route('compare.clear') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-ui-border bg-surface px-4 py-2.5 text-xs font-bold text-muted transition hover:text-heading">Xóa hết</button>
                </form>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="mx-auto my-12 max-w-md rounded-3xl border border-ui-border bg-surface p-12 text-center shadow-sm">
                <div class="mx-auto mb-4 grid size-16 place-items-center rounded-full bg-surface-alt text-muted">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V5H4v14h5Zm11 0V5h-5v14h5ZM9 12h6"/>
                    </svg>
                </div>
                <h2 class="mb-2 font-display text-xl font-semibold text-heading">Chưa có sản phẩm để so sánh</h2>
                <p class="mb-6 text-sm text-muted">Bấm “So sánh” trên thẻ sản phẩm. Có thể chọn tối đa {{ $max }} món.</p>
                <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center rounded-full bg-primary px-7 py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-lg shadow-primary/20 transition hover:-translate-y-0.5">Xem sản phẩm</a>
            </div>
        @else
            @if ($products->count() < 2)
                <p class="mt-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">Hãy thêm ít nhất một sản phẩm nữa để bắt đầu so sánh.</p>
            @else
                <p class="mt-6 text-xs text-muted">Ô được tô là thông tin khác nhau. Nhãn trên giá, đánh giá và tồn kho chỉ ra giá trị nổi bật hơn.</p>
            @endif

            <div class="mt-6 overflow-x-auto rounded-3xl border border-ui-border bg-surface shadow-sm">
                <table class="w-full min-w-[720px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-ui-border">
                            <th class="sticky left-0 z-10 w-36 bg-surface p-4 text-[11px] font-bold uppercase tracking-[0.14em] text-muted">Tiêu chí</th>
                            @foreach ($products as $product)
                                @php
                                    $cartVariant = $product->variants->first(fn ($variant) => $variant->stock > 0);
                                @endphp
                                <th class="min-w-[220px] border-l border-ui-border p-4 align-top font-normal">
                                    <a href="{{ route('products.show', $product->slug) }}" class="block overflow-hidden rounded-2xl bg-surface-alt">
                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full object-cover">
                                    </a>
                                    <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $product->category?->name ?? 'Nội thất' }}</p>
                                    <a href="{{ route('products.show', $product->slug) }}" class="mt-1 block font-display text-lg font-semibold leading-snug text-heading hover:text-accent">{{ $product->name }}</a>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('products.show', $product->slug) }}" class="rounded-xl border border-ui-border px-3 py-2 text-xs font-bold text-heading transition hover:border-primary hover:text-primary">Xem chi tiết</a>
                                        @if ($cartVariant)
                                            <form method="POST" action="{{ route('cart.store') }}">
                                                @csrf
                                                <input type="hidden" name="variant_id" value="{{ $cartVariant->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="rounded-xl bg-primary px-3 py-2 text-xs font-bold text-primary-foreground transition hover:opacity-90">Thêm vào giỏ</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('compare.toggle', $product) }}">
                                            @csrf
                                            <button type="submit" class="compare-toggle rounded-xl px-2 py-2 text-xs font-semibold text-muted transition hover:text-rose-600" data-compare-url="{{ route('compare.toggle', $product) }}" data-product-id="{{ $product->id }}">Bỏ</button>
                                        </form>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-ui-border last:border-b-0">
                                <th class="sticky left-0 z-10 bg-surface p-4 text-xs font-bold text-heading">{{ $row['label'] }}</th>
                                @foreach ($row['cells'] as $cell)
                                    <td class="border-l border-ui-border p-4 align-top text-sm leading-relaxed {{ $cell['differs'] ? 'bg-amber-500/10' : 'text-body' }} {{ $cell['best'] ? 'font-semibold text-heading' : 'text-body' }}">
                                        <span>{{ $cell['text'] }}</span>
                                        @if ($cell['badge'])
                                            <span class="ml-1.5 inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary">{{ $cell['badge'] }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
