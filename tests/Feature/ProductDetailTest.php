<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach-' . uniqid(),
            'description' => 'Nội thất phòng khách',
            'is_active' => true,
        ], $attributes));
    }

    private function createProduct(array $attributes = []): Product
    {
        $categoryId = $attributes['category_id'] ?? $this->createCategory()->id;
        $name = $attributes['name'] ?? 'Bàn trà Mộc An';
        $slug = $attributes['slug'] ?? (Str::slug($name) . '-' . uniqid());

        return Product::create(array_merge([
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'sku' => 'BT-' . strtoupper(uniqid()),
            'short_description' => 'Mô tả ngắn bàn trà',
            'description' => 'Mô tả chi tiết chất liệu gỗ tự nhiên cao cấp.',
            'base_price' => 3500000,
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_can_access_product_detail(): void
    {
        $product = $this->createProduct(['name' => 'Ghế Sofa Gỗ Sồi']);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Ghế Sofa Gỗ Sồi');
    }

    public function test_authenticated_user_can_access_product_detail(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['name' => 'Kệ Sách Tối Giản']);

        $response = $this->actingAs($user)->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Kệ Sách Tối Giản');
    }

    public function test_product_detail_route_uses_slug(): void
    {
        $product = $this->createProduct(['slug' => 'ban-tra-nhat-ban']);

        $response = $this->get('/san-pham/ban-tra-nhat-ban');

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    public function test_non_existent_product_returns_404(): void
    {
        $response = $this->get('/san-pham/san-pham-khong-ton-tai-xyz');

        $response->assertStatus(404);
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = $this->createProduct([
            'slug' => 'san-pham-ngung-kinh-doanh',
            'is_active' => false,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(404);
    }

    public function test_product_detail_displays_product_name_category_sku_and_description(): void
    {
        $category = $this->createCategory(['name' => 'Phòng ngủ']);
        $product = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Giường ngủ Nara Gỗ Tự Nhiên',
            'sku' => 'GN-NARA-01',
            'short_description' => 'Thiết kế tối giản cho giấc ngủ êm đềm.',
            'description' => 'Khung giường gỗ sồi nhập khẩu, gia công mộng gỗ truyền thống vững chắc.',
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Giường ngủ Nara Gỗ Tự Nhiên');
        $response->assertSee('Phòng ngủ');
        $response->assertSee('GN-NARA-01');
        $response->assertSee('Thiết kế tối giản cho giấc ngủ êm đềm.');
        $response->assertSee('Khung giường gỗ sồi nhập khẩu, gia công mộng gỗ truyền thống vững chắc.');
    }

    public function test_product_detail_displays_formatted_price(): void
    {
        $product = $this->createProduct(['base_price' => 12500000]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('12.500.000₫');
    }

    public function test_product_detail_displays_primary_image_and_gallery_thumbnails(): void
    {
        $product = $this->createProduct();

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/primary.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/gallery-1.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('https://example.com/primary.jpg');
        $response->assertSee('https://example.com/gallery-1.jpg');
    }

    public function test_product_detail_displays_fallback_image_when_no_images_exist(): void
    {
        $product = $this->createProduct();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        // Fallback image url or svg exists
        $response->assertSee('images.unsplash.com');
    }

    public function test_product_detail_displays_breadcrumbs(): void
    {
        $category = $this->createCategory(['name' => 'Phòng ăn', 'slug' => 'phong-an']);
        $product = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn ăn Osaka',
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Trang chủ');
        $response->assertSee('Sản phẩm');
        $response->assertSee('Phòng ăn');
        $response->assertSee('Bàn ăn Osaka');
        $response->assertSee(route('products.index', ['category' => 'phong-an']));
    }

    public function test_product_detail_displays_related_products_from_same_category(): void
    {
        $category = $this->createCategory(['name' => 'Phòng làm việc']);
        $otherCategory = $this->createCategory(['name' => 'Phòng tắm']);

        $mainProduct = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn làm việc Nordic',
        ]);

        $related1 = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Ghế xoay da Lento',
            'is_active' => true,
        ]);

        $related2 = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Kệ tài liệu Mino',
            'is_active' => true,
        ]);

        $unrelated = $this->createProduct([
            'category_id' => $otherCategory->id,
            'name' => 'Gương soi phòng tắm',
            'is_active' => true,
        ]);

        $inactiveRelated = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Tủ gỗ cũ ngừng bán',
            'is_active' => false,
        ]);

        $response = $this->get(route('products.show', $mainProduct->slug));

        $response->assertStatus(200);
        $response->assertSee('Ghế xoay da Lento');
        $response->assertSee('Kệ tài liệu Mino');
        $response->assertDontSee('Gương soi phòng tắm');
        $response->assertDontSee('Tủ gỗ cũ ngừng bán');
    }

    public function test_product_detail_uses_semantic_theme_tokens(): void
    {
        $product = $this->createProduct();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('bg-page');
        $response->assertSee('bg-surface');
        $response->assertSee('text-heading');
        $response->assertSee('text-body');
        $response->assertSee('border-ui-border');
    }
}
