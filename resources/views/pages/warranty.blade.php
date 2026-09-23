@extends('layouts.app')

@section('title', 'Chính sách bảo hành & bảo trì | Mộc An')

@section('content')
<div class="min-h-[70vh] py-10 sm:py-16 bg-page">
    <div class="page-shell max-w-4xl">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-heading transition-colors">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <span class="text-heading font-medium" aria-current="page">Chính sách bảo hành</span>
        </nav>

        <div class="mb-10">
            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent">Cam kết chất lượng</span>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl font-semibold text-heading">Chính sách bảo hành & bảo trì</h1>
            <p class="mt-3 text-sm text-muted">Mộc An cam kết mang lại sự an tâm tuyệt đối trong suốt quá trình đồng hành cùng tổ ấm của bạn.</p>
        </div>

        <div class="rounded-3xl border border-ui-border bg-surface p-6 sm:p-10 shadow-xs space-y-8 text-body text-sm leading-relaxed">
            <section class="space-y-3">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">1</span>
                    Thời hạn bảo hành
                </h2>
                <p>Mộc An bảo hành chính hãng <strong>24 tháng</strong> kể từ ngày bàn giao thành công sản phẩm đối với toàn bộ các dòng sản phẩm gỗ tự nhiên, sofa và bàn ghế nội thất.</p>
                <ul class="list-disc list-inside space-y-1.5 pl-2 text-muted">
                    <li>Khung sườn gỗ, kết cấu mộng nối: 24 tháng.</li>
                    <li>Đệm mút sofa, lò xo chịu lực: 18 tháng.</li>
                    <li>Phụ kiện ray trượt, bản lề cao cấp: 12 tháng.</li>
                </ul>
            </section>

            <section class="space-y-3 pt-6 border-t border-ui-border">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">2</span>
                    Điều kiện được bảo hành miễn phí
                </h2>
                <ul class="list-disc list-inside space-y-1.5 pl-2 text-muted">
                    <li>Sản phẩm còn trong thời hạn bảo hành căn cứ theo Mã đơn hàng hoặc Số điện thoại đặt hàng.</li>
                    <li>Lỗi kỹ thuật xuất phát từ vật liệu gỗ, mối nối, kỹ thuật lắp ráp của Mộc An.</li>
                    <li>Sản phẩm cong vênh, co ngót bất thường trong điều kiện sử dụng gia đình bình thường.</li>
                </ul>
            </section>

            <section class="space-y-3 pt-6 border-t border-ui-border">
                <h2 class="font-display text-xl font-semibold text-heading flex items-center gap-2.5">
                    <span class="size-7 rounded-lg bg-primary/10 text-primary font-bold text-xs grid place-items-center">3</span>
                    Quy trình yêu cầu bảo hành
                </h2>
                <ol class="list-decimal list-inside space-y-2 pl-2 text-muted">
                    <li>Khách hàng chụp ảnh/quay video hiện trạng lỗi gửi qua Hotline/Zalo <strong>0901 234 567</strong> hoặc email <strong>cskh@mocan.vn</strong>.</li>
                    <li>Kỹ thuật viên Mộc An tiếp nhận và phản hồi phương án xử lý trong vòng <strong>24 giờ làm việc</strong>.</li>
                    <li>Kỹ thuật viên đến tận nhà thẩm định và tiến hành bảo dưỡng, sửa chữa hoặc đổi mới phụ kiện.</li>
                </ol>
            </section>
        </div>
    </div>
</div>
@endsection
