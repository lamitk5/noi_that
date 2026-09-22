@extends('layouts.app')

@section('title', 'Sổ địa chỉ nhận hàng | Mộc An')

@section('content')
<div class="min-h-[70vh] py-16 px-4 sm:px-6 lg:px-8 bg-page">
    <div class="max-w-4xl mx-auto" x-data="{ showModal: false, editingAddress: null }">
        <!-- Breadcrumb & Title -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="mb-2 flex items-center gap-2 text-xs text-muted" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-heading transition">Trang chủ</a>
                    <span>/</span>
                    <a href="{{ route('account.index') }}" class="hover:text-heading transition">Tài khoản</a>
                    <span>/</span>
                    <span class="text-heading font-medium" aria-current="page">Sổ địa chỉ</span>
                </nav>
                <h1 class="font-display text-2xl sm:text-3xl font-semibold text-heading">Sổ địa chỉ nhận hàng</h1>
                <p class="mt-1 text-sm text-muted">Quản lý các địa chỉ giao hàng để thanh toán nhanh hơn.</p>
            </div>
            <button @click="editingAddress = null; showModal = true" class="btn-primary inline-flex items-center gap-2">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Thêm địa chỉ mới</span>
            </button>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-3">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 p-4 text-sm font-medium text-rose-600 dark:text-rose-400">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Address List -->
        @if ($addresses->isEmpty())
            <div class="rounded-3xl border border-ui-border bg-surface p-12 text-center shadow-sm">
                <div class="size-16 rounded-full bg-primary/10 text-primary mx-auto grid place-items-center mb-4">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </div>
                <h3 class="font-display text-lg font-bold text-heading">Chưa có địa chỉ nào</h3>
                <p class="text-sm text-muted mt-1 max-w-md mx-auto">Thêm địa chỉ nhận hàng để không cần phải nhập lại thông tin mỗi lần mua sắm tại Mộc An.</p>
                <button @click="editingAddress = null; showModal = true" class="mt-6 btn-primary inline-flex items-center gap-2">
                    <span>Thêm địa chỉ đầu tiên</span>
                </button>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($addresses as $addr)
                    <div class="rounded-3xl border {{ $addr->is_default ? 'border-primary/40 bg-primary/[0.02]' : 'border-ui-border bg-surface' }} p-6 shadow-sm flex flex-col justify-between transition hover:border-primary/60">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="font-bold text-base text-heading">{{ $addr->recipient_name }}</h3>
                                    <p class="text-xs text-muted mt-0.5">{{ $addr->phone }}</p>
                                </div>
                                @if ($addr->is_default)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-bold text-primary">
                                        <svg class="size-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Mặc định
                                    </span>
                                @endif
                            </div>

                            <p class="text-sm text-body leading-relaxed">
                                {{ $addr->address_line }}<br>
                                {{ collect([$addr->ward, $addr->district, $addr->city])->filter()->implode(', ') }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-ui-border/60 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                <button 
                                    @click="editingAddress = {{ json_encode($addr) }}; showModal = true"
                                    class="text-primary hover:underline font-semibold"
                                >
                                    Sửa
                                </button>
                                <form action="{{ route('account.addresses.destroy', $addr->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:underline font-semibold">Xóa</button>
                                </form>
                            </div>

                            @if (! $addr->is_default)
                                <form action="{{ route('account.addresses.set-default', $addr->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-muted hover:text-heading font-medium">Thiết lập mặc định</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Add / Edit Modal -->
        <div 
            x-show="showModal" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @keydown.escape.window="showModal = false"
        >
            <div 
                @click.away="showModal = false"
                class="w-full max-w-lg rounded-3xl bg-surface p-6 sm:p-8 shadow-2xl border border-ui-border max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-display text-xl font-bold text-heading" x-text="editingAddress ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ mới'"></h2>
                    <button @click="showModal = false" class="text-muted hover:text-heading">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="editingAddress ? '/tai-khoan/dia-chi/' + editingAddress.id : '{{ route('account.addresses.store') }}'" method="POST">
                    @csrf
                    <template x-if="editingAddress">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Họ và tên người nhận *</label>
                            <input type="text" name="recipient_name" required :value="editingAddress ? editingAddress.recipient_name : '{{ old('recipient_name', auth()->user()->name) }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Số điện thoại *</label>
                            <input type="tel" name="phone" required :value="editingAddress ? editingAddress.phone : '{{ old('phone', auth()->user()->phone ?? '') }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Tỉnh / Thành phố *</label>
                            <input type="text" name="city" required placeholder="Ví dụ: Hà Nội, TP. Hồ Chí Minh, Đà Nẵng..." :value="editingAddress ? editingAddress.city : '{{ old('city') }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Quận / Huyện</label>
                                <input type="text" name="district" placeholder="Ví dụ: Cầu Giấy..." :value="editingAddress ? editingAddress.district : '{{ old('district') }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Phường / Xã</label>
                                <input type="text" name="ward" placeholder="Ví dụ: Dịch Vọng..." :value="editingAddress ? editingAddress.ward : '{{ old('ward') }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">Số nhà, tên đường chi tiết *</label>
                            <input type="text" name="address_line" required placeholder="Ví dụ: 123 Đường Cầu Giấy..." :value="editingAddress ? editingAddress.address_line : '{{ old('address_line') }}'" class="w-full rounded-xl border border-ui-border bg-surface-alt px-4 py-2.5 text-sm text-heading focus:border-primary focus:outline-none">
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="modal_is_default" name="is_default" value="1" :checked="editingAddress ? editingAddress.is_default : false" class="size-4 rounded border-ui-border text-primary focus:ring-primary">
                            <label for="modal_is_default" class="text-sm text-body cursor-pointer">Đặt làm địa chỉ nhận hàng mặc định</label>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-3">
                        <button type="button" @click="showModal = false" class="btn-secondary">Hủy</button>
                        <button type="submit" class="btn-primary">Lưu địa chỉ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
