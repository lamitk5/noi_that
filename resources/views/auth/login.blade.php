@extends('layouts.app')

@section('title', 'Đăng Nhập Tài Khoản')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl border shadow-sm p-8">
        <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">Đăng Nhập</h1>
        <p class="text-xs text-gray-500 text-center mb-6">Truy cập tài khoản cá nhân hoặc bảng quản trị</p>

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mật khẩu</label>
                <input type="password" name="password" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-gray-600">
                    <input type="checkbox" name="remember" class="mr-2">
                    Ghi nhớ đăng nhập
                </label>
            </div>

            <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-bold py-3 rounded-lg transition">
                Đăng Nhập
            </button>
        </form>

        <div class="mt-6 pt-4 border-t text-center text-xs text-gray-600">
            Chưa có tài khoản? <a href="{{ route('register') }}" class="text-amber-800 font-bold hover:underline">Đăng ký ngay</a>
        </div>
    </div>
</div>
@endsection
