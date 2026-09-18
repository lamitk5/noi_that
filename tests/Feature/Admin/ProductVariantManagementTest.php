<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_variant_to_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Ghế', 'slug' => 'ghe', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Ăn Thonet',
            'slug' => 'ghe-an-thonet',
            'sku' => 'GHE-THONET',
            'base_price' => 1200000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.variants.store', $product), [
            'sku' => 'GHE-THONET-WHITE',
            'color' => 'Trắng Sữa',
            'size' => 'Tiêu chuẩn',
            'material' => 'Gỗ Dẻ Gai',
            'price' => 1250000,
            'stock' => 20,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'GHE-THONET-WHITE',
            'color' => 'Trắng Sữa',
            'price' => 1250000,
            'stock' => 20,
        ]);
    }

    public function test_admin_can_update_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Bàn', 'slug' => 'ban', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Học Sinh',
            'slug' => 'ban-hoc-sinh',
            'sku' => 'BAN-HOC',
            'base_price' => 800000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BAN-HOC-V1',
            'color' => 'Hồng',
            'price' => 800000,
            'stock' => 5,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $variant]), [
            'sku' => 'BAN-HOC-V1-PINK',
            'color' => 'Hồng Pastel',
            'price' => 850000,
            'stock' => 12,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('BAN-HOC-V1-PINK', $variant->fresh()->sku);
        $this->assertEquals('Hồng Pastel', $variant->fresh()->color);
        $this->assertEquals(850000, (int) $variant->fresh()->price);
        $this->assertEquals(12, $variant->fresh()->stock);
    }

    public function test_cannot_delete_variant_with_order_items(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $category = Category::create(['name' => 'Tủ', 'slug' => 'tu', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Tủ Giày Thông Minh',
            'slug' => 'tu-giay-thong-minh',
            'sku' => 'TU-GIAY',
            'base_price' => 1800000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TU-GIAY-3T',
            'color' => 'Vân Gỗ',
            'price' => 1800000,
            'stock' => 4,
        ]);

        // Create an order containing this variant
        $order = Order::create([
            'order_code' => 'ORD-TEST-VAR',
            'user_id' => $customer->id,
            'customer_name' => 'Nguyen Van A',
            'customer_email' => 'a@example.com',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Hai Ba Trung, HN',
            'total_price' => 1800000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Tủ Giày Thông Minh',
            'variant_info' => 'Vân Gỗ - 3 Tầng',
            'price' => 1800000,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.variants.destroy', [$product, $variant]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
    }

    public function test_can_delete_unused_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Kệ', 'slug' => 'ke', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kệ Góc',
            'slug' => 'ke-goc',
            'sku' => 'KE-GOC',
            'base_price' => 300000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KE-GOC-UNUSED',
            'price' => 300000,
            'stock' => 5,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.variants.destroy', [$product, $variant]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
