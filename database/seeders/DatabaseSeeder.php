<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@furniture.com'],
            [
                'name' => 'Quản Trị Viên Nội Thất',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '0901234567',
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Nguyễn Văn An',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'phone' => '0987654321',
            ]
        );

        $categoriesData = [
            ['name' => 'Nội thất Phòng khách', 'slug' => 'phong-khach', 'description' => 'Bộ sưu tập sofa da cao cấp.', 'sort_order' => 1],
            ['name' => 'Nội thất Phòng ngủ', 'slug' => 'phong-ngu', 'description' => 'Giường ngủ gỗ sồi tự nhiên.', 'sort_order' => 2],
            ['name' => 'Nội thất Phòng ăn & Bếp', 'slug' => 'phong-an-bep', 'description' => 'Bàn ăn thông minh.', 'sort_order' => 3],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $productsData = [
            [
                'category_slug' => 'phong-khach',
                'name' => 'Bàn Console Gỗ Sồi',
                'sku' => 'MA-001',
                'short_description' => 'Bàn console thanh mảnh cho lối vào.',
                'description' => 'Sản phẩm Mộc An — vật liệu tự nhiên, hoàn thiện chỉn chu.',
                'base_price' => 4590000,
                'sale_price' => null,
                'material' => 'Gỗ sồi',
                'dimensions' => '120 x 35 x 80 cm',
                'color' => 'Gỗ sáng',
                'weight' => 7.4,
                'is_featured' => true,
            ],
            [
                'category_slug' => 'phong-an-bep',
                'name' => 'Bàn Ăn Gỗ Sồi',
                'sku' => 'MA-002',
                'short_description' => 'Bàn ăn tự nhiên cho 6–8 người.',
                'description' => 'Bàn ăn gỗ sồi bền đẹp cho không gian ấm cúng.',
                'base_price' => 8990000,
                'sale_price' => null,
                'material' => 'Gỗ sồi',
                'dimensions' => '160 x 80 x 75 cm',
                'color' => 'Gỗ sáng',
                'weight' => 48.6,
                'is_featured' => true,
            ],
        ];

        foreach ($productsData as $prod) {
            $catSlug = $prod['category_slug'];
            unset($prod['category_slug']);
            $prod['category_id'] = $categories[$catSlug]->id;
            $prod['slug'] = Str::slug($prod['name']);

            $product = Product::firstOrCreate(['sku' => $prod['sku']], $prod);

            if ($product->variants()->count() === 0) {
                foreach ($this->variantMatrix($product) as $variant) {
                    $product->variants()->create($variant);
                }
            }
        }

        if (Order::count() === 0) {
            $firstProduct = Product::with('variants')->first();
            $variant = $firstProduct?->variants->first();

            $order = Order::create([
                'order_code' => 'ORD-DEMO-001',
                'user_id' => $customer->id,
                'customer_name' => 'Nguyễn Văn An',
                'customer_email' => 'customer@example.com',
                'customer_phone' => '0987654321',
                'shipping_address' => 'Số 123 Đường Cầu Giấy, Hà Nội',
                'shipping_fee' => 0,
                'discount_amount' => 0,
                'total_price' => 4590000,
                'payment_method' => 'cod',
                'payment_status' => Order::PAYMENT_PENDING,
                'order_status' => Order::STATUS_PENDING,
                'note' => 'Giao hàng giờ hành chính.',
            ]);

            if ($variant) {
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $firstProduct->name,
                    'variant_info' => $variant->display_label,
                    'price' => $variant->price,
                    'quantity' => 1,
                ]);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function variantMatrix(Product $product): array
    {
        $woodColors = [
            ['color' => 'Gỗ sáng', 'material' => 'Gỗ sồi'],
            ['color' => 'Nâu óc chó', 'material' => 'Gỗ sồi'],
        ];

        $sizes = [
            ['size' => '120 x 35 x 80 cm', 'price_factor' => 0.9, 'stock' => 8],
            ['size' => '160 x 80 x 75 cm', 'price_factor' => 1.0, 'stock' => 12],
        ];

        $basePrice = (float) $product->base_price;
        $rows = [];

        foreach ($sizes as $sizeRow) {
            foreach ($woodColors as $colorRow) {
                $rows[] = [
                    'size' => $sizeRow['size'],
                    'color' => $colorRow['color'],
                    'material' => $colorRow['material'],
                    'sku' => $product->sku . '-' . strtoupper(substr(md5($sizeRow['size'] . $colorRow['color']), 0, 4)),
                    'price' => (int) round($basePrice * $sizeRow['price_factor'] / 1000) * 1000,
                    'stock' => $sizeRow['stock'],
                ];
            }
        }

        return $rows;
    }
}
