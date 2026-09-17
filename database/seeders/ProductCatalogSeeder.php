<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->catalog() as $categoryData) {
                $products = $categoryData['products'];
                unset($categoryData['products']);

                $category = Category::updateOrCreate(
                    ['slug' => $categoryData['slug']],
                    $categoryData,
                );

                foreach ($products as $productData) {
                    $images = $productData['images'];
                    $variants = $productData['variants'];
                    unset($productData['images'], $productData['variants']);

                    $product = Product::updateOrCreate(
                        ['sku' => $productData['sku']],
                        [...$productData, 'category_id' => $category->id],
                    );

                    $product->images()->delete();
                    $product->variants()->delete();
                    $product->images()->createMany($images);
                    $product->variants()->createMany($variants);
                }
            }
        });
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'Phòng khách',
                'slug' => 'phong-khach',
                'description' => 'Sofa, ghế thư giãn và bàn trà cho không gian sum họp.',
                'is_active' => true,
                'products' => [
                    $this->product('Sofa vải Linen Mây', 'sofa-vai-linen-may', 'PK-SF-001', 12990000, 'Sofa ba chỗ ngồi với phom dáng mềm mại và chất liệu linen thoáng khí.', 'https://images.unsplash.com/photo-1550581190-9c1c48d21d6c?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Kem', 'size' => '220 x 90 x 82 cm', 'material' => 'Vải linen', 'price' => 12990000, 'stock' => 8, 'sku' => 'PK-SF-001-KEM'],
                        ['color' => 'Xanh rêu', 'size' => '220 x 90 x 82 cm', 'material' => 'Vải linen', 'price' => 13490000, 'stock' => 5, 'sku' => 'PK-SF-001-XR'],
                    ]),
                    $this->product('Ghế thư giãn Lento', 'ghe-thu-gian-lento', 'PK-GT-002', 4290000, 'Ghế bành nhỏ gọn, phù hợp góc đọc sách và phòng khách hiện đại.', 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Nâu nhạt', 'size' => '76 x 82 x 88 cm', 'material' => 'Vải bố', 'price' => 4290000, 'stock' => 12, 'sku' => 'PK-GT-002-NAU'],
                        ['color' => 'Ghi sáng', 'size' => '76 x 82 x 88 cm', 'material' => 'Vải bố', 'price' => 4290000, 'stock' => 9, 'sku' => 'PK-GT-002-GHI'],
                    ]),
                    $this->product('Bàn trà gỗ Tần Bì', 'ban-tra-go-tan-bi', 'PK-BT-003', 3650000, 'Bàn trà gỗ tự nhiên với đường nét bo tròn an toàn và thanh lịch.', 'https://images.unsplash.com/photo-1532372320572-cda25653a694?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Gỗ tự nhiên', 'size' => '110 x 60 x 42 cm', 'material' => 'Gỗ tần bì', 'price' => 3650000, 'stock' => 10, 'sku' => 'PK-BT-003-TN'],
                        ['color' => 'Nâu óc chó', 'size' => '110 x 60 x 42 cm', 'material' => 'Gỗ tần bì', 'price' => 3890000, 'stock' => 6, 'sku' => 'PK-BT-003-OC'],
                    ]),
                ],
            ],
            [
                'name' => 'Phòng ngủ',
                'slug' => 'phong-ngu',
                'description' => 'Nội thất êm dịu cho khoảng nghỉ ngơi riêng tư.',
                'is_active' => true,
                'products' => [
                    $this->product('Giường gỗ Nara', 'giuong-go-nara', 'PN-GG-001', 10500000, 'Thiết kế đầu giường tối giản, kết cấu chắc chắn và bền bỉ.', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Gỗ sáng', 'size' => '160 x 200 cm', 'material' => 'Gỗ sồi', 'price' => 10500000, 'stock' => 5, 'sku' => 'PN-GG-001-160'],
                        ['color' => 'Gỗ sáng', 'size' => '180 x 200 cm', 'material' => 'Gỗ sồi', 'price' => 11900000, 'stock' => 4, 'sku' => 'PN-GG-001-180'],
                    ]),
                    $this->product('Tủ đầu giường Mino', 'tu-dau-giuong-mino', 'PN-TD-002', 2190000, 'Tủ nhỏ hai ngăn kéo giúp góc giường luôn gọn gàng.', 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Gỗ tự nhiên', 'size' => '48 x 40 x 52 cm', 'material' => 'Gỗ cao su', 'price' => 2190000, 'stock' => 14, 'sku' => 'PN-TD-002-TN'],
                        ['color' => 'Nâu trầm', 'size' => '48 x 40 x 52 cm', 'material' => 'Gỗ cao su', 'price' => 2290000, 'stock' => 8, 'sku' => 'PN-TD-002-NT'],
                    ]),
                ],
            ],
            [
                'name' => 'Phòng ăn',
                'slug' => 'phong-an',
                'description' => 'Bàn ghế cho những bữa cơm ấm cúng và gắn kết.',
                'is_active' => true,
                'products' => [
                    $this->product('Bàn ăn Osaka', 'ban-an-osaka', 'PA-BA-001', 8990000, 'Bàn ăn gỗ thanh thoát, vừa vặn cho gia đình hiện đại.', 'https://images.unsplash.com/photo-1617806118233-18e1de247200?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Gỗ sáng', 'size' => '160 x 80 x 75 cm', 'material' => 'Gỗ sồi', 'price' => 8990000, 'stock' => 7, 'sku' => 'PA-BA-001-160'],
                        ['color' => 'Nâu óc chó', 'size' => '180 x 90 x 75 cm', 'material' => 'Gỗ sồi', 'price' => 10490000, 'stock' => 4, 'sku' => 'PA-BA-001-180'],
                    ]),
                    $this->product('Ghế ăn Mộc', 'ghe-an-moc', 'PA-GA-002', 1890000, 'Ghế ăn tựa cong nâng đỡ lưng, nhẹ và dễ di chuyển.', 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Tự nhiên', 'size' => '48 x 52 x 78 cm', 'material' => 'Gỗ cao su', 'price' => 1890000, 'stock' => 24, 'sku' => 'PA-GA-002-TN'],
                        ['color' => 'Đen', 'size' => '48 x 52 x 78 cm', 'material' => 'Gỗ cao su', 'price' => 1990000, 'stock' => 16, 'sku' => 'PA-GA-002-DEN'],
                    ]),
                ],
            ],
            [
                'name' => 'Phòng làm việc',
                'slug' => 'phong-lam-viec',
                'description' => 'Không gian tập trung với bàn, ghế và tủ lưu trữ tiện dụng.',
                'is_active' => true,
                'products' => [
                    $this->product('Bàn làm việc Nordic', 'ban-lam-viec-nordic', 'LV-BL-001', 4590000, 'Bàn làm việc có ngăn kéo, thiết kế tinh gọn cho căn hộ hiện đại.', 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=1200&q=85', [
                        ['color' => 'Gỗ sáng', 'size' => '120 x 60 x 75 cm', 'material' => 'Gỗ sồi', 'price' => 4590000, 'stock' => 11, 'sku' => 'LV-BL-001-120'],
                        ['color' => 'Nâu trầm', 'size' => '140 x 65 x 75 cm', 'material' => 'Gỗ sồi', 'price' => 5290000, 'stock' => 7, 'sku' => 'LV-BL-001-140'],
                    ]),
                ],
            ],
        ];
    }

    private function product(
        string $name,
        string $slug,
        string $sku,
        int $basePrice,
        string $shortDescription,
        string $image,
        array $variants,
    ): array {
        return [
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'short_description' => $shortDescription,
            'description' => $shortDescription.' Sản phẩm được tuyển chọn theo tiêu chí thẩm mỹ, công năng và độ bền cho không gian sống Việt.',
            'base_price' => $basePrice,
            'is_active' => true,
            'images' => [
                ['image_path' => $image, 'is_primary' => true, 'sort_order' => 0],
                ['image_path' => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80', 'is_primary' => false, 'sort_order' => 1],
            ],
            'variants' => $variants,
        ];
    }
}
