@extends('layouts.app')

@section('title', 'Quên Mật Khẩu | Mộc An')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-surface rounded-2xl border border-ui-border shadow-lg p-6 sm:p-8 transition-colors">
        <h1 class="text-2xl sm:text-3xl font-bold font-display text-heading text-center mb-2">Quên Mật Khẩu</h1>
        <p class="text-xs text-muted text-center mb-6">Nhập email, số điện thoại hoặc tên đăng nhập. Chúng tôi sẽ gửi mã xác thực 6 chữ số tới email hoặc số điện thoại đã đăng ký.</p>

        @include('auth.partials.flash')

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="account" class="block text-xs font-semibold uppercase tracking-wider text-heading mb-1.5">Email / SĐT / Tên đăng nhập</label>
                <input
                    type="text"
                    id="account"
                    name="account"
                    value="{{ old('account') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="vd: ban@example.com hoặc 0912345678"
                    class="w-full text-sm rounded-xl border border-ui-border bg-surface-alt px-3.5 py-2.5 text-heading placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition @error('account') border-rose-500 @enderror"
                >
                @error('account') <span class="text-xs text-rose-500 block mt-1.5">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full bg-primary hover:opacity-90 text-primary-foreground font-bold py-3 rounded-xl transition shadow-sm text-sm">
                Gửi Mã Xác Thực
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-ui-border text-center text-xs text-muted">
            Đã nhớ mật khẩu? <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">Đăng nhập</a>
        </div>
    </div>
</div>
@endsection
