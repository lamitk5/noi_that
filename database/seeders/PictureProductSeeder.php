<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed products from storage/picture — STRICTLY one product per image file.
 *
 * Rules:
 * - Wipes products / images / variants / reviews before insert
 * - Product count == image file count (~41)
 * - Categories are interleaved so listings don't cluster same room/color
 * - DB stores picture/{filename}; media route serves the file
 */
class PictureProductSeeder extends Seeder
{
    private const PICTURE_DIR = 'picture';

    /**
     * filename => [name, category slug, base price VND, material, dimensions, colors, short]
     *
     * Display order follows this map order after interleave pass — categories
     * are deliberately mixed (sofa → dining → light → storage → bedroom…).
     */
    private const CATALOG = [
        'ghế sofa.jpg' => [
            'name' => 'Ghế Sofa Đơn',
            'category' => 'phong-khach',
            'price' => 5_490_000,
            'material' => 'Khung gỗ + vải',
            'dimensions' => '90 x 85 x 80 cm',
            'colors' => ['Kem', 'Xám'],
            'short' => 'Ghế sofa đơn phom rộng, ngồi êm và dễ phối trộn.',
        ],
        'bàn ăn.jpg' => [
            'name' => 'Bàn Ăn Gỗ Sồi',
            'category' => 'phong-an',
            'price' => 8_990_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '160 x 80 x 75 cm',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'short' => 'Bàn ăn gỗ tự nhiên cho 6–8 người.',
        ],
        'đèn chùm.jpg' => [
            'name' => 'Đèn Chùm',
            'category' => 'den-va-phu-kien',
            'price' => 4_590_000,
            'material' => 'Kim loại + pha lê',
            'dimensions' => 'Ø60 x 50 cm',
            'colors' => ['Vàng đồng', 'Đen'],
            'short' => 'Đèn chùm sang trọng cho bàn ăn và phòng khách.',
        ],
        'tủ quần áo.jpg' => [
            'name' => 'Tủ Quần Áo',
            'category' => 'phong-ngu',
            'price' => 9_890_000,
            'material' => 'Gỗ công nghiệp + veneer',
            'dimensions' => '200 x 60 x 220 cm',
            'colors' => ['Gỗ sáng', 'Trắng'],
            'short' => 'Tủ quần áo 3 buồng, thanh treo và kệ gấp.',
        ],
        'kệ tivi.jpg' => [
            'name' => 'Kệ Tivi',
            'category' => 'phong-khach',
            'price' => 3_990_000,
            'material' => 'Gỗ MDF + veneer',
            'dimensions' => '180 x 40 x 45 cm',
            'colors' => ['Gỗ sáng', 'Trắng'],
            'short' => 'Kệ tivi hiện đại có ngăn kéo giấu dây.',
        ],
        'tủ bếp.jpg' => [
            'name' => 'Tủ Bếp',
            'category' => 'phong-an',
            'price' => 15_900_000,
            'material' => 'MDF chống ẩm + acrylic',
            'dimensions' => 'Dài 3m (module)',
            'colors' => ['Trắng', 'Ghi'],
            'short' => 'Module tủ bếp hiện đại, bề mặt chống ẩm.',
        ],
        'đèn cây đứng.webp' => [
            'name' => 'Đèn Cây Đứng',
            'category' => 'den-va-phu-kien',
            'price' => 1_890_000,
            'material' => 'Kim loại + vải',
            'dimensions' => 'Ø35 x 160 cm',
            'colors' => ['Đen', 'Trắng'],
            'short' => 'Đèn cây đứng góc sofa, ánh sáng dịu.',
        ],
        'giường ngủ.jpg' => [
            'name' => 'Giường Ngủ Gỗ Sồi',
            'category' => 'phong-ngu',
            'price' => 11_900_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '180 x 200 cm',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'short' => 'Giường ngủ khung gỗ chắc chắn, đầu giường tối giản.',
        ],
        'bàn trà.webp' => [
            'name' => 'Bàn Trà Tròn',
            'category' => 'phong-khach',
            'price' => 3_290_000,
            'material' => 'Gỗ sồi',
            'dimensions' => 'Ø80 x 42 cm',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'short' => 'Bàn trà bo tròn an toàn, hợp sofa hiện đại.',
        ],
        'đảo bếp.jpg' => [
            'name' => 'Đảo Bếp',
            'category' => 'phong-an',
            'price' => 8_490_000,
            'material' => 'Gỗ + đá nhân tạo',
            'dimensions' => '150 x 80 x 90 cm',
            'colors' => ['Gỗ sáng', 'Gỗ óc chó'],
            'short' => 'Đảo bếp đa năng: sơ chế, ăn sáng và lưu trữ.',
        ],
        'đèn bàn.jpg' => [
            'name' => 'Đèn Bàn',
            'category' => 'den-va-phu-kien',
            'price' => 690_000,
            'material' => 'Kim loại + vải',
            'dimensions' => 'Ø20 x 40 cm',
            'colors' => ['Đen', 'Trắng'],
            'short' => 'Đèn bàn đọc sách ánh sáng ấm.',
        ],
        'kệ đầu giường.jpg' => [
            'name' => 'Kệ Đầu Giường',
            'category' => 'phong-ngu',
            'price' => 1_690_000,
            'material' => 'Gỗ cao su',
            'dimensions' => '45 x 40 x 50 cm',
            'colors' => ['Gỗ tự nhiên', 'Trắng'],
            'short' => 'Kệ đầu giường ngăn kéo cho góc ngủ gọn.',
        ],
        'ghế bành.webp' => [
            'name' => 'Ghế Bành',
            'category' => 'phong-khach',
            'price' => 4_890_000,
            'material' => 'Gỗ + vải bố',
            'dimensions' => '76 x 82 x 88 cm',
            'colors' => ['Nâu nhạt', 'Xanh rêu'],
            'short' => 'Ghế bành êm ái cho góc đọc sách.',
        ],
        'Tủ buffet.jpg' => [
            'name' => 'Tủ Buffet',
            'category' => 'phong-an',
            'price' => 7_490_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '160 x 40 x 80 cm',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'short' => 'Tủ buffet phòng ăn lưu trữ chén đĩa.',
        ],
        'Đèn hắt tường.jpg' => [
            'name' => 'Đèn Hắt Tường',
            'category' => 'den-va-phu-kien',
            'price' => 590_000,
            'material' => 'Nhôm',
            'dimensions' => '12 x 8 x 10 cm',
            'colors' => ['Đen', 'Trắng'],
            'short' => 'Đèn hắt tường tạo điểm nhấn ánh sáng.',
        ],
        'tủ giày.webp' => [
            'name' => 'Tủ Giày',
            'category' => 'luu-tru',
            'price' => 2_590_000,
            'material' => 'Gỗ MFD',
            'dimensions' => '80 x 30 x 100 cm',
            'colors' => ['Trắng', 'Gỗ sáng'],
            'short' => 'Tủ giày nhiều tầng, cửa lật gọn cho lối vào.',
        ],
        'bàn làm việc.jpg' => [
            'name' => 'Bàn Làm Việc',
            'category' => 'phong-lam-viec',
            'price' => 4_290_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '140 x 70 x 75 cm',
            'colors' => ['Gỗ sáng', 'Nâu trầm'],
            'short' => 'Bàn làm việc rộng rãi, mặt gỗ mộc.',
        ],
        'thảm trải sàn.jpg' => [
            'name' => 'Thảm Trải Sàn',
            'category' => 'phu-kien',
            'price' => 2_290_000,
            'material' => 'Sợi tổng hợp',
            'dimensions' => '160 x 230 cm',
            'colors' => ['Be', 'Xám'],
            'short' => 'Thảm trải sàn êm chân, họa tiết tối giản.',
        ],
        'Sofa giường thông minh.jpg' => [
            'name' => 'Sofa Giường',
            'category' => 'phong-khach',
            'price' => 9_890_000,
            'material' => 'Khung gỗ + vải',
            'dimensions' => '200 x 90 x 85 cm',
            'colors' => ['Xám', 'Xanh navy'],
            'short' => 'Sofa gấp thành giường cho căn hộ nhỏ.',
        ],
        'tủ hồ sơ.webp' => [
            'name' => 'Tủ Hồ Sơ',
            'category' => 'phong-lam-viec',
            'price' => 3_490_000,
            'material' => 'Gỗ công nghiệp',
            'dimensions' => '80 x 40 x 120 cm',
            'colors' => ['Ghi', 'Trắng'],
            'short' => 'Tủ hồ sơ khóa chắc, nhiều ngăn kéo.',
        ],
        'bồn tắm.jpg' => [
            'name' => 'Bồn Tắm',
            'category' => 'phong-tam',
            'price' => 12_500_000,
            'material' => 'Composite',
            'dimensions' => '170 x 80 x 60 cm',
            'colors' => ['Trắng'],
            'short' => 'Bồn tắm hiện đại, bề mặt nhẵn dễ vệ sinh.',
        ],
        'ghế xoay.jpg' => [
            'name' => 'Ghế Xoay Văn Phòng',
            'category' => 'phong-lam-viec',
            'price' => 2_890_000,
            'material' => 'Lưới + nhựa',
            'dimensions' => '62 x 62 x 110 cm',
            'colors' => ['Đen', 'Xám'],
            'short' => 'Ghế xoay công thái học, tựa lưới thoáng.',
        ],
        'Bàn Console.jpg' => [
            'name' => 'Bàn Console',
            'category' => 'phong-khach',
            'price' => 4_590_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '120 x 35 x 80 cm',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'short' => 'Bàn console thanh mảnh cho lối vào.',
        ],
        'tủ rượu.jpg' => [
            'name' => 'Tủ Rượu',
            'category' => 'phong-an',
            'price' => 6_790_000,
            'material' => 'Gỗ sồi + kính',
            'dimensions' => '90 x 40 x 140 cm',
            'colors' => ['Nâu óc chó', 'Gỗ sáng'],
            'short' => 'Tủ rượu kính trưng bày, kệ chắc chắn.',
        ],
        'rèm cửa.jpg' => [
            'name' => 'Rèm Cửa',
            'category' => 'phu-kien',
            'price' => 1_490_000,
            'material' => 'Vải polyester',
            'dimensions' => '140 x 270 cm / tấm',
            'colors' => ['Kem', 'Xám'],
            'short' => 'Rèm cửa cản sáng nhẹ, mềm rũ.',
        ],
        'bàn trang điểm.webp' => [
            'name' => 'Bàn Trang Điểm',
            'category' => 'phong-ngu',
            'price' => 3_690_000,
            'material' => 'Gỗ MDF phủ veneer',
            'dimensions' => '100 x 40 x 75 cm',
            'colors' => ['Trắng kem', 'Hồng phấn'],
            'short' => 'Bàn trang điểm kèm gương và ngăn kéo.',
        ],
        'kệ sách.jpg' => [
            'name' => 'Kệ Sách',
            'category' => 'phong-lam-viec',
            'price' => 4_790_000,
            'material' => 'Gỗ sồi',
            'dimensions' => '80 x 30 x 180 cm',
            'colors' => ['Gỗ sáng', 'Nâu trầm'],
            'short' => 'Kệ sách nhiều tầng lưu trữ và trưng bày.',
        ],
        'ghế đôn.webp' => [
            'name' => 'Ghế Đôn',
            'category' => 'phong-khach',
            'price' => 790_000,
            'material' => 'Gỗ + nệm',
            'dimensions' => 'Ø40 x 45 cm',
            'colors' => ['Gỗ tự nhiên', 'Nâu'],
            'short' => 'Ghế đôn nhỏ xinh ngồi phụ hoặc kê chân.',
        ],
        'gương toàn thân.jpg' => [
            'name' => 'Gương Toàn Thân',
            'category' => 'phong-ngu',
            'price' => 1_890_000,
            'material' => 'Gỗ + kính',
            'dimensions' => '50 x 160 cm',
            'colors' => ['Gỗ tự nhiên', 'Trắng'],
            'short' => 'Gương toàn thân khung gỗ, đặt nghiêng.',
        ],
        'vách kính phòng tắm.jpg' => [
            'name' => 'Vách Kính Tắm',
            'category' => 'phong-tam',
            'price' => 5_890_000,
            'material' => 'Kính cường lực 10mm',
            'dimensions' => '90 x 200 cm',
            'colors' => ['Trong suốt', 'Mờ'],
            'short' => 'Vách kính ngăn buồng tắm hiện đại.',
        ],
        'Bàn góc sofa.jpg' => [
            'name' => 'Bàn Góc Sofa',
            'category' => 'phong-khach',
            'price' => 1_890_000,
            'material' => 'Gỗ cao su',
            'dimensions' => '50 x 50 x 55 cm',
            'colors' => ['Tự nhiên', 'Trắng'],
            'short' => 'Bàn góc nhỏ gọn đặt cạnh sofa.',
        ],
        'tủ đựng đồ.webp' => [
            'name' => 'Tủ Đựng Đồ',
            'category' => 'luu-tru',
            'price' => 3_290_000,
            'material' => 'Gỗ công nghiệp',
            'dimensions' => '100 x 40 x 120 cm',
            'colors' => ['Trắng', 'Gỗ sáng'],
            'short' => 'Tủ đa năng đựng đồ gia dụng.',
        ],
        'ghế quần bar.jpg' => [
            'name' => 'Ghế Quầy Bar',
            'category' => 'phong-an',
            'price' => 1_990_000,
            'material' => 'Gỗ + kim loại',
            'dimensions' => '40 x 40 x 75 cm',
            'colors' => ['Gỗ tự nhiên', 'Đen'],
            'short' => 'Ghế quầy bar cao cho đảo bếp.',
        ],
        'Bình phong ngăn phòng.jpg' => [
            'name' => 'Bình Phong Ngăn Phòng',
            'category' => 'phong-khach',
            'price' => 2_790_000,
            'material' => 'Gỗ thông + vải',
            'dimensions' => '180 x 160 cm (3 cánh)',
            'colors' => ['Gỗ tự nhiên', 'Trắng'],
            'short' => 'Bình phong 3 cánh phân chia không gian.',
        ],
        'tủ lavabo.jpg' => [
            'name' => 'Tủ Lavabo',
            'category' => 'phong-tam',
            'price' => 4_290_000,
            'material' => 'Gỗ chống ẩm',
            'dimensions' => '80 x 50 x 85 cm',
            'colors' => ['Trắng', 'Gỗ sồi'],
            'short' => 'Tủ chậu rửa mặt tích hợp ngăn kéo.',
        ],
        'ghế băng dài.jpg' => [
            'name' => 'Ghế Băng Dài',
            'category' => 'phong-an',
            'price' => 2_490_000,
            'material' => 'Gỗ sồi + nệm',
            'dimensions' => '140 x 35 x 45 cm',
            'colors' => ['Gỗ sáng', 'Nâu trầm'],
            'short' => 'Ghế băng dài cuối giường hoặc bàn ăn.',
        ],
        'kệ góc tường.webp' => [
            'name' => 'Kệ Góc Tường',
            'category' => 'luu-tru',
            'price' => 1_190_000,
            'material' => 'Gỗ thông',
            'dimensions' => '3 tầng, 80 x 25 x 120 cm',
            'colors' => ['Gỗ tự nhiên', 'Trắng'],
            'short' => 'Kệ góc tận dụng khoảng trống tường.',
        ],
        'ghế lười.jpg' => [
            'name' => 'Ghế Lười',
            'category' => 'phong-ngu',
            'price' => 1_290_000,
            'material' => 'Vải nhung + hạt xốp',
            'dimensions' => 'Ø90 x 70 cm',
            'colors' => ['Xám', 'Be'],
            'short' => 'Ghế lười hạt xốp thư giãn.',
        ],
        'gương phòng tắm.jpg' => [
            'name' => 'Gương Phòng Tắm',
            'category' => 'phong-tam',
            'price' => 1_590_000,
            'material' => 'Kính + nhôm',
            'dimensions' => '70 x 90 cm',
            'colors' => ['Bạc', 'Đen'],
            'short' => 'Gương phòng tắm chống ẩm, viền mỏng.',
        ],
        'ghế thay giày.jpg' => [
            'name' => 'Ghế Thay Giày',
            'category' => 'luu-tru',
            'price' => 890_000,
            'material' => 'Gỗ cao su + nệm',
            'dimensions' => '60 x 35 x 45 cm',
            'colors' => ['Gỗ sáng', 'Nâu'],
            'short' => 'Ghế nhỏ gọn cạnh tủ giày ở lối vào.',
        ],
        'Bục ngồi cửa sổ.jpg' => [
            'name' => 'Bục Ngồi Cửa Sổ',
            'category' => 'phong-ngu',
            'price' => 2_190_000,
            'material' => 'Gỗ cao su + nệm',
            'dimensions' => '120 x 45 x 45 cm',
            'colors' => ['Gỗ sáng + nệm be'],
            'short' => 'Bục ngồi đọc sách bên cửa sổ.',
        ],
    ];

    public function run(): void
    {
        $files = $this->scanPictures();
        if ($files === []) {
            $this->command?->warn('No images in storage/picture — seeder skipped.');

            return;
        }

        // Only seed products that actually have a picture file
        $items = [];
        foreach ($files as $file) {
            $meta = self::CATALOG[$file] ?? null;
            if ($meta === null) {
                $meta = $this->fallbackMeta($file);
            }

            $items[] = [
                'file' => $file,
                'name' => $meta['name'],
                'slug' => Str::slug($meta['name']),
                'category' => $meta['category'],
                'price' => $meta['price'],
                'material' => $meta['material'],
                'dimensions' => $meta['dimensions'],
                'colors' => array_values(array_unique($meta['colors'])),
                'short' => $meta['short'],
            ];
        }

        // Interleave categories so UI listings mix rooms/types
        $items = $this->interleaveByCategory($items);

        $customer = User::firstOrCreate(
            ['email' => 'customer@mocan.test'],
            [
                'name' => 'Khách hàng Demo',
                'phone' => '0909876547',
                'password' => bcrypt('Customer@123'),
                'role' => 'customer',
            ]
        );

        $created = 0;

        DB::transaction(function () use ($items, $customer, &$created) {
            // Clean old catalog completely (products cascade to images/variants/orders items stay)
            Review::query()->delete();
            ProductVariant::query()->delete();
            ProductImage::query()->delete();
            Product::query()->delete();

            foreach ($items as $index => $item) {
                $category = Category::firstOrCreate(
                    ['slug' => $item['category']],
                    [
                        'name' => $this->categoryName($item['category']),
                        'description' => 'Danh mục '.$this->categoryName($item['category']).' tại Mộc An.',
                        'is_active' => true,
                    ]
                );

                // Unique slug/sku guards
                $slug = $item['slug'];
                $suffix = 2;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $item['slug'].'-'.$suffix++;
                }

                $sku = sprintf('MA-%03d', $index + 1);
                while (Product::where('sku', $sku)->exists()) {
                    $sku = sprintf('MA-%03d-%s', $index + 1, Str::upper(Str::random(3)));
                }

                // ~35% on sale, 10–25% off
                $onSale = ($index % 3) === 0;
                $discount = $onSale ? random_int(10, 25) : 0;
                $salePrice = $onSale
                    ? (int) round($item['price'] * (100 - $discount) / 100 / 1000) * 1000
                    : null;

                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'sku' => $sku,
                    'short_description' => $item['short'],
                    'description' => $item['short'].' Sản phẩm Mộc An — vật liệu tự nhiên, hoàn thiện chỉn chu, bảo hành dài hạn.',
                    'base_price' => $item['price'],
                    'sale_price' => $salePrice,
                    'material' => $item['material'],
                    'dimensions' => $item['dimensions'],
                    'color' => $item['colors'][0] ?? 'Tự nhiên',
                    'weight' => random_int(2, 80) + random_int(0, 9) / 10,
                    'is_featured' => ($index % 4) === 0,
                    'is_active' => true,
                    // Stagger views so "latest" by id still reads natural; views vary for realism
                    'views_count' => random_int(30, 600),
                    'created_at' => now()->subDays(count($items) - $index)->subHours(random_int(0, 12)),
                    'updated_at' => now(),
                ]);

                // ONE image per product — original filename in storage/picture
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => self::PICTURE_DIR.'/'.$item['file'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);

                // Variants share the same product (not separate products / not separate images)
                foreach ($item['colors'] as $vi => $color) {
                    $variantPrice = $vi === 0
                        ? $item['price']
                        : (int) round($item['price'] * (1 + $vi * 0.02) / 1000) * 1000;

                    $vsku = $sku.'-'.($vi + 1);
                    while (ProductVariant::where('sku', $vsku)->exists()) {
                        $vsku = $sku.'-'.Str::upper(Str::random(3));
                    }

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'color' => $color,
                        'size' => $item['dimensions'],
                        'material' => $item['material'],
                        'price' => $variantPrice,
                        'stock' => random_int(10, 50),
                        'sku' => $vsku,
                    ]);
                }

                $this->seedReviews($product, $customer);
                $created++;
            }
        });

        $this->command?->info("PictureProductSeeder: cleaned old data, created {$created} products (1 image = 1 product).");
        $this->command?->info('Image files: '.count($files).'; products in DB: '.Product::count());
    }

    /**
     * Round-robin by category so a product grid doesn't show 3 wardrobes in a row.
     *
     * @param  list<array>  $items
     * @return list<array>
     */
    private function interleaveByCategory(array $items): array
    {
        $buckets = [];
        foreach ($items as $item) {
            $buckets[$item['category']][] = $item;
        }

        // Keep a preferred category rotation matching the UX example
        $order = [
            'phong-khach',
            'phong-an',
            'den-va-phu-kien',
            'luu-tru',
            'phong-ngu',
            'phong-lam-viec',
            'phong-tam',
            'phu-kien',
        ];

        $keys = array_values(array_unique(array_merge($order, array_keys($buckets))));
        $result = [];
        $max = max(array_map('count', $buckets ?: [[]]));
        // Guard empty
        if ($buckets === [] || $max < 1) {
            return $items;
        }

        for ($i = 0; $i < $max; $i++) {
            foreach ($keys as $key) {
                if (isset($buckets[$key][$i])) {
                    $result[] = $buckets[$key][$i];
                }
            }
        }

        return $result;
    }

    /** @return list<string> */
    private function scanPictures(): array
    {
        $dir = storage_path(self::PICTURE_DIR);
        if (! is_dir($dir)) {
            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $files = [];

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $files[] = $entry;
            }
        }

        natcasesort($files);

        return array_values($files);
    }

    private function fallbackMeta(string $file): array
    {
        $base = pathinfo($file, PATHINFO_FILENAME);
        $base = trim(preg_replace('/\s+/u', ' ', str_replace(['-', '_'], ' ', $base)) ?? $base);
        $words = preg_split('/\s+/u', $base) ?: [];
        $name = implode(' ', array_map(static function (string $w): string {
            return mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($w, 1, null, 'UTF-8');
        }, $words));

        return [
            'name' => $name !== '' ? $name : 'Sản phẩm Mộc An',
            'category' => 'phu-kien',
            'price' => random_int(12, 45) * 100_000,
            'material' => 'Gỗ tự nhiên',
            'dimensions' => 'Tiêu chuẩn',
            'colors' => ['Tự nhiên'],
            'short' => ($name !== '' ? $name : 'Sản phẩm').' phong cách tối giản cho không gian sống.',
        ];
    }

    private function categoryName(string $slug): string
    {
        return match ($slug) {
            'phong-khach' => 'Phòng khách',
            'phong-ngu' => 'Phòng ngủ',
            'phong-an' => 'Phòng ăn',
            'phong-lam-viec' => 'Phòng làm việc',
            'phong-tam' => 'Phòng tắm',
            'luu-tru' => 'Lưu trữ',
            'den-va-phu-kien' => 'Đèn & phụ kiện',
            default => 'Phụ kiện',
        };
    }

    private function seedReviews(Product $product, User $customer): void
    {
        $comments = [
            'Sản phẩm đẹp, đóng gói kỹ, giao nhanh.',
            'Chất lượng tốt, đúng mô tả. Sẽ ủng hộ shop tiếp.',
            'Phom dáng tinh giản, hợp căn hộ của mình.',
            'Giao hàng cẩn thận, lắp ráp dễ.',
            'Hoàn thiện tốt, không bị xước hay mối mọt.',
        ];

        $count = random_int(2, 4);
        for ($i = 0; $i < $count; $i++) {
            // Bias 4–5 stars (schema stores integer rating)
            $rating = random_int(0, 9) < 7 ? 5 : 4;

            Review::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'rating' => $rating,
                'comment' => $comments[array_rand($comments)],
                'is_approved' => true,
            ]);
        }
    }
}
