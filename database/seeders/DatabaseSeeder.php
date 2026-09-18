<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Users (Admin & Customer)
        $admin = User::firstOrCreate(
            ['email' => 'admin@furniture.com'],
            [
                'name' => 'Quản Trị Viên Nội Thất',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '0901234567',
                'address' => 'Tòa nhà Landmark 81, Quận Bình Thạnh, TP. Hồ Chí Minh',
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Nguyễn Văn An',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'phone' => '0987654321',
                'address' => 'Số 123 Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội',
            ]
        );

        // 2. Seed Categories
        $categoriesData = [
            [
                'name' => 'Nội thất Phòng khách',
                'slug' => 'phong-khach',
                'description' => 'Bộ sưu tập sofa da cao cấp, bàn trà mặt đá ceramic, kệ tivi phong cách hiện đại.',
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 1,
            ],
            [
                'name' => 'Nội thất Phòng ngủ',
                'slug' => 'phong-ngu',
                'description' => 'Giường ngủ gỗ sồi tự nhiên, tủ quần áo cánh kính sang trọng, bàn trang điểm Bắc Âu.',
                'image' => 'https://images.unsplash.com/photo-1540518614846-7ede433c4ef7?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 2,
            ],
            [
                'name' => 'Nội thất Phòng ăn & Bếp',
                'slug' => 'phong-an-bep',
                'description' => 'Bàn ăn thông minh mở rộng, bộ bàn ghế ăn gỗ óc chó cao cấp cho không gian ấm cúng.',
                'image' => 'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 3,
            ],
            [
                'name' => 'Nội thất Phòng làm việc',
                'slug' => 'phong-lam-viec',
                'description' => 'Bàn làm việc gỗ nguyên tấm, ghế công thái học bảo vệ cột sống, tủ tài liệu tiện nghi.',
                'image' => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 4,
            ],
            [
                'name' => 'Đèn & Đồ Trang trí',
                'slug' => 'den-trang-tri',
                'description' => 'Đèn chùm pha lê, thảm dệt tay Thổ Nhĩ Kỳ, gương nghệ thuật tạo điểm nhấn thẩm mỹ.',
                'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=800&q=80',
                'sort_order' => 5,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = Category::create($cat);
        }

        // 3. Seed Products with realistic Vietnamese furniture specifications
        $productsData = [
            // Phòng khách
            [
                'category_slug' => 'phong-khach',
                'name' => 'Sofa Góc Chữ L Bọc Da Bò Ý Cao Cấp Milano',
                'sku' => 'SOFA-MILANO-01',
                'short_description' => 'Sofa góc bọc 100% da bò nguyên tấm nhập khẩu từ Ý, khung gỗ thông Chile sấy khô chống mối mọt.',
                'description' => 'Sofa Milano mang phong cách Ý lịch lãm với đường may tỉ mỉ, đệm mút D40 kết hợp lông vũ siêu êm ái. Khung chịu lực bằng gỗ tự nhiên có độ bền trên 15 năm. Phù hợp cho phòng khách chung cư cao cấp và biệt thự.',
                'price' => 28500000,
                'sale_price' => 25900000,
                'stock_quantity' => 12,
                'material' => 'Da bò Ý thật, Khung gỗ thông Chile',
                'dimensions' => '280cm x 170cm x 85cm',
                'color' => 'Nâu da bò Cognac',
                'weight' => 85.0,
                'is_featured' => true,
                'views_count' => 1420,
                'images' => [
                    'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
            [
                'category_slug' => 'phong-khach',
                'name' => 'Bàn Trà Đôi Mặt Đá Ceramic Chống Trầy Xước Venus',
                'sku' => 'TEA-VENUS-02',
                'short_description' => 'Bộ đôi bàn trà tròn mặt đá phiến chống trầy, chống ố nhiệt, khung inox 304 mạ PVD vàng titan.',
                'description' => 'Bàn trà đôi lồng ghép thông minh giúp tiết kiệm không gian. Mặt đá ceramic dày 12mm chịu nhiệt tới 1200 độ C, dễ dàng lau chùi mọi vết bẩn cứng đầu.',
                'price' => 6800000,
                'sale_price' => 5990000,
                'stock_quantity' => 25,
                'material' => 'Đá Ceramic, Inox 304 mạ PVD',
                'dimensions' => 'Bàn lớn D80cm x H45cm, Bàn nhỏ D60cm x H40cm',
                'color' => 'Trắng vân mây & Chân mạ vàng',
                'weight' => 32.0,
                'is_featured' => true,
                'views_count' => 890,
                'images' => [
                    'https://images.unsplash.com/photo-1533090161767-e6ffed986c88?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
            [
                'category_slug' => 'phong-khach',
                'name' => 'Kệ Tivi Gỗ Óc Chó Bắc Mỹ Tối Giản Horizon',
                'sku' => 'TV-HORIZON-03',
                'short_description' => 'Kệ tivi thiết kế phong cách Minimalist làm từ gỗ óc chó tự nhiên FAS.',
                'description' => 'Được gia công từ gỗ óc chó nhập khẩu Bắc Mỹ với đường vân núi uốn lượn đặc trưng. Bản lề giảm chấn Hafele êm ái, phủ sơn mờ PU gốc nước an toàn sức khỏe.',
                'price' => 14500000,
                'sale_price' => null,
                'stock_quantity' => 8,
                'material' => 'Gỗ óc chó Bắc Mỹ (Walnut)',
                'dimensions' => '200cm x 40cm x 45cm',
                'color' => 'Nâu hạt dẻ tự nhiên',
                'weight' => 45.0,
                'is_featured' => false,
                'views_count' => 610,
                'images' => [
                    'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=1000&q=80',
                ],
            ],

            // Phòng ngủ
            [
                'category_slug' => 'phong-ngu',
                'name' => 'Giường Ngủ Gỗ Sồi Tự Nhiên Bắc Âu Scandinavian Nordic',
                'sku' => 'BED-NORDIC-04',
                'short_description' => 'Giường ngủ phong cách Scandinavian làm từ 100% gỗ sồi Nga tự nhiên đã qua tẩm sấy.',
                'description' => 'Khung giường chắc chắn, giát phản nguyên tấm chịu tải lên đến 500kg. Đầu giường vát cong 15 độ ôm trọn tư thế tựa lưng đọc sách hay thư giãn.',
                'price' => 12900000,
                'sale_price' => 11500000,
                'stock_quantity' => 15,
                'material' => 'Gỗ sồi Nga tự nhiên (Oak)',
                'dimensions' => '180cm x 200cm x 95cm',
                'color' => 'Gỗ tự nhiên sáng vân gỗ',
                'weight' => 70.0,
                'is_featured' => true,
                'views_count' => 2100,
                'images' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
            [
                'category_slug' => 'phong-ngu',
                'name' => 'Tủ Quần Áo Cánh Kính Cường Lực Tích Hợp Đèn LED Luxe',
                'sku' => 'WARD-LUXE-05',
                'short_description' => 'Tủ áo 4 cánh kính màu trà chống tia UV, cảm biến mở cửa phát sáng đèn LED thông minh.',
                'description' => 'Khung nhôm định hình anode cao cấp kết hợp thùng gỗ MDF lõi xanh chống ẩm An Cường. Đầy đủ khoang treo đầm dài, hộc kéo phụ kiện có khóa vân tay.',
                'price' => 22000000,
                'sale_price' => 19800000,
                'stock_quantity' => 4,
                'material' => 'Kính cường lực màu trà, MDF chống ẩm An Cường, Nhôm Anode',
                'dimensions' => '220cm x 60cm x 240cm',
                'color' => 'Xám khói & Kính màu trà',
                'weight' => 110.0,
                'is_featured' => true,
                'views_count' => 970,
                'images' => [
                    'https://images.unsplash.com/photo-1558997519-83ea9252edf8?auto=format&fit=crop&w=1000&q=80',
                ],
            ],

            // Phòng ăn & Bếp
            [
                'category_slug' => 'phong-an-bep',
                'name' => 'Bộ Bàn Ăn Thông Minh Kéo Dài 6-10 Ghế Concorde',
                'sku' => 'DIN-CONCORDE-06',
                'short_description' => 'Bàn ăn thông minh kéo dài từ 1m6 đến 2m4 bằng ray trượt trợ lực cơ học.',
                'description' => 'Mặt bàn đá nhân tạo vân Calacatta chống bám dầu mỡ. Kèm theo 6 ghế bọc da nappa cao cấp có tựa lưng êm ái, chân thép carbon sơn tĩnh điện chịu lực.',
                'price' => 18900000,
                'sale_price' => 16900000,
                'stock_quantity' => 9,
                'material' => 'Mặt đá nung kết, Khung thép carbon, Ghế bọc da nappa',
                'dimensions' => '160cm - 240cm x 90cm x 76cm',
                'color' => 'Trắng vân mây xám, Ghế màu cam Hermès',
                'weight' => 95.0,
                'is_featured' => true,
                'views_count' => 1840,
                'images' => [
                    'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
            [
                'category_slug' => 'phong-an-bep',
                'name' => 'Ghế Ăn Tựa Cong Gỗ Tần Bì Bọc Đệm Nỉ Monet',
                'sku' => 'CHAIR-MONET-07',
                'short_description' => 'Ghế ăn lưng cong chữ U mềm mại, đệm bọc vải nỉ nhung cao cấp chống bám bụi.',
                'description' => 'Khung ghế làm bằng gỗ tần bì (Ash) uốn nhiệt nguyên khối tạo đường cong hoàn mỹ. Thích hợp cho phòng ăn phong cách Retro hoặc Modern Luxury.',
                'price' => 1850000,
                'sale_price' => 1550000,
                'stock_quantity' => 30,
                'material' => 'Gỗ tần bì tự nhiên, Nỉ nhung Hàn Quốc',
                'dimensions' => '52cm x 54cm x 78cm',
                'color' => 'Xanh rêu cổ điển',
                'weight' => 7.5,
                'is_featured' => false,
                'views_count' => 530,
                'images' => [
                    'https://images.unsplash.com/photo-1580481077195-c3a821a58875?auto=format&fit=crop&w=1000&q=80',
                ],
            ],

            // Phòng làm việc
            [
                'category_slug' => 'phong-lam-viec',
                'name' => 'Bàn Làm Việc Giám Đốc Gỗ Tự Nhiên Master Executive',
                'sku' => 'DESK-MASTER-08',
                'short_description' => 'Bàn làm việc đẳng cấp tích hợp cổng sạc không dây và ổ cắm âm bàn xoay tự động.',
                'description' => 'Mặt bàn bo cạnh thẩm mỹ, kết hợp da PU cao cấp vùng viết ký tài liệu. Hệ thống ngăn kéo ray bi 3 tầng bền bỉ, khóa bảo mật bằng mã số điện tử.',
                'price' => 15500000,
                'sale_price' => null,
                'stock_quantity' => 6,
                'material' => 'Gỗ công nghiệp HDF phủ Melamine cao cấp, Da PU',
                'dimensions' => '180cm x 80cm x 75cm',
                'color' => 'Nâu vân gỗ sồi đậm & Đen nhám',
                'weight' => 68.0,
                'is_featured' => true,
                'views_count' => 780,
                'images' => [
                    'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
            [
                'category_slug' => 'phong-lam-viec',
                'name' => 'Ghế Công Thái Học Lưới Toàn Thần Ergonomic Pro X',
                'sku' => 'CHAIR-ERGO-09',
                'short_description' => 'Ghế công thái học đỡ hõm lưng điều chỉnh 3D, piston Class 4 an toàn đạt chuẩn BIFMA.',
                'description' => 'Lưới Wintex Hàn Quốc thoáng khí đàn hồi cao, ngả lưng tới 135 độ có kê chân gấp gọn tiện lợi ngủ trưa. Tay vịn nâng hạ xoay 4 hướng.',
                'price' => 5200000,
                'sale_price' => 4690000,
                'stock_quantity' => 20,
                'material' => 'Lưới Wintex cao cấp, Chân hợp kim nhôm',
                'dimensions' => '65cm x 65cm x 115-125cm',
                'color' => 'Xám không gian (Space Gray)',
                'weight' => 21.0,
                'is_featured' => true,
                'views_count' => 3120,
                'images' => [
                    'https://images.unsplash.com/photo-1580481077195-c3a821a58875?auto=format&fit=crop&w=1000&q=80',
                ],
            ],

            // Đèn & Trang trí
            [
                'category_slug' => 'den-trang-tri',
                'name' => 'Đèn Chùm Pha Lê K9 Bắc Âu Độc Bản Halo Glow',
                'sku' => 'LAMP-HALO-10',
                'short_description' => 'Đèn chùm pha lê K9 đa tầng khúc xạ ánh sáng 3 màu, điều khiển remote từ xa.',
                'description' => 'Thân đèn bằng đồng thau nguyên chất mạ điện bóng bẩy. Hạt pha lê K9 độ trong suốt tuyệt hảo tạo hiệu ứng ánh sáng lung linh rực rỡ.',
                'price' => 8900000,
                'sale_price' => 7900000,
                'stock_quantity' => 7,
                'material' => 'Pha lê K9, Thân đồng thau nguyên chất',
                'dimensions' => 'Đường kính 80cm x Chiều cao 60cm (xích treo 50cm)',
                'color' => 'Vàng kim loại bóng',
                'weight' => 14.0,
                'is_featured' => false,
                'views_count' => 640,
                'images' => [
                    'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1000&q=80',
                ],
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $prod) {
            $catSlug = $prod['category_slug'];
            $images = $prod['images'];
            unset($prod['category_slug'], $prod['images']);

            $prod['category_id'] = $categories[$catSlug]->id;
            $prod['slug'] = Str::slug($prod['name']);

            $product = Product::create($prod);
            $createdProducts[] = $product;

            $isFirst = true;
            foreach ($images as $index => $imgPath) {
                $product->images()->create([
                    'image_path' => $imgPath,
                    'is_primary' => $isFirst,
                    'sort_order' => $index,
                ]);
                $isFirst = false;
            }
        }

        // 4. Seed Sample Orders for Dashboard Metrics & Testing
        $order1 = Order::create([
            'order_number' => 'ORD-20260910-AA1001',
            'user_id' => $customer->id,
            'customer_name' => 'Nguyễn Văn An',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0987654321',
            'shipping_address' => 'Số 123 Đường Cầu Giấy, Quận Cầu Giấy, Hà Nội',
            'subtotal' => 25900000,
            'shipping_fee' => 0.0,
            'discount_amount' => 0.0,
            'total_amount' => 25900000,
            'payment_method' => 'banking',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_COMPLETED,
            'notes' => 'Giao hàng vào giờ hành chính, lắp đặt tầng 5.',
        ]);

        $order1->items()->create([
            'product_id' => $createdProducts[0]->id,
            'product_name' => $createdProducts[0]->name,
            'product_sku' => $createdProducts[0]->sku,
            'price' => 25900000,
            'quantity' => 1,
            'total' => 25900000,
        ]);

        $order2 = Order::create([
            'order_number' => 'ORD-20260914-BB2002',
            'user_id' => null, // Guest checkout
            'customer_name' => 'Trần Thị Mai',
            'customer_email' => 'tranmai@gmail.com',
            'customer_phone' => '0912345678',
            'shipping_address' => 'Biệt thự BT2, Khu đô thị Ciputra, Tây Hồ, Hà Nội',
            'subtotal' => 10680000,
            'shipping_fee' => 0.0,
            'discount_amount' => 0.0,
            'total_amount' => 10680000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_SHIPPING,
            'notes' => 'Gọi điện trước khi giao 30 phút.',
        ]);

        $order2->items()->create([
            'product_id' => $createdProducts[1]->id,
            'product_name' => $createdProducts[1]->name,
            'product_sku' => $createdProducts[1]->sku,
            'price' => 5990000,
            'quantity' => 1,
            'total' => 5990000,
        ]);

        $order2->items()->create([
            'product_id' => $createdProducts[8]->id,
            'product_name' => $createdProducts[8]->name,
            'product_sku' => $createdProducts[8]->sku,
            'price' => 4690000,
            'quantity' => 1,
            'total' => 4690000,
        ]);

        $order3 = Order::create([
            'order_number' => 'ORD-20260916-CC3003',
            'user_id' => null,
            'customer_name' => 'Lê Hoàng Nam',
            'customer_email' => 'hoangnam.le@gmail.com',
            'customer_phone' => '0933889900',
            'shipping_address' => 'Chung cư Masteri Thảo Điền, TP. Thủ Đức, TP. Hồ Chí Minh',
            'subtotal' => 1550000,
            'shipping_fee' => 50000.0,
            'discount_amount' => 0.0,
            'total_amount' => 1600000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
            'notes' => 'Giao hàng sau 18h tối.',
        ]);

        $order3->items()->create([
            'product_id' => $createdProducts[6]->id,
            'product_name' => $createdProducts[6]->name,
            'product_sku' => $createdProducts[6]->sku,
            'price' => 1550000,
            'quantity' => 1,
            'total' => 1550000,
        ]);
    }
}
