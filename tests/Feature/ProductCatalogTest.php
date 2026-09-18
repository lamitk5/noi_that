<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach',
            'description' => 'Nội thất phòng khách',
            'is_active' => true,
        ], $attributes));
    }

    private function createProduct(array $attributes = []): Product
    {
        $category = $attributes['category_id'] ?? null
            ? null
            : $this->createCategory();

        $name = $attributes['name'] ?? 'Sofa Mộc An';
        $slug = $attributes['slug'] ?? (\Illuminate\Support\Str::slug($name) . '-' . uniqid());

        return Product::create(array_merge([
            'category_id' => $category?->id ?? $attributes['category_id'],
            'name' => $name,
            'slug' => $slug,
            'sku' => 'SF-' . uniqid(),
            'short_description' => 'Mô tả ngắn sofa',
            'description' => 'Mô tả chi tiết sofa',
            'base_price' => 5000000,
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_can_access_product_catalog(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Sản phẩm');
    }

    public function test_authenticated_user_can_access_product_catalog(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertStatus(200);
    }

    public function test_catalog_displays_active_products_only(): void
    {
        $category = $this->createCategory(['name' => 'Phòng ăn', 'slug' => 'phong-an']);

        $activeProduct = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn ăn Gỗ Sồi',
            'sku' => 'BA-001',
            'base_price' => 7500000,
            'is_active' => true,
        ]);

        $inactiveProduct = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Ghế Cũ Tạm Ngưng',
            'sku' => 'GC-002',
            'base_price' => 1200000,
            'is_active' => false,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Bàn ăn Gỗ Sồi');
        $response->assertDontSee('Ghế Cũ Tạm Ngưng');
    }

    public function test_catalog_displays_product_details_including_price_category_and_primary_image(): void
    {
        $category = $this->createCategory(['name' => 'Phòng ngủ', 'slug' => 'phong-ngu']);

        $product = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Giường ngủ Tối Giản',
            'sku' => 'GN-001',
            'base_price' => 8900000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/giuong-ngu.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Giường ngủ Tối Giản');
        $response->assertSee('Phòng ngủ');
        $response->assertSee('8.900.000₫');
        $response->assertSee('https://example.com/giuong-ngu.jpg');
    }

    public function test_catalog_shows_empty_state_when_no_products_exist(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Không tìm thấy sản phẩm phù hợp.');
        $response->assertSee('Xóa bộ lọc');
    }

    public function test_catalog_can_search_products_by_name(): void
    {
        $category = $this->createCategory();

        $match1 = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn làm việc Tần Bì',
            'sku' => 'BLV-001',
        ]);

        $match2 = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn trà Cà Phê',
            'sku' => 'BT-002',
        ]);

        $nonMatch = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Kệ sách Treo Tường',
            'sku' => 'KS-003',
        ]);

        $response = $this->get(route('products.index', ['q' => 'Bàn']));

        $response->assertStatus(200);
        $response->assertSee('Bàn làm việc Tần Bì');
        $response->assertSee('Bàn trà Cà Phê');
        $response->assertDontSee('Kệ sách Treo Tường');
    }

    public function test_catalog_can_filter_by_category_slug(): void
    {
        $catLiving = $this->createCategory(['name' => 'Phòng khách', 'slug' => 'phong-khach']);
        $catKitchen = $this->createCategory(['name' => 'Phòng bếp', 'slug' => 'phong-bep']);

        $livingProduct = $this->createProduct([
            'category_id' => $catLiving->id,
            'name' => 'Sofa Phòng Khách',
            'sku' => 'SF-PK-1',
        ]);

        $kitchenProduct = $this->createProduct([
            'category_id' => $catKitchen->id,
            'name' => 'Tủ bếp Mini',
            'sku' => 'TB-PK-2',
        ]);

        $response = $this->get(route('products.index', ['category' => 'phong-khach']));

        $response->assertStatus(200);
        $response->assertSee('Sofa Phòng Khách');
        $response->assertDontSee('Tủ bếp Mini');
    }

    public function test_catalog_handles_invalid_category_slug_gracefully(): void
    {
        $this->createProduct(['name' => 'Sofa Cao Cấp', 'sku' => 'SF-999']);

        $response = $this->get(route('products.index', ['category' => 'danh-muc-khong-ton-tai']));

        $response->assertStatus(200);
        $response->assertSee('Không tìm thấy sản phẩm phù hợp.');
    }

    public function test_catalog_sorts_by_latest_by_default(): void
    {
        $category = $this->createCategory();

        $older = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Sản phẩm Cũ hơn',
            'sku' => 'SP-OLD',
            'created_at' => now()->subDays(5),
        ]);

        $newer = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Sản phẩm Mới nhất',
            'sku' => 'SP-NEW',
            'created_at' => now(),
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Sản phẩm Mới nhất', 'Sản phẩm Cũ hơn']);
    }

    public function test_catalog_sorts_by_price_ascending(): void
    {
        $category = $this->createCategory();

        $cheap = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Đèn bàn Gỗ Nhỏ',
            'sku' => 'DB-01',
            'base_price' => 850000,
        ]);

        $expensive = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Sofa Da Cao Cấp',
            'sku' => 'SF-02',
            'base_price' => 25000000,
        ]);

        $response = $this->get(route('products.index', ['sort' => 'price_asc']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Đèn bàn Gỗ Nhỏ', 'Sofa Da Cao Cấp']);
    }

    public function test_catalog_sorts_by_price_descending(): void
    {
        $category = $this->createCategory();

        $cheap = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Gối tựa Lưng',
            'sku' => 'GT-01',
            'base_price' => 350000,
        ]);

        $expensive = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Bàn ăn 8 ghế Gỗ Mun',
            'sku' => 'BA-02',
            'base_price' => 45000000,
        ]);

        $response = $this->get(route('products.index', ['sort' => 'price_desc']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Bàn ăn 8 ghế Gỗ Mun', 'Gối tựa Lưng']);
    }

    public function test_catalog_invalid_sort_falls_back_to_latest(): void
    {
        $category = $this->createCategory();

        $older = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Sản phẩm Thứ hai',
            'sku' => 'SP-TH2',
            'created_at' => now()->subDay(),
        ]);

        $newer = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Sản phẩm Thứ nhất',
            'sku' => 'SP-TH1',
            'created_at' => now(),
        ]);

        $response = $this->get(route('products.index', ['sort' => 'drop_database_sort']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Sản phẩm Thứ nhất', 'Sản phẩm Thứ hai']);
    }

    public function test_catalog_pagination_and_query_string_preservation(): void
    {
        $category = $this->createCategory(['slug' => 'phong-khach']);

        // Tạo 15 sản phẩm (page size 12)
        for ($i = 1; $i <= 15; $i++) {
            $this->createProduct([
                'category_id' => $category->id,
                'name' => sprintf('Sản phẩm số %02d', $i),
                'sku' => sprintf('SP-%02d', $i),
                'base_price' => $i * 100000,
            ]);
        }

        $responsePage1 = $this->get(route('products.index', ['category' => 'phong-khach']));
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('Sản phẩm số 15'); // latest first
        $responsePage1->assertSee('page=2');

        $responsePage2 = $this->get(route('products.index', ['category' => 'phong-khach', 'page' => 2]));
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Sản phẩm số 01');
    }

    public function test_catalog_uses_semantic_theme_tokens(): void
    {
        $this->createProduct(['name' => 'Sản phẩm Mẫu']);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('bg-page');
        $response->assertSee('bg-surface');
        $response->assertSee('text-heading');
        $response->assertSee('border-ui-border');
    }
}
