@extends('layouts.app')

@section('title', 'Đăng Ký Tài Khoản Mới')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl border shadow-sm p-8">
        <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">Đăng Ký Tài Khoản</h1>
        <p class="text-xs text-gray-500 text-center mb-6">Trở thành thành viên để nhận các ưu đãi nội thất hấp dẫn</p>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Họ và tên *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Số điện thoại</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Địa chỉ</label>
                <input type="text" name="address" value="{{ old('address') }}" class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Mật khẩu *</label>
                <input type="password" name="password" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
                @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Xác nhận mật khẩu *</label>
                <input type="password" name="password_confirmation" required class="w-full text-sm border rounded-lg p-2.5 outline-none focus:ring-1 focus:ring-amber-700">
            </div>

            <button type="submit" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-bold py-3 rounded-lg transition">
                Đăng Ký
            </button>
        </form>

        <div class="mt-6 pt-4 border-t text-center text-xs text-gray-600">
            Đã có tài khoản? <a href="{{ route('login') }}" class="text-amber-800 font-bold hover:underline">Đăng nhập</a>
        </div>
    </div>
</div>
@endsection
