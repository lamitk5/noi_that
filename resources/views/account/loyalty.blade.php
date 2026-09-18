@extends('layouts.app')

@section('title', 'Điểm thưởng & Hạng thành viên - Mộc An')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-muted mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}" class="hover:text-primary transition">Tài khoản</a>
        <span>/</span>
        <span class="text-heading font-semibold">Khách hàng thân thiết</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Sidebar Navigation -->
        <div class="md:col-span-1">
            <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-1.5 text-xs font-semibold">
                <a href="{{ route('account.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Hồ sơ cá nhân</span>
                </a>
                <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Lịch sử đơn hàng</span>
                </a>
                <a href="{{ route('account.wishlist') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Sản phẩm yêu thích</span>
                </a>
                <a href="{{ route('account.loyalty') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-primary text-primary-foreground transition shadow-xs">
                    <span>Điểm thưởng & Hạng thẻ</span>
                </a>
                <a href="{{ route('account.notifications') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-body hover:bg-surface-alt hover:text-heading transition">
                    <span>Thông báo</span>
                </a>
            </div>
        </div>

        <!-- Loyalty Dashboard Content -->
        <div class="md:col-span-3 space-y-6">
            <div>
                <h1 class="text-xl font-bold font-display text-heading">Khách hàng thân thiết & Điểm thưởng</h1>
                <p class="text-xs text-muted mt-1">Tích lũy điểm khi mua hàng để thăng hạng thành viên và quy đổi ưu đãi thanh toán.</p>
            </div>

            <!-- Tier Banner & Current Balance Card -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-6 rounded-2xl bg-gradient-to-br from-primary/10 via-surface to-surface border border-primary/20 shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-accent">Hạng thành viên hiện tại</span>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="size-10 rounded-xl bg-primary text-primary-foreground font-display font-bold text-lg grid place-items-center">
                            {{ substr(strtoupper($user->loyalty_tier), 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold font-display text-heading">Hạng {{ $user->tierLabel() }}</h2>
                            <p class="text-xs text-muted">Tích thêm điểm để nhận ưu đãi đặc quyền tiếp theo.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-surface border border-ui-border shadow-xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Số điểm khả dụng</span>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-3xl font-bold font-display text-primary">{{ number_format($user->loyalty_points) }}</span>
                        <span class="text-xs text-muted">điểm Mộc An</span>
                    </div>
                    <p class="text-xs text-muted mt-1">Giá trị quy đổi tương đương: <span class="font-semibold text-heading">{{ number_format($user->loyalty_points * 1000, 0, ',', '.') }}đ</span> giảm giá khi thanh toán.</p>
                </div>
            </div>

            <!-- Tiers Guide -->
            <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-heading">Quy định tích điểm & Các hạng thẻ</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 rounded-xl {{ $user->loyalty_tier === 'bronze' ? 'border-2 border-primary bg-primary/5' : 'border border-ui-border bg-surface-alt/40' }}">
                        <div class="font-bold text-heading">Đồng (Bronze)</div>
                        <div class="text-muted text-[11px] mt-0.5">0 - 999 điểm</div>
                        <div class="text-[11px] text-primary mt-1">Tích 1 điểm / 10k</div>
                    </div>
                    <div class="p-3 rounded-xl {{ $user->loyalty_tier === 'silver' ? 'border-2 border-primary bg-primary/5' : 'border border-ui-border bg-surface-alt/40' }}">
                        <div class="font-bold text-heading">Bạc (Silver)</div>
                        <div class="text-muted text-[11px] mt-0.5">1.000 - 2.999 điểm</div>
                        <div class="text-[11px] text-primary mt-1">Voucher 5% sinh nhật</div>
                    </div>
                    <div class="p-3 rounded-xl {{ $user->loyalty_tier === 'gold' ? 'border-2 border-primary bg-primary/5' : 'border border-ui-border bg-surface-alt/40' }}">
                        <div class="font-bold text-heading">Vàng (Gold)</div>
                        <div class="text-muted text-[11px] mt-0.5">3.000 - 9.999 điểm</div>
                        <div class="text-[11px] text-primary mt-1">Voucher 10% & Miễn ship</div>
                    </div>
                    <div class="p-3 rounded-xl {{ $user->loyalty_tier === 'diamond' ? 'border-2 border-primary bg-primary/5' : 'border border-ui-border bg-surface-alt/40' }}">
                        <div class="font-bold text-heading">Kim Cương (Diamond)</div>
                        <div class="text-muted text-[11px] mt-0.5">10.000+ điểm</div>
                        <div class="text-[11px] text-primary mt-1">Chăm sóc VIP riêng 24/7</div>
                    </div>
                </div>
            </div>

            <!-- Points Transaction History -->
            <div class="p-5 rounded-2xl bg-surface border border-ui-border space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-heading">Lịch sử biến động điểm</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="text-muted font-semibold uppercase tracking-wider text-[11px] border-b border-ui-border">
                                <th class="py-2.5 px-3">Thời gian</th>
                                <th class="py-2.5 px-3">Nội dung</th>
                                <th class="py-2.5 px-3 text-right">Số điểm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ui-border">
                            @forelse($transactions as $trx)
                                <tr>
                                    <td class="py-2.5 px-3 text-muted">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 px-3 text-heading">{{ $trx->description ?? 'Giao dịch điểm Mộc An' }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold {{ $trx->points > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ $trx->points > 0 ? '+' . number_format($trx->points) : number_format($trx->points) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-muted">Chưa có lịch sử giao dịch điểm thưởng nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
