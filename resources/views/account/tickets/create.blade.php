@extends('layouts.app')

@section('title', 'Tạo yêu cầu hỗ trợ mới | Mộc An')

@section('content')
<div class="page-shell py-12">
    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('home') }}" class="hover:text-heading">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}" class="hover:text-heading">Tài khoản</a>
        <span>/</span>
        <a href="{{ route('account.tickets.index') }}" class="hover:text-heading">Yêu cầu hỗ trợ</a>
        <span>/</span>
        <span class="text-heading font-medium">Tạo mới</span>
    </nav>

    <div class="max-w-2xl mx-auto rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs">
        <div class="border-b border-ui-border pb-5 mb-6">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Bộ phận CSKH Mộc An</span>
            <h1 class="mt-1 font-display text-2xl font-bold text-heading">Gửi yêu cầu hỗ trợ</h1>
            <p class="text-xs text-muted mt-1">Vui lòng điền thông tin chi tiết để chúng tôi phục vụ bạn tốt nhất.</p>
        </div>

        <form method="POST" action="{{ route('account.tickets.store') }}" class="space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="category" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                        Chủ đề cần hỗ trợ <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="category"
                        name="category"
                        required
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                    >
                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>Tư vấn chung</option>
                        <option value="order" {{ old('category') === 'order' ? 'selected' : '' }}>Đơn hàng & Giao vận</option>
                        <option value="product" {{ old('category') === 'product' ? 'selected' : '' }}>Sản phẩm & Thông số</option>
                        <option value="warranty" {{ old('category') === 'warranty' ? 'selected' : '' }}>Bảo hành & Bảo trì</option>
                        <option value="complaint" {{ old('category') === 'complaint' ? 'selected' : '' }}>Khiếu nại & Đổi trả</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                        Mức độ ưu tiên
                    </label>
                    <select
                        id="priority"
                        name="priority"
                        class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                    >
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Thấp</option>
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Bình thường</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Cao</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="subject" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Tiêu đề yêu cầu <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="{{ old('subject') }}"
                    required
                    placeholder="Ví dụ: Cần kiểm tra thời gian giao hàng đơn ORD-2026..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none"
                >
                @error('subject')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="message" class="block text-xs font-bold uppercase tracking-wider text-heading mb-1.5">
                    Nội dung chi tiết <span class="text-rose-500">*</span>
                </label>
                <textarea
                    id="message"
                    name="message"
                    rows="6"
                    required
                    placeholder="Mô tả cụ thể thắc mắc hoặc vấn đề bạn gặp phải..."
                    class="w-full rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-xs text-heading focus:border-primary focus:outline-none leading-relaxed"
                >{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-ui-border">
                <a href="{{ route('account.tickets.index') }}" class="text-xs font-semibold text-muted hover:text-heading">
                    ← Quay lại danh sách
                </a>
                <button
                    type="submit"
                    class="px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold hover:opacity-95 transition shadow-xs cursor-pointer"
                >
                    Gửi yêu cầu hỗ trợ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
