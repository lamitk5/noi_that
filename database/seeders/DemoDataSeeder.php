<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@mocan.test'],
            [
                'name' => 'Nguyễn Minh Hải (Admin)',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'loyalty_points' => 5000,
                'loyalty_tier' => 'gold',
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer@mocan.test'],
            [
                'name' => 'Trần Thu Thảo (Khách VIP)',
                'password' => Hash::make('Customer@123'),
                'role' => 'customer',
                'loyalty_points' => 1250,
                'loyalty_tier' => 'silver',
            ]
        );

        // 2. Vouchers
        Voucher::updateOrCreate(
            ['code' => 'MOCAN10'],
            [
                'name' => 'Ưu đãi thành viên mới giảm 10%',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 1000000,
                'max_discount' => 500000,
                'usage_limit' => 500,
                'used_count' => 12,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ]
        );

        Voucher::updateOrCreate(
            ['code' => 'TRIAN200K'],
            [
                'name' => 'Tri ân khách hàng giảm ngay 200.000đ',
                'type' => 'fixed',
                'value' => 200000,
                'min_order_amount' => 3000000,
                'usage_limit' => 200,
                'used_count' => 45,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        // 3. FAQs
        $faqs = [
            [
                'category' => 'delivery',
                'question' => 'Mộc An có hỗ trợ giao hàng và lắp đặt tận nhà không?',
                'answer' => 'Có. Chúng tôi miễn phí vận chuyển cho đơn hàng từ 5.000.000đ và đội ngũ kỹ thuật viên sẽ hỗ trợ bê vác, lắp ráp hoàn thiện tại phòng cho quý khách.',
                'sort_order' => 1,
            ],
            [
                'category' => 'warranty',
                'question' => 'Chính sách bảo hành sản phẩm gỗ tự nhiên như thế nào?',
                'answer' => 'Mọi sản phẩm nội thất gỗ tại Mộc An đều được áp dụng chính sách bảo hành chính hãng 24 tháng cho các lỗi cong vênh, co ngót, nứt tự nhiên hoặc mối mọt.',
                'sort_order' => 2,
            ],
            [
                'category' => 'payment',
                'question' => 'Tôi có thể thanh toán khi nhận hàng (COD) hoặc qua ví điện tử không?',
                'answer' => 'Có. Mộc An hỗ trợ thanh toán khi nhận hàng (COD), Chuyển khoản ngân hàng trực tiếp, cổng thanh toán VNPAY-QR và ví MoMo an toàn tuyệt đối.',
                'sort_order' => 3,
            ],
            [
                'category' => 'return',
                'question' => 'Quy định đổi trả hàng trong vòng mấy ngày?',
                'answer' => 'Khách hàng được quyền đổi sản phẩm khác hoặc trả hàng trong vòng 7 ngày kể từ khi nhận hàng nếu sản phẩm gặp lỗi từ nhà sản xuất hoặc giao sai quy cách.',
                'sort_order' => 4,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(['question' => $faq['question']], array_merge($faq, ['is_active' => true]));
        }

        // 4. CMS Pages
        CmsPage::updateOrCreate(
            ['slug' => 'gioi-thieu'],
            [
                'title' => 'Giới thiệu về Mộc An',
                'content' => "Mộc An ra đời từ niềm đam mê chế tác gỗ truyền thống hòa quyện cùng ngôn ngữ thiết kế tối giản hiện đại.\n\nMỗi sản phẩm là kết tinh của sự tỉ mỉ từ khâu chọn lọc từng thanh gỗ sồi, tần bì nhập khẩu đến lớp sơn hoàn thiện giữ trọn vân gỗ tự nhiên và an toàn cho sức khỏe gia đình.",
                'meta_title' => 'Về Mộc An - Không gian nội thất gỗ bền vững',
                'meta_description' => 'Mộc An chế tác nội thất gỗ tự nhiên cao cấp với phong cách Bắc Âu và tối giản.',
                'is_active' => true,
            ]
        );

        CmsPage::updateOrCreate(
            ['slug' => 'chinh-sach-bao-hanh'],
            [
                'title' => 'Chính sách bảo hành 24 tháng',
                'content' => "1. Thời hạn bảo hành: 24 tháng đối với khung gỗ và phụ kiện bản lề, ray trượt.\n2. Điều kiện bảo hành: Sản phẩm được sử dụng trong nhà theo đúng hướng dẫn của Mộc An.\n3. Thời gian xử lý bảo hành: Kỹ thuật viên sẽ kiểm tra và khắc phục trong vòng 48 giờ kể từ khi tiếp nhận thông tin.",
                'is_active' => true,
            ]
        );

        // 5. Blog Categories & Posts
        $catDesign = PostCategory::updateOrCreate(
            ['slug' => 'xu-huong-thiet-ke'],
            ['name' => 'Xu hướng Thiết kế', 'description' => 'Cập nhật phong cách nội thất mới nhất.']
        );

        $catTips = PostCategory::updateOrCreate(
            ['slug' => 'cam-nang-cham-soc-go'],
            ['name' => 'Cẩm nang Đồ gỗ', 'description' => 'Mẹo bảo dưỡng và giữ gìn nội thất luôn như mới.']
        );

        Post::updateOrCreate(
            ['slug' => '5-bi-quyet-bo-tri-phong-khach-am-cung'],
            [
                'title' => '5 Bí quyết bố trí phòng khách ấm cúng theo phong cách Japandi',
                'post_category_id' => $catDesign->id,
                'author_id' => $admin->id,
                'excerpt' => 'Sự kết hợp giữa nét tinh tế Nhật Bản và sự ấm áp Bắc Âu tạo nên không gian phòng khách thanh bình.',
                'content' => "Phong cách Japandi là sự giao thoa hoàn hảo giữa hai nền văn hóa thiết kế: sự mộc mạc Wabi-sabi của Nhật Bản và tinh thần Hygge ấm cúng của Bắc Âu.\n\n1. Sử dụng tông màu trung tính ấm áp (be, kem, nâu nhạt).\n2. Lựa chọn bàn ghế chân thon gọn từ gỗ sồi tự nhiên.\n3. Tối ưu ánh sáng tự nhiên và điểm xuyết cây xanh trong nhà.",
                'featured_image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80',
                'view_count' => 128,
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ]
        );

        Post::updateOrCreate(
            ['slug' => 'cach-ve-sinh-ban-an-go-soi-luon-sang-bong'],
            [
                'title' => 'Cách vệ sinh và bảo dưỡng bàn ăn gỗ sồi luôn sáng bóng như mới',
                'post_category_id' => $catTips->id,
                'author_id' => $admin->id,
                'excerpt' => 'Hướng dẫn chi tiết cách lau chùi và xử lý vết ố trên bề mặt gỗ tự nhiên mà không làm hỏng lớp bảo vệ.',
                'content' => "Bàn ăn gỗ sồi là tâm điểm của căn bếp gia đình. Để giữ được độ bóng đẹp theo thời gian:\n\n- Dùng khăn ẩm mềm vắt ráo nước để lau sau mỗi bữa ăn.\n- Tránh để trực tiếp nồi nóng hoặc cốc nước đá lên mặt gỗ mà không có đế lót.\n- Thoa sáp ong dưỡng gỗ định kỳ 6 tháng một lần.",
                'featured_image' => 'https://images.unsplash.com/photo-1532372320572-cda25653a694?auto=format&fit=crop&w=1200&q=80',
                'view_count' => 95,
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ]
        );

        // 6. Support Tickets
        SupportTicket::updateOrCreate(
            ['ticket_code' => 'TK-DEMO001'],
            [
                'user_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'subject' => 'Tư vấn chọn kích thước bàn ăn cho căn hộ 65m2',
                'category' => 'product',
                'priority' => 'normal',
                'status' => 'resolved',
                'message' => 'Chào shop, phòng khách liền bếp nhà mình khoảng 20m2 thì nên chọn bàn ăn 4 ghế hay 6 ghế để không bị chật?',
                'last_reply_at' => now()->subDay(),
            ]
        );

        // 7. Orders & Reviews
        $product = Product::with('variants')->first();
        if ($product && $product->variants->isNotEmpty()) {
            $variant = $product->variants->first();

            $order = Order::updateOrCreate(
                ['order_code' => 'ORD-DEMO-2026'],
                [
                    'user_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone' => '0912345678',
                    'customer_email' => $customer->email,
                    'shipping_address' => 'Số 123 Đường Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
                    'note' => 'Giao hàng giờ hành chính',
                    'total_price' => $variant->price,
                    'shipping_fee' => 0,
                    'payment_method' => 'vnpay',
                    'payment_status' => 'paid',
                    'order_status' => 'completed',
                    'shipping_carrier' => 'ghn',
                    'tracking_code' => 'GHN-DEMO998877',
                    'ghn_order_code' => 'GHN-DEMO998877',
                    'shipping_status' => 'delivered',
                    'to_district_id' => 1442,
                    'to_ward_code' => '20101',
                    'shipped_at' => now()->subDays(3),
                ]
            );

            $orderItem = OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_variant_id' => $variant->id],
                [
                    'product_name' => $product->name,
                    'variant_info' => $variant->color . ' / ' . $variant->size,
                    'price' => $variant->price,
                    'quantity' => 1,
                ]
            );

            Review::updateOrCreate(
                ['user_id' => $customer->id, 'product_id' => $product->id],
                [
                    'order_item_id' => $orderItem->id,
                    'rating' => 5,
                    'comment' => 'Sản phẩm hoàn thiện rất tỉ mỉ, bề mặt gỗ sồi sờ mịn và thơm mùi gỗ tự nhiên. Giao hàng đóng gói bọc xốp cẩn thận!',
                ]
            );
        }
    }
}
