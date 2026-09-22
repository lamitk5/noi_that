@extends('layouts.app')

@section('title', '403 — Truy cập bị từ chối | Mộc An')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-md w-full text-center">
        <div class="inline-flex size-20 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-600 mb-6">
            <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.002A11.959 11.959 0 0112 3.464z" />
            </svg>
        </div>
        <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">Lỗi 403</span>
        <h1 class="font-display text-3xl font-bold text-heading mb-3">Truy cập bị từ chối</h1>
        <p class="text-sm text-muted mb-8">Bạn không có quyền truy cập vào trang hoặc tài nguyên này. Vui lòng kiểm tra lại tài khoản hoặc quay về trang chủ.</p>
        <div class="flex items-center justify-center gap-4">
            <a href="{{ route('home') }}" class="btn-primary">Quay về trang chủ</a>
            <a href="{{ url()->previous() }}" class="btn-secondary">Trang trước</a>
        </div>
    </div>
</div>
@endsection
