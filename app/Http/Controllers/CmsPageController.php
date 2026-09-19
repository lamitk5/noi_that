<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = CmsPage::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        $defaults = [
            'gioi-thieu' => [
                'title' => 'Giới thiệu về Mộc An',
                'content' => 'Chào mừng bạn đến với Mộc An - Không gian nội thất gỗ tinh tế, ấm cúng và bền vững. Chúng tôi tự hào mang đến những sản phẩm nội thất được chế tác từ gỗ tự nhiên chất lượng cao, kết hợp hài hòa giữa nét truyền thống và hơi thở hiện đại.',
            ],
            'chinh-sach-mua-hang' => [
                'title' => 'Chính sách mua hàng',
                'content' => 'Tại Mộc An, quý khách có thể mua sắm trực tiếp trên website hoặc liên hệ hotline. Chúng tôi hỗ trợ nhiều phương thức thanh toán an toàn như COD, Chuyển khoản, VNPAY, MoMo.',
            ],
            'chinh-sach-bao-hanh' => [
                'title' => 'Chính sách bảo hành',
                'content' => 'Tất cả sản phẩm nội thất gỗ của Mộc An được bảo hành chính hãng 24 tháng đối với lỗi kỹ thuật do nhà sản xuất (cong vênh, mối mọt, nứt gãy trong điều kiện sử dụng bình thường).',
            ],
            'chinh-sach-doi-tra' => [
                'title' => 'Chính sách đổi trả',
                'content' => 'Quý khách được đổi trả miễn phí trong vòng 7 ngày kể từ ngày nhận hàng nếu sản phẩm bị lỗi sản xuất hoặc sai quy cách đã đặt hàng.',
            ],
        ];

        if (! $page && isset($defaults[$slug])) {
            $page = new CmsPage([
                'slug' => $slug,
                'title' => $defaults[$slug]['title'],
                'content' => $defaults[$slug]['content'],
                'is_active' => true,
            ]);
        }

        if (! $page) {
            abort(404);
        }

        return view('pages.show', compact('page'));
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên của bạn.',
            'email.required' => 'Vui lòng nhập địa chỉ email liên hệ.',
            'subject.required' => 'Vui lòng nhập chủ đề liên hệ.',
            'message.required' => 'Vui lòng nhập lời nhắn chi tiết.',
        ]);

        SupportTicket::create([
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'subject' => '[Liên hệ] ' . $validated['subject'],
            'category' => 'general',
            'priority' => 'normal',
            'status' => SupportTicket::STATUS_OPEN,
            'message' => $validated['message'],
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'Cảm ơn bạn đã gửi lời nhắn! Mộc An sẽ liên hệ lại với bạn trong thời gian sớm nhất.');
    }
}
