@extends('layouts.admin')

@section('title', 'Chỉnh sửa câu hỏi FAQ | Admin Mộc An')
@section('header', 'Chỉnh sửa câu hỏi')

@section('content')
<div class="max-w-2xl mx-auto rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
    <div class="border-b border-ui-border pb-4 mb-6">
        <h1 class="text-lg font-bold font-display text-heading">Chỉnh sửa câu hỏi thường gặp</h1>
        <p class="text-xs text-muted mt-0.5">Cập nhật nội dung câu hỏi và câu trả lời.</p>
    </div>

    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Danh mục <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    name="category"
                    value="{{ old('category', $faq->category) }}"
                    required
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
                @error('category')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Thứ tự sắp xếp
                </label>
                <input
                    type="number"
                    name="sort_order"
                    value="{{ old('sort_order', $faq->sort_order) }}"
                    min="0"
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
                >
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                Câu hỏi <span class="text-rose-500">*</span>
            </label>
            <input
                type="text"
                name="question"
                value="{{ old('question', $faq->question) }}"
                required
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none"
            >
            @error('question')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                Nội dung câu trả lời <span class="text-rose-500">*</span>
            </label>
            <textarea
                name="answer"
                rows="6"
                required
                class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
            >{{ old('answer', $faq->answer) }}</textarea>
            @error('answer')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-2">
            <input
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                class="rounded border-ui-border text-primary focus:ring-primary"
                {{ old('is_active', $faq->is_active) ? 'checked' : '' }}
            >
            <label for="is_active" class="text-xs font-medium text-heading">Kích hoạt hiển thị cho khách hàng</label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-ui-border">
            <a href="{{ route('admin.faqs.index') }}" class="text-xs font-semibold text-muted hover:text-heading">
                ← Hủy bỏ
            </a>
            <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
            >
                Cập nhật câu hỏi
            </button>
        </div>
    </form>
</div>
@endsection
