@extends('layouts.app')

@section('title', '419 — Phiên làm việc hết hạn | Mộc An')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex size-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-600 mb-6">
            <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
        </div>
        <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">Lỗi 419</span>
        <h1 class="font-display text-3xl font-bold text-heading mb-3">Phiên làm việc hết hạn</h1>
        <p class="text-sm text-muted mb-8">Trang đã hết hạn bảo mật do không có tương tác trong một khoảng thời gian. Vui lòng tải lại trang và thực hiện lại thao tác.</p>
        <div class="flex items-center justify-center gap-4">
            <button onclick="window.location.reload()" class="btn-primary">Tải lại trang</button>
            <a href="{{ route('home') }}" class="btn-secondary">Về trang chủ</a>
        </div>
    </div>
</div>
@endsection
