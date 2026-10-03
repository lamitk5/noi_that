@extends('layouts.app')

@section('title', 'Báo giá | Mộc An')

@section('content')
<div class="bg-page min-h-screen py-10">
    <div class="page-shell max-w-4xl">
        <p class="eyebrow">Dự án</p>
        <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Bảng báo giá</h1>
        <p class="mt-3 text-sm text-muted">Chọn sản phẩm, số lượng và chiết khấu. Trang in dùng cùng khổ A4 với hoá đơn.</p>

        @if ($errors->any())
            <ul class="mt-4 list-disc space-y-1 rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('quotes.store') }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-xs font-bold text-heading">Tên dự án
                    <input name="project_name" value="{{ old('project_name') }}" required class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-xs font-bold text-heading">Người nhận
                    <input name="contact_name" value="{{ old('contact_name') }}" required class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-xs font-bold text-heading">Điện thoại
                    <input name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-xs font-bold text-heading">Email
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-xs font-bold text-heading">Chiết khấu (%)
                    <input type="number" name="discount" min="0" max="80" step="0.5" value="{{ old('discount', 0) }}" class="mt-1 w-full rounded-xl border border-ui-border bg-surface px-3 py-2 text-sm font-normal">
                </label>
            </div>

            <div class="overflow-hidden rounded-2xl border border-ui-border bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-alt text-[11px] uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-4 py-3">Sản phẩm</th>
                            <th class="px-4 py-3">Kích thước</th>
                            <th class="px-4 py-3 w-28">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $index => $product)
                            <tr class="border-t border-ui-border">
                                <td class="px-4 py-3">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                                    <span class="font-semibold text-heading">{{ $product->name }}</span>
                                    <span class="block text-xs text-muted">{{ number_format((float) $product->final_price, 0, ',', '.') }}₫</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted">{{ \App\Support\FurnitureGlb::displaySize($product->name, $product->dimensions) }}</td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[{{ $index }}][quantity]" min="0" max="99" value="{{ old('items.'.$index.'.quantity', 0) }}" class="w-20 rounded-lg border border-ui-border bg-page px-2 py-1.5 text-sm">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-primary-foreground">Xuất báo giá</button>
        </form>
    </div>
</div>
@endsection
