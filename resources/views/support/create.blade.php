@extends('layouts.app')

@php
    $isReturn = $type === 'return';
    $oldItems = old('items', []);
@endphp

@section('title', ($isReturn ? 'Yêu cầu hoàn hàng' : 'Gửi khiếu nại').' | Mộc An')

@section('content')
<div class="page-shell py-8 max-w-3xl">
    <div class="flex items-center gap-2 text-xs text-muted mb-2">
        <a href="{{ route('support.index') }}" class="hover:text-primary transition">Hoàn hàng & khiếu nại</a>
        <span>/</span>
        <span class="text-heading font-semibold">{{ $isReturn ? 'Yêu cầu hoàn hàng' : 'Gửi khiếu nại' }}</span>
    </div>
    <h1 class="text-2xl font-bold text-heading">{{ $isReturn ? 'Yêu cầu hoàn hàng' : 'Gửi khiếu nại' }}</h1>
    <p class="text-xs text-muted mt-1 mb-6">
        @if($isReturn)
            Áp dụng trong {{ \App\Models\SupportRequest::RETURN_WINDOW_DAYS }} ngày kể từ khi nhận hàng. Sản phẩm cần còn nguyên trạng, kèm ảnh chụp tình trạng thực tế.
        @else
            Hãy cho chúng tôi biết điều gì chưa ổn. Mọi khiếu nại đều được bộ phận chăm sóc khách hàng xem xét và phản hồi.
        @endif
    </p>

    @if($errors->any())
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-700 text-sm rounded-xl">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="space-y-6"
          x-data="supportForm({{ $maxPhotos }}, @js(old('resolution', 'refund')))">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <section class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Đơn hàng</h2>
            @if($isReturn)
                <input type="hidden" name="order_code" value="{{ $order->order_code }}">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span class="font-bold text-heading">#{{ $order->order_code }}</span>
                    <span class="text-xs text-muted">Đặt ngày {{ $order->created_at->format('d/m/Y') }} · Hạn hoàn hàng {{ $order->returnDeadline()->format('d/m/Y') }}</span>
                </div>

                <p class="text-xs font-semibold text-heading mt-5 mb-2">Chọn sản phẩm và số lượng cần hoàn</p>
                <div class="divide-y divide-ui-border rounded-xl border border-ui-border">
                    @foreach($order->items as $item)
                        @php $checked = (int) ($oldItems[$item->id] ?? 0) > 0 || ($order->items->count() === 1 && !old('_token')); @endphp
                        <label class="flex items-center gap-4 p-4 cursor-pointer" x-data="{ on: @js($checked) }">
                            <input type="checkbox" x-model="on" class="size-4 accent-[var(--color-primary)]" data-item-check="{{ $item->id }}">
                            <img src="{{ $item->variant?->product?->primary_image_url ?? asset('images/placeholder.jpg') }}" alt="" class="size-14 rounded-lg object-cover border border-ui-border bg-surface-alt">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-heading truncate">{{ $item->product_name }}</p>
                                @if($item->variant_info)
                                    <p class="text-[11px] text-muted">{{ $item->variant_info }}</p>
                                @endif
                                <p class="text-[11px] text-muted">{{ number_format($item->price, 0, ',', '.') }}đ × {{ $item->quantity }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted" x-show="on" x-cloak>
                                <span>SL</span>
                                <input type="number" name="items[{{ $item->id }}]" min="1" max="{{ $item->quantity }}"
                                       value="{{ (int) ($oldItems[$item->id] ?? 0) ?: $item->quantity }}"
                                       :disabled="!on"
                                       class="w-16 rounded-lg border border-ui-border bg-page px-2 py-1.5 text-sm text-heading outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </label>
                    @endforeach
                </div>
            @else
                <select name="order_code" class="w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">
                    <option value="">Không liên quan đến đơn hàng cụ thể</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->order_code }}" @selected(old('order_code', $order?->order_code) === $o->order_code)>
                            #{{ $o->order_code }} · {{ $o->created_at->format('d/m/Y') }} · {{ number_format($o->total_price, 0, ',', '.') }}đ
                        </option>
                    @endforeach
                </select>
            @endif
        </section>

        <section class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">{{ $isReturn ? 'Lý do hoàn hàng' : 'Vấn đề bạn gặp phải' }}</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($reasons as $key => $label)
                    <label class="flex items-center gap-2.5 rounded-xl border border-ui-border bg-page px-3.5 py-3 text-sm text-body cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:text-heading transition">
                        <input type="radio" name="reason" value="{{ $key }}" @checked(old('reason') === $key) class="accent-[var(--color-primary)]">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            @unless($isReturn)
                <label class="block mt-5">
                    <span class="text-xs font-semibold text-heading">Tiêu đề (không bắt buộc)</span>
                    <input type="text" name="subject" value="{{ old('subject') }}" maxlength="150" placeholder="VD: Giao hàng trễ 3 ngày so với hẹn"
                           class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">
                </label>
            @endunless

            <label class="block mt-5">
                <span class="text-xs font-semibold text-heading">Mô tả chi tiết</span>
                <textarea name="description" rows="5" maxlength="2000" required
                          placeholder="{{ $isReturn ? 'Mô tả tình trạng sản phẩm: vị trí trầy xước, lỗi gặp phải, thời điểm phát hiện...' : 'Mô tả sự việc, thời gian và mong muốn của bạn...' }}"
                          class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">{{ old('description') }}</textarea>
            </label>
        </section>

        <section class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-muted mb-1">Ảnh minh chứng {{ $isReturn ? '' : '(không bắt buộc)' }}</h2>
            <p class="text-[11px] text-muted mb-3">Tối đa {{ $maxPhotos }} ảnh JPG, PNG, WEBP · mỗi ảnh tối đa 5MB.</p>
            <div class="flex flex-wrap gap-3">
                <template x-for="(src, i) in previews" :key="src">
                    <div class="relative">
                        <img :src="src" class="size-24 rounded-xl object-cover border border-ui-border">
                        <button type="button" @click="removePhoto(i)" class="absolute -top-2 -right-2 size-6 rounded-full bg-rose-600 text-white text-xs font-bold shadow" aria-label="Xóa ảnh">×</button>
                    </div>
                </template>
                <label x-show="previews.length < max" class="size-24 rounded-xl border-2 border-dashed border-ui-border flex flex-col items-center justify-center text-muted text-[11px] cursor-pointer hover:border-primary hover:text-primary transition">
                    <span class="text-2xl leading-none">+</span>
                    <span>Thêm ảnh</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="addPhotos($event)">
                </label>
            </div>
            <input type="file" name="photos[]" multiple class="hidden" x-ref="photos">
        </section>

        @if($isReturn)
            <section class="bg-surface rounded-2xl border border-ui-border shadow-sm p-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Hình thức xử lý mong muốn</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach(\App\Models\SupportRequest::RESOLUTIONS as $key => $label)
                        <label class="flex items-center gap-2.5 rounded-xl border border-ui-border bg-page px-3.5 py-3 text-sm text-body cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:text-heading transition">
                            <input type="radio" name="resolution" value="{{ $key }}" x-model="resolution" class="accent-[var(--color-primary)]">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <label class="block mt-4" x-show="resolution === 'refund'" x-cloak>
                    <span class="text-xs font-semibold text-heading">Tài khoản nhận tiền hoàn</span>
                    <input type="text" name="refund_account" value="{{ old('refund_account') }}" maxlength="255" :disabled="resolution !== 'refund'"
                           placeholder="VD: Vietcombank - 0123456789 - NGUYEN VAN A"
                           class="mt-1.5 w-full rounded-xl border border-ui-border bg-page px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary">
                    <span class="block mt-1 text-[11px] text-muted">Tiền được hoàn trong 2–3 ngày làm việc sau khi Mộc An nhận lại và kiểm tra sản phẩm.</span>
                </label>
            </section>
        @endif

        <div class="flex items-center justify-between gap-3">
            <a href="{{ $order ? route('orders.show', $order->order_code) : route('support.index') }}" class="text-xs font-semibold text-muted hover:text-heading">← Quay lại</a>
            <button type="submit" class="px-6 py-3 bg-primary hover:opacity-90 text-primary-foreground font-bold text-sm rounded-xl transition shadow-sm">
                {{ $isReturn ? 'Gửi yêu cầu hoàn hàng' : 'Gửi khiếu nại' }}
            </button>
        </div>
    </form>
</div>

<script>
    function supportForm(max, resolution) {
        return {
            max,
            resolution,
            files: [],
            previews: [],
            addPhotos(event) {
                for (const file of event.target.files) {
                    if (this.files.length >= this.max) break;
                    if (!file.type.startsWith('image/')) continue;
                    this.files.push(file);
                    this.previews.push(URL.createObjectURL(file));
                }
                event.target.value = '';
                this.sync();
            },
            removePhoto(index) {
                URL.revokeObjectURL(this.previews[index]);
                this.files.splice(index, 1);
                this.previews.splice(index, 1);
                this.sync();
            },
            sync() {
                const transfer = new DataTransfer();
                this.files.forEach((file) => transfer.items.add(file));
                this.$refs.photos.files = transfer.files;
            },
        };
    }
</script>
@endsection
