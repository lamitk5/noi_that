@extends('layouts.app')

@section('title', '503 — Hệ thống đang bảo trì | Mộc An')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex size-20 items-center justify-center rounded-3xl bg-blue-500/10 text-blue-600 mb-6">
            <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.162 1.15-.098 1.636.188l4.475 2.63a2.548 2.548 0 003.586-3.586l-2.63-4.475a2.548 2.548 0 00-1.824-1.208c-.468-.073-.94.02-1.353.264l-3.03 2.496" />
            </svg>
        </div>
        <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">Bảo trì hệ thống</span>
        <h1 class="font-display text-3xl font-bold text-heading mb-3">Đang nâng cấp dịch vụ</h1>
        <p class="text-sm text-muted mb-8">Mộc An đang thực hiện bảo trì định kỳ để nâng cao trải nghiệm mua sắm. Chúng tôi sẽ trở lại trong ít phút. Hotline hỗ trợ: 1900 6868.</p>
        <div class="flex items-center justify-center gap-4">
            <button onclick="window.location.reload()" class="btn-primary">Tải lại trang</button>
            <a href="tel:19006868" class="btn-secondary">Gọi Hotline</a>
        </div>
    </div>
</div>
@endsection
