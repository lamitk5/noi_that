<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithStock(int $stock, array $variantAttributes = []): Product
    {
        $category = Category::create([
            'name' => 'Phòng khách ' . uniqid(),
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Gỗ Cao Cấp ' . uniqid(),
            'slug' => 'ghe-go-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'color' => 'Nâu hạt dẻ',
            'size' => 'Tiêu chuẩn',
            'price' => 2000000,
            'stock' => $stock,
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ], $variantAttributes));

        return $product;
    }

    public function test_product_with_positive_stock_shows_in_stock_badge(): void
    {
        $product = $this->createProductWithStock(15);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Còn hàng');
        $response->assertDontSee('bg-red-500');
        $this->assertEquals(15, $product->totalStock());
        $this->assertFalse($product->isOutOfStock());
        $this->assertFalse($product->isLowStock());
    }

    public function test_product_with_low_stock_shows_low_stock_badge(): void
    {
        $product = $this->createProductWithStock(3);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Sắp hết hàng');
        $response->assertSee('còn 3');
        $this->assertEquals(3, $product->totalStock());
        $this->assertFalse($product->isOutOfStock());
        $this->assertTrue($product->isLowStock());
    }

    public function test_product_with_zero_stock_shows_out_of_stock_badge(): void
    {
        $product = $this->createProductWithStock(0);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Hết hàng');
        $this->assertEquals(0, $product->totalStock());
        $this->assertTrue($product->isOutOfStock());
        $this->assertFalse($product->isLowStock());
    }

    public function test_product_total_stock_is_sum_of_all_variants(): void
    {
        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Đa Năng',
            'slug' => 'ban-da-nang-' . uniqid(),
            'sku' => 'BDN-' . strtoupper(uniqid()),
            'base_price' => 3000000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Trắng',
            'size' => '120cm',
            'price' => 3000000,
            'stock' => 5,
            'sku' => 'BDN-TRANG',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Đen',
            'size' => '140cm',
            'price' => 3500000,
            'stock' => 7,
            'sku' => 'BDN-DEN',
        ]);

        $this->assertEquals(12, $product->totalStock());

        $response = $this->get(route('products.show', $product->slug));
        $response->assertStatus(200);
        $response->assertSee('Trắng');
        $response->assertSee('Đen');
    }

    public function test_out_of_stock_variant_is_marked_as_out_of_stock(): void
    {
        $category = Category::create([
            'name' => 'Sofa',
            'slug' => 'sofa-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Băng Thư Giãn',
            'slug' => 'sofa-bang-' . uniqid(),
            'sku' => 'SFB-' . strtoupper(uniqid()),
            'base_price' => 8000000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Xanh',
            'price' => 8000000,
            'stock' => 4,
            'sku' => 'SFB-XANH',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Xám',
            'price' => 8000000,
            'stock' => 0,
            'sku' => 'SFB-XAM',
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('SFB-XAM');
        $response->assertSee('Hết hàng');
    }
}
