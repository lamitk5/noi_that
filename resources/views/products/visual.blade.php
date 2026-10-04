@extends('layouts.app')

@section('title', 'Tìm bằng ảnh | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10">
    <div class="page-shell">
        <p class="eyebrow">Kho sẵn hàng</p>
        <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Tìm nội thất bằng ảnh</h1>
        <p class="mt-3 max-w-2xl text-sm text-muted">Tải ảnh góc phòng hoặc mẫu từ Pinterest. Hệ thống đọc kiểu dáng, màu và gợi ý món đang có trong kho.</p>

        <form method="POST" action="{{ route('products.visual.search') }}" enctype="multipart/form-data" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-ui-border bg-surface p-4">
            @csrf
            <label class="text-xs font-bold text-heading">Ảnh jpg, png hoặc webp, tối đa 4 MB
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="mt-1 block text-sm font-normal">
            </label>
            <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground" @disabled(! $configured)>Phân tích</button>
        </form>
        @error('image') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        @if (! $configured)
            <p class="mt-3 text-sm text-amber-700">Chưa có GEMINI_API_KEY nên chưa gọi được dịch vụ phân tích ảnh.</p>
        @endif

        @if ($analysis)
            <p class="mt-6 text-sm text-muted">
                Nhận diện:
                {{ $analysis['room'] ?? 'phòng chưa rõ' }},
                {{ $analysis['style'] ?? 'kiểu chưa rõ' }},
                màu {{ $analysis['color'] ?? 'chưa rõ' }}.
            </p>
        @endif

        @if ($products->isNotEmpty())
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <article class="rounded-2xl border border-ui-border bg-surface p-3">
                        <a href="{{ route('products.show', $product->slug) }}" class="block">
                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="aspect-4/3 w-full rounded-xl object-cover">
                            <p class="mt-3 text-[11px] uppercase tracking-wider text-muted">{{ $product->category?->name }}</p>
                            <h2 class="font-display text-lg font-semibold text-heading">{{ $product->name }}</h2>
                            <p class="mt-1 text-sm font-bold">{{ number_format((float) $product->final_price, 0, ',', '.') }}₫</p>
                        </a>
                        <div class="mt-3">
                            @include('partials.compare-toggle', ['product' => $product, 'variant' => 'chip'])
                        </div>
                    </article>
                @endforeach
            </div>
        @elseif ($analysis)
            <p class="mt-6 text-sm text-muted">Chưa thấy món trong kho khớp với ảnh này.</p>
        @endif
    </div>
</div>
@endsection
