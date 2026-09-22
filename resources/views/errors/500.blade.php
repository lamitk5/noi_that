@extends('layouts.app')

@section('title', '500 — Lỗi máy chủ | Mộc An')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex size-20 items-center justify-center rounded-3xl bg-rose-500/10 text-rose-600 mb-6">
            <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </div>
        <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">Lỗi 500</span>
        <h1 class="font-display text-3xl font-bold text-heading mb-3">Đã xảy ra sự cố</h1>
        <p class="text-sm text-muted mb-8">Hệ thống đang gặp trục trặc tạm thời khi xử lý yêu cầu của bạn. Đội ngũ kỹ thuật đã được thông báo để khắc phục ngay.</p>
        <div class="flex items-center justify-center gap-4">
            <button onclick="window.location.reload()" class="btn-primary">Thử lại</button>
            <a href="{{ route('home') }}" class="btn-secondary">Về trang chủ</a>
        </div>
    </div>
</div>
@endsection
