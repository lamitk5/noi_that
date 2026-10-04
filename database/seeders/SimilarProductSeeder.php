<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Adds comparable siblings (same kind of furniture, different price and size)
 * without deleting the existing catalog. Safe to run more than once.
 */
class SimilarProductSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    private const ITEMS = [
        [
            'file' => 'cmp-sofa-vai-3-cho.jpg',
            'name' => 'Sofa Vải 3 Chỗ',
            'category' => 'phong-khach',
            'price' => 7_290_000,
            'sale' => null,
            'material' => 'Vải bố + khung gỗ',
            'weight' => 42,
            'stock' => 6,
            'short' => 'Sofa vải 3 chỗ, tựa thấp, dễ phối phòng khách.',
            'colors' => ['Xanh rêu', 'Xám khói'],
            'sizes' => [
                ['2 chỗ', '170 x 90 x 85 cm', 0.86],
                ['3 chỗ', '210 x 90 x 85 cm', 1],
                ['4 chỗ', '250 x 90 x 85 cm', 1.16],
            ],
        ],
        [
            'file' => 'cmp-sofa-da-2-cho.jpg',
            'name' => 'Sofa Da 2 Chỗ',
            'category' => 'phong-khach',
            'price' => 8_490_000,
            'sale' => 0.85,
            'material' => 'Da tổng hợp + khung gỗ',
            'weight' => 38,
            'stock' => 4,
            'short' => 'Sofa da 2 chỗ, đệm dày, chân kim loại.',
            'colors' => ['Kem', 'Nâu nhạt'],
            'sizes' => [
                ['2 chỗ', '168 x 90 x 82 cm', 1],
                ['3 chỗ', '210 x 90 x 82 cm', 1.18],
            ],
        ],
        [
            'file' => 'cmp-sofa-chu-l.jpg',
            'name' => 'Sofa Da Chữ L',
            'category' => 'phong-khach',
            'price' => 14_900_000,
            'sale' => null,
            'material' => 'Da PU + khung gỗ',
            'weight' => 68,
            'stock' => 3,
            'short' => 'Sofa chữ L có đôn, ngồi được cả gia đình.',
            'colors' => ['Nâu caramel', 'Nâu đậm'],
            'sizes' => [
                ['Góc nhỏ', '220 x 150 x 84 cm', 0.88],
                ['Góc tiêu chuẩn', '260 x 160 x 84 cm', 1],
                ['Góc lớn', '300 x 170 x 84 cm', 1.14],
            ],
        ],
        [
            'file' => 'cmp-sofa-da-nau.jpg',
            'name' => 'Sofa Da Nâu',
            'category' => 'phong-khach',
            'price' => 9_690_000,
            'sale' => null,
            'material' => 'Da thật + khung gỗ',
            'weight' => 36,
            'stock' => 8,
            'short' => 'Sofa da nâu 2–3 chỗ, form gọn.',
            'colors' => ['Nâu', 'Nâu óc chó'],
            'sizes' => [
                ['2 chỗ', '160 x 88 x 80 cm', 0.9],
                ['3 chỗ', '200 x 88 x 80 cm', 1],
            ],
        ],
        [
            'file' => 'cmp-ban-an-trang.jpg',
            'name' => 'Bàn Ăn Sơn Trắng',
            'category' => 'phong-an',
            'price' => 6_490_000,
            'sale' => 0.88,
            'material' => 'Gỗ sơn trắng + mặt gỗ',
            'weight' => 32,
            'stock' => 7,
            'short' => 'Bàn ăn chân tiện, mặt gỗ, cho 4–6 người.',
            'colors' => ['Trắng', 'Kem'],
            'sizes' => [
                ['4 người', '120 x 75 x 76 cm', 0.82],
                ['6 người', '150 x 85 x 76 cm', 1],
                ['8 người', '180 x 90 x 76 cm', 1.2],
            ],
        ],
        [
            'file' => 'cmp-ban-an-oc-cho.jpg',
            'name' => 'Bàn Ăn Gỗ Óc Chó',
            'category' => 'phong-an',
            'price' => 12_500_000,
            'sale' => null,
            'material' => 'Gỗ óc chó',
            'weight' => 48,
            'stock' => 4,
            'short' => 'Bàn ăn gỗ óc chó cho 6–8 người.',
            'colors' => ['Nâu óc chó', 'Gỗ tối'],
            'sizes' => [
                ['6 người', '160 x 85 x 76 cm', 0.9],
                ['8 người', '180 x 90 x 76 cm', 1],
                ['10 người', '220 x 95 x 76 cm', 1.18],
            ],
        ],
        [
            'file' => 'cmp-ban-tra-go.jpg',
            'name' => 'Bàn Ăn Tròn Mặt Trắng',
            'category' => 'phong-an',
            'price' => 4_290_000,
            'sale' => null,
            'material' => 'Mặt MDF + chân gỗ',
            'weight' => 14,
            'stock' => 11,
            'short' => 'Bàn ăn tròn 4 người, chân gỗ thon.',
            'colors' => ['Trắng', 'Kem'],
            'sizes' => [
                ['2 người', 'Ø80 x 75 cm', 0.8],
                ['4 người', 'Ø100 x 75 cm', 1],
                ['6 người', 'Ø120 x 75 cm', 1.22],
            ],
        ],
        [
            'file' => 'cmp-giuong-go-trang.jpg',
            'name' => 'Giường Ngủ Gỗ Trắng',
            'category' => 'phong-ngu',
            'price' => 9_290_000,
            'sale' => null,
            'material' => 'Gỗ sơn trắng',
            'weight' => 45,
            'stock' => 5,
            'short' => 'Giường gỗ trắng, đầu giường thấp, phòng ngủ sáng.',
            'colors' => ['Trắng', 'Kem'],
            'sizes' => [
                ['1m6', '160 x 200 cm', 0.92],
                ['1m8', '180 x 200 cm', 1],
                ['2m', '200 x 200 cm', 1.12],
            ],
        ],
        [
            'file' => 'cmp-giuong-boc-nem.jpg',
            'name' => 'Giường Bọc Nệm Kem',
            'category' => 'phong-ngu',
            'price' => 13_900_000,
            'sale' => 0.9,
            'material' => 'Khung gỗ + vải',
            'weight' => 52,
            'stock' => 3,
            'short' => 'Giường bọc nệm đầu giường rút núm, êm lưng.',
            'colors' => ['Kem', 'Xám nhạt'],
            'sizes' => [
                ['1m6', '160 x 200 cm', 0.9],
                ['1m8', '180 x 200 cm', 1],
                ['2m', '200 x 200 cm', 1.14],
            ],
        ],
        [
            'file' => 'cmp-ke-dau-giuong.jpg',
            'name' => 'Giường Bọc Nệm Xám',
            'category' => 'phong-ngu',
            'price' => 15_400_000,
            'sale' => null,
            'material' => 'Khung gỗ + vải',
            'weight' => 48,
            'stock' => 2,
            'short' => 'Giường bọc nệm kênh chỉ, chân kim loại.',
            'colors' => ['Xám', 'Kem'],
            'sizes' => [
                ['1m6', '160 x 200 cm', 0.9],
                ['1m8', '180 x 200 cm', 1],
                ['2m', '200 x 200 cm', 1.12],
            ],
        ],
        [
            'file' => 'cmp-tu-ao-go.jpg',
            'name' => 'Tủ Áo Âm Tường',
            'category' => 'phong-ngu',
            'price' => 11_500_000,
            'sale' => null,
            'material' => 'Gỗ sồi veneer',
            'weight' => 85,
            'stock' => 3,
            'short' => 'Tủ áo âm tường cánh phẳng, tay nắm âm.',
            'colors' => ['Gỗ sáng', 'Nâu óc chó'],
            'sizes' => [
                ['2 buồng', '160 x 60 x 220 cm', 0.78],
                ['3 buồng', '200 x 60 x 240 cm', 1],
                ['4 buồng', '240 x 60 x 240 cm', 1.22],
            ],
        ],
        [
            'file' => 'cmp-tu-ao-trang.jpg',
            'name' => 'Tủ Treo Phòng Tắm',
            'category' => 'phong-tam',
            'price' => 3_890_000,
            'sale' => null,
            'material' => 'Gỗ công nghiệp chống ẩm',
            'weight' => 28,
            'stock' => 9,
            'short' => 'Tủ treo tường có hộc mở, dùng trong phòng tắm.',
            'colors' => ['Gỗ sáng', 'Trắng'],
            'sizes' => [
                ['Hẹp', '60 x 25 x 120 cm', 0.82],
                ['Tiêu chuẩn', '80 x 25 x 140 cm', 1],
                ['Rộng', '100 x 25 x 160 cm', 1.2],
            ],
        ],
        [
            'file' => 'cmp-ke-tivi-go.jpg',
            'name' => 'Kệ Tivi Gỗ Óc Chó',
            'category' => 'phong-khach',
            'price' => 12_900_000,
            'sale' => null,
            'material' => 'Gỗ óc chó',
            'weight' => 55,
            'stock' => 2,
            'short' => 'Vách kệ tivi gỗ tối, có hộc đèn và ngăn kéo.',
            'colors' => ['Nâu óc chó', 'Nâu đậm'],
            'sizes' => [
                ['180 cm', '180 x 45 x 50 cm', 0.72],
                ['240 cm', '240 x 45 x 160 cm', 0.88],
                ['280 cm', '280 x 45 x 180 cm', 1],
            ],
        ],
        [
            'file' => 'cmp-ban-lam-viec.jpg',
            'name' => 'Bàn Làm Việc Chữ L',
            'category' => 'phong-lam-viec',
            'price' => 5_290_000,
            'sale' => 0.82,
            'material' => 'Mặt gỗ công nghiệp + chân gỗ sơn',
            'weight' => 27,
            'stock' => 8,
            'short' => 'Bàn chữ L có kệ dưới, hợp góc làm việc.',
            'colors' => ['Trắng', 'Xám'],
            'sizes' => [
                ['Góc nhỏ', '120 x 100 x 75 cm', 0.86],
                ['Chữ L', '150 x 120 x 75 cm', 1],
                ['Chữ L rộng', '180 x 140 x 75 cm', 1.18],
            ],
        ],
        [
            'file' => 'cmp-ban-hoc.jpg',
            'name' => 'Bàn Học Mặt Gỗ',
            'category' => 'phong-lam-viec',
            'price' => 3_190_000,
            'sale' => null,
            'material' => 'Gỗ cao su',
            'weight' => 18,
            'stock' => 14,
            'short' => 'Bàn học mặt gỗ liền, gọn cho phòng nhỏ.',
            'colors' => ['Gỗ sáng', 'Nâu nhạt'],
            'sizes' => [
                ['100 cm', '100 x 50 x 75 cm', 0.86],
                ['120 cm', '120 x 55 x 75 cm', 1],
                ['140 cm', '140 x 60 x 75 cm', 1.16],
            ],
        ],
        [
            'file' => 'cmp-ban-tra-kinh.jpg',
            'name' => 'Bàn Làm Việc Gỗ Tần Bì',
            'category' => 'phong-lam-viec',
            'price' => 4_890_000,
            'sale' => null,
            'material' => 'Gỗ tần bì + chân kim loại',
            'weight' => 22,
            'stock' => 6,
            'short' => 'Bàn làm việc mặt gỗ tần bì, chân chữ A.',
            'colors' => ['Gỗ sáng', 'Tự nhiên'],
            'sizes' => [
                ['120 cm', '120 x 60 x 75 cm', 0.88],
                ['140 cm', '140 x 70 x 75 cm', 1],
                ['160 cm', '160 x 75 x 75 cm', 1.14],
            ],
        ],
    ];

    public function run(): void
    {
        $created = 0;
        $skipped = 0;
        $skuNumber = $this->nextSkuNumber();

        foreach (self::ITEMS as $item) {
            $slug = Str::slug($item['name']);
            if (Product::where('slug', $slug)->orWhere('name', $item['name'])->exists()) {
                $skipped++;
                continue;
            }

            $path = storage_path('picture/'.$item['file']);
            if (! is_file($path)) {
                $this->command?->warn('Thiếu ảnh '.$item['file'].', bỏ qua '.$item['name']);
                continue;
            }

            DB::transaction(function () use ($item, $slug, &$skuNumber, &$created) {
                $category = Category::firstOrCreate(
                    ['slug' => $item['category']],
                    [
                        'name' => $this->categoryName($item['category']),
                        'description' => 'Danh mục '.$this->categoryName($item['category']).' tại Mộc An.',
                        'is_active' => true,
                    ]
                );

                $sku = sprintf('MA-%03d', $skuNumber++);
                $middle = $item['sizes'][(int) floor((count($item['sizes']) - 1) / 2)];
                $salePrice = $item['sale']
                    ? (int) (round($item['price'] * $item['sale'] / 1000) * 1000)
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
                    'dimensions' => $middle[1],
                    'color' => $item['colors'][0],
                    'weight' => $item['weight'],
                    'is_featured' => false,
                    'is_active' => true,
                    'views_count' => 40,
                ]);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'picture/'.$item['file'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);

                $n = 1;
                foreach ($item['sizes'] as $size) {
                    foreach ($item['colors'] as $colorIndex => $color) {
                        $price = (int) (round($item['price'] * $size[2] * (1 + $colorIndex * 0.02) / 1000) * 1000);
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'color' => $color,
                            'size' => $size[0].' ('.$size[1].')',
                            'material' => $item['material'],
                            'price' => $price,
                            'stock' => max(1, (int) $item['stock'] + ($size[2] < 1 ? 2 : 0)),
                            'sku' => $sku.'-'.$n++,
                        ]);
                    }
                }

                $created++;
            });
        }

        $this->command?->info("SimilarProductSeeder: thêm {$created} sản phẩm, bỏ qua {$skipped} sản phẩm đã có.");
    }

    private function nextSkuNumber(): int
    {
        $max = 41;
        foreach (Product::query()->pluck('sku') as $sku) {
            if (preg_match('/^MA-(\d+)/', (string) $sku, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        return $max + 1;
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
}
