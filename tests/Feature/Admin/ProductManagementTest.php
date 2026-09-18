<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_products(): void
    {
        $response = $this->get(route('admin.products.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_products(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('admin.products.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_products_list_with_stock_info(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Phòng Khách', 'slug' => 'phong-khach', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Da Cao Cấp Italy',
            'slug' => 'sofa-da-cao-cap-italy',
            'sku' => 'SOFA-ITA-01',
            'base_price' => 15000000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Nâu Da Bò',
            'price' => 15000000,
            'stock' => 8,
            'sku' => 'SOFA-ITA-01-BR',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Quản lý Sản phẩm');
        $response->assertSee('Sofa Da Cao Cấp Italy');
        $response->assertSee('SOFA-ITA-01');
        $response->assertSee('Còn hàng');
    }

    public function test_admin_can_create_product_with_initial_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Phòng Ăn', 'slug' => 'phong-an', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Bàn Ăn Mango 4 Ghế',
            'base_price' => 4500000,
            'short_description' => 'Bộ bàn ăn nhỏ gọn phong cách Hàn Quốc',
            'description' => 'Chất liệu gỗ cao su tự nhiên đã qua tẩm sấy.',
            'is_active' => 1,
            'variant_color' => 'Tự Nhiên',
            'variant_size' => '120x75cm',
            'variant_stock' => 15,
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::where('name', 'Bàn Ăn Mango 4 Ghế')->first();
        $this->assertNotNull($product);
        $this->assertEquals('ban-an-mango-4-ghe', $product->slug);
        $this->assertNotEmpty($product->sku);
        $this->assertTrue($product->is_active);

        $this->assertCount(1, $product->variants);
        $variant = $product->variants->first();
        $this->assertEquals('Tự Nhiên', $variant->color);
        $this->assertEquals(15, $variant->stock);

        $response->assertRedirect(route('admin.products.edit', $product));
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Phòng Làm Việc', 'slug' => 'phong-lam-viec', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Giám Đốc Royal',
            'slug' => 'ban-giam-doc-royal',
            'sku' => 'BGD-ROYAL',
            'base_price' => 12000000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Bàn Giám Đốc Royal Luxury 2026',
            'slug' => 'ban-giam-doc-royal',
            'sku' => 'BGD-ROYAL',
            'base_price' => 14500000,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('Bàn Giám Đốc Royal Luxury 2026', $product->fresh()->name);
        $this->assertEquals(14500000, (int) $product->fresh()->base_price);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Kệ Trang Trí', 'slug' => 'ke-trang-tri', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kệ Gỗ Treo Tường',
            'slug' => 'ke-go-treo-tuong',
            'sku' => 'KE-TREO-01',
            'base_price' => 650000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.products.toggle-status', $product));
        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.products.toggle-status', $product));
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_destroy_deactivates_product_instead_of_hard_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Đèn', 'slug' => 'den', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Đèn Sàn Đọc Sách',
            'slug' => 'den-san-doc-sach',
            'sku' => 'DEN-SAN-01',
            'base_price' => 890000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_deactivated_product_is_hidden_from_storefront(): void
    {
        $category = Category::create(['name' => 'Tủ', 'slug' => 'tu', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Tủ Quần Áo Đã Ẩn',
            'slug' => 'tu-quan-ao-da-an',
            'sku' => 'TU-AN-01',
            'base_price' => 5000000,
            'is_active' => false,
        ]);

        // Detail page returns 404
        $response = $this->get(route('products.show', $product->slug));
        $response->assertStatus(404);

        // Not visible in catalog index
        $catalogResponse = $this->get(route('products.index'));
        $catalogResponse->assertDontSee('Tủ Quần Áo Đã Ẩn');
    }
}
