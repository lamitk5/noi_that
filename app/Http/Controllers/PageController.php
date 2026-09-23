<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function faq(): View
    {
        $faqs = [
            [
                'question' => 'Mộc An sử dụng những loại gỗ tự nhiên nào?',
                'answer' => 'Mộc An tuyển chọn kỹ lưỡng các dòng gỗ nhập khẩu chính ngạch như Gỗ Sồi Bắc Mỹ (White Oak), Gỗ Tần Bì (Ash), Gỗ Óc Chó (Walnut) và Gỗ Teak, qua quy trình sấy nhiệt tiêu chuẩn chống cong vênh và mối mọt tối đa.',
            ],
            [
                'question' => 'Thời gian giao hàng và chi phí vận chuyển như thế nào?',
                'answer' => 'Thời gian giao hàng nội thành Hà Nội và TP.HCM từ 1-3 ngày làm việc. Các tỉnh thành khác từ 3-5 ngày làm việc. Phí vận chuyển tiêu chuẩn là 50.000₫ và miễn phí cho đơn hàng từ 5.000.000₫ trở lên.',
            ],
            [
                'question' => 'Tôi có được kiểm tra sản phẩm trước khi thanh toán không?',
                'answer' => 'Có. Với tất cả hình thức giao hàng (bao gồm COD và chuyển khoản), khách hàng đều được mở kiện hàng để kiểm tra chất liệu, kiểu dáng và độ hoàn thiện trước khi ký nhận.',
            ],
            [
                'question' => 'Chính sách bảo hành sản phẩm nội thất tại Mộc An?',
                'answer' => 'Toàn bộ sản phẩm nội thất gỗ tại Mộc An được bảo hành chính hãng 24 tháng đối với lỗi kết cấu khung, mối ghép, nứt vỡ tự nhiên và bảo trì trọn đời bề mặt gỗ.',
            ],
            [
                'question' => 'Cách áp dụng mã giảm giá / voucher khi mua hàng?',
                'answer' => 'Tại bước Giỏ hàng hoặc Thanh toán, bạn chỉ cần nhập mã khuyến mãi vào ô "Mã giảm giá / Voucher" và nhấn "Áp dụng". Hệ thống sẽ tự động trừ số tiền giảm vào tổng thanh toán.',
            ],
        ];

        return view('pages.faq', compact('faqs'));
    }

    public function warranty(): View
    {
        return view('pages.warranty');
    }

    public function returnPolicy(): View
    {
        return view('pages.return');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
