@extends('layouts.app')

@section('title', '404 — Không tìm thấy trang | Mộc An')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex size-20 items-center justify-center rounded-3xl bg-primary/10 text-primary mb-6">
            <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
        <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">Lỗi 404</span>
        <h1 class="font-display text-3xl font-bold text-heading mb-3">Không tìm thấy trang</h1>
        <p class="text-sm text-muted mb-8">Trang bạn đang tìm kiếm không tồn tại, đã đổi địa chỉ hoặc tạm ngừng cung cấp. Khám phá các bộ sưu tập nội thất của chúng tôi bên dưới.</p>
        <div class="flex items-center justify-center gap-4">
            <a href="{{ route('home') }}" class="btn-primary">Về trang chủ</a>
            <a href="{{ route('products.index') }}" class="btn-secondary">Xem sản phẩm</a>
        </div>
    </div>
</div>
@endsection
