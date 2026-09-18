<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurnitureShopBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_loads_successfully_with_furniture_data(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Mộc An');
        $response->assertSee('Sofa');
    }

    public function test_guest_can_track_order_with_code_and_phone(): void
    {
        $product = Product::first();
        $variant = $product->variants()->first();

        $order = Order::create([
            'order_code' => 'ORD-TRACK-TEST01',
            'customer_name' => 'Nguyễn Văn Test',
            'customer_phone' => '0988776655',
            'customer_email' => 'testtrack@example.com',
            'shipping_address' => '123 Đường Test, Hà Nội',
            'total_price' => 5000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'shipping',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant?->id,
            'product_name' => $product->name,
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 5000000,
        ]);

        $response = $this->get('/tra-cuu-don-hang?order_number=' . $order->order_code . '&customer_phone=' . $order->customer_phone);
        $response->assertStatus(200);
        $response->assertSee($order->order_code);
        $response->assertSee($product->name);
        $response->assertSee('Đang giao hàng');
    }

    public function test_admin_middleware_blocks_guest_and_customers(): void
    {
        // 1. Guest blocked -> redirected to login
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect(route('login'));

        // 2. Regular customer blocked -> 403 Forbidden
        $customer = User::where('role', 'customer')->first();
        $customerResponse = $this->actingAs($customer)->get('/admin');
        $customerResponse->assertStatus(403);

        // 3. Admin allowed -> 200 OK
        $admin = User::where('role', 'admin')->first();
        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Bảng điều khiển');
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. View category list
        $response = $this->actingAs($admin)->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertSee('Phòng khách');

        // 2. Create new category
        $createResponse = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Phòng tắm cao cấp',
            'description' => 'Nội thất phòng tắm gỗ Teak chống ẩm',
            'is_active' => true,
        ]);
        $createResponse->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Phòng tắm cao cấp',
            'slug' => 'phong-tam-cao-cap',
        ]);
    }

    public function test_admin_can_create_product_and_default_variant_is_synced(): void
    {
        $admin = User::where('role', 'admin')->first();
        $category = Category::first();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Ghế Đôn Tròn Gỗ Ash',
            'price' => 1500000,
            'stock_quantity' => 20,
            'material' => 'Gỗ tần bì Ash',
            'dimensions' => '40 x 40 x 45 cm',
            'color' => 'Gỗ sáng tự nhiên',
            'short_description' => 'Ghế đôn tròn gọn nhẹ, bền bỉ',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Ghế Đôn Tròn Gỗ Ash')->first();
        $this->assertNotNull($product);
        $this->assertEquals(1500000, $product->base_price);

        // Verify default variant was created for customer shopping compatibility
        $this->assertCount(1, $product->variants);
        $variant = $product->variants->first();
        $this->assertEquals(20, $variant->stock);
        $this->assertEquals(1500000, $variant->price);
    }

    public function test_admin_can_update_order_status_and_stock_is_restored_on_cancellation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $product = Product::first();
        $variant = $product->variants()->first();
        $initialVariantStock = $variant->stock;

        // Create pending order
        $order = Order::create([
            'order_code' => 'ORD-ADMIN-CANCEL01',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@test.com',
            'customer_phone' => '0911223344',
            'shipping_address' => 'Sample Address',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Tiêu chuẩn',
            'price' => 500000,
            'quantity' => 2,
        ]);

        // Cancel order as admin
        $response = $this->actingAs($admin)->put(route('admin.orders.updateStatus', $order), [
            'order_status' => 'canceled',
            'payment_status' => 'failed',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('canceled', $order->order_status);

        // Verify stock was restored (+2)
        $variant->refresh();
        $this->assertEquals($initialVariantStock + 2, $variant->stock);
    }
}

