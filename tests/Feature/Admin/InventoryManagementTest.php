<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory(): void
    {
        $response = $this->get(route('admin.inventory.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_inventory(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('admin.inventory.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_inventory_dashboard_and_badges(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Bàn Trà', 'slug' => 'ban-tra', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Trà Mặt Đá',
            'slug' => 'ban-tra-mat-da',
            'sku' => 'BT-DA',
            'base_price' => 3200000,
            'is_active' => true,
        ]);

        $v1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BT-DA-WHITE',
            'color' => 'Trắng',
            'price' => 3200000,
            'stock' => 2, // Low stock (<= 5)
        ]);

        $v2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BT-DA-BLACK',
            'color' => 'Đen',
            'price' => 3200000,
            'stock' => 0, // Out of stock
        ]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index'));

        $response->assertStatus(200);
        $response->assertSee('Quản lý Tồn kho');
        $response->assertSee('BT-DA-WHITE');
        $response->assertSee('BT-DA-BLACK');
        $response->assertSee('Sắp hết');
        $response->assertSee('Hết hàng');
    }

    public function test_admin_can_update_variant_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Kệ', 'slug' => 'ke', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kệ TV Gỗ Sồi',
            'slug' => 'ke-tv-go-soi',
            'sku' => 'KTV-SOI',
            'base_price' => 4200000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KTV-SOI-2M',
            'price' => 4200000,
            'stock' => 3,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.inventory.update', $variant), [
            'stock' => 25,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(25, $variant->fresh()->stock);
    }

    public function test_inventory_filter_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Giường', 'slug' => 'giuong', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Giường Ngủ Hiện Đại',
            'slug' => 'giuong-ngu-hien-dai',
            'sku' => 'G-HD',
            'base_price' => 7000000,
            'is_active' => true,
        ]);

        $vLow = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'G-HD-LOW',
            'price' => 7000000,
            'stock' => 3,
        ]);

        $vHigh = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'G-HD-HIGH',
            'price' => 7000000,
            'stock' => 50,
        ]);

        // Filter low stock
        $responseLow = $this->actingAs($admin)->get(route('admin.inventory.index', ['filter' => 'low_stock']));
        $responseLow->assertStatus(200);
        $responseLow->assertSee('G-HD-LOW');
        $responseLow->assertDontSee('G-HD-HIGH');
    }
}
