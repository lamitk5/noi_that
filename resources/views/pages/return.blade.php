@extends('layouts.app')

@section('title', 'Chính sách đổi trả & hoàn tiền | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-16 bg-page">
    <div class="page-shell max-w-4xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Chính sách đổi trả</span>
        </nav>

        <div class="mb-10">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Quyền lợi khách hàng</span>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Chính sách đổi trả & hoàn tiền</h1>
            <p class="mt-3 text-sm text-muted">Đổi trả linh hoạt trong vòng 7 ngày để đảm bảo món đồ hoàn toàn hòa hợp với không gian nhà bạn.</p>
        </div>

        <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs space-y-8 text-body text-sm leading-relaxed">
            <section class="space-y-3">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">1</span>
                    Thời gian áp dụng đổi trả
                </h2>
                <p>Khách hàng được quyền yêu cầu đổi mới hoặc trả hàng hoàn tiền trong vòng <strong>7 ngày</strong> tính từ thời điểm nhận hàng thành công.</p>
            </section>

            <section class="space-y-3 pt-6 border-t border-ui-border">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">2</span>
                    Trường hợp được đổi trả miễn phí
                </h2>
                <ul class="list-disc list-inside space-y-1.5 pl-2 text-muted">
                    <li>Sản phẩm giao không đúng mẫu mã, màu sắc, kích thước so với đơn đặt hàng.</li>
                    <li>Sản phẩm bị trầy xước, nứt vỡ do quá trình vận chuyển của bên Mộc An.</li>
                    <li>Lỗi gia công từ nhà sản xuất phát hiện ngay khi kiểm tra nhận hàng.</li>
                </ul>
            </section>

            <section class="space-y-3 pt-6 border-t border-ui-border">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">3</span>
                    Phương thức hoàn tiền
                </h2>
                <p class="text-muted">Đối với các trường hợp trả hàng hợp lệ, Mộc An hoàn trả 100% giá trị sản phẩm qua tài khoản ngân hàng hoặc ví điện tử của khách hàng trong vòng <strong>2-3 ngày làm việc</strong> sau khi thu hồi sản phẩm về kho.</p>
            </section>
        </div>
    </div>
</div>
@endsection
