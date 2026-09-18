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
        $response->assertSee('LUXURY HOME');
        $response->assertSee('Sofa');
    }

    public function test_catalog_filtering_by_category_and_search(): void
    {
        $livingCategory = Category::where('slug', 'phong-khach')->first();

        // Test category filter
        $response = $this->get('/products?category_id=' . $livingCategory->id);
        $response->assertStatus(200);
        $response->assertSee('Sofa');

        // Test keyword search
        $searchResponse = $this->get('/products?search=Ceramic');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Ceramic');
    }

    public function test_cart_operations_and_stock_validation(): void
    {
        $product = Product::where('stock_quantity', '>', 5)->first();

        // 1. Add to cart
        $addResponse = $this->post('/cart/add/' . $product->id, [
            'quantity' => 2,
        ]);
        $addResponse->assertSessionHas('furniture_cart');

        // 2. View cart
        $cartPage = $this->get('/cart');
        $cartPage->assertStatus(200);
        $cartPage->assertSee($product->name);

        // 3. Exceed stock validation
        $exceedResponse = $this->post('/cart/add/' . $product->id, [
            'quantity' => 99999,
        ]);
        $exceedResponse->assertSessionHas('error');
    }

    public function test_checkout_creates_order_and_decrements_stock_in_transaction(): void
    {
        $product = Product::where('stock_quantity', '>=', 5)->first();
        $initialStock = $product->stock_quantity;
        $orderQty = 2;

        // Add to cart
        $this->post('/cart/add/' . $product->id, ['quantity' => $orderQty]);

        // Process checkout
        $response = $this->post('/checkout', [
            'customer_name' => 'Nguyễn Thị Bích',
            'customer_email' => 'bich@example.com',
            'customer_phone' => '0909112233',
            'shipping_address' => '456 Lê Duẩn, Quận 1, TP. Hồ Chí Minh',
            'payment_method' => 'cod',
            'notes' => 'Giao hàng cẩn thận',
        ]);

        $order = Order::where('customer_phone', '0909112233')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.success', ['order_number' => $order->order_number]));

        // Check stock decremented
        $product->refresh();
        $this->assertEquals($initialStock - $orderQty, $product->stock_quantity);

        // Check order items
        $this->assertCount(1, $order->items);
        $this->assertEquals($product->id, $order->items->first()->product_id);
    }

    public function test_admin_middleware_blocks_guest_and_customers(): void
    {
        // 1. Guest blocked
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect(route('login'));

        // 2. Regular customer blocked
        $customer = User::where('role', 'customer')->first();
        $customerResponse = $this->actingAs($customer)->get('/admin');
        $customerResponse->assertRedirect(route('login'));

        // 3. Admin allowed
        $admin = User::where('role', 'admin')->first();
        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Bảng Điều Khiển');
    }

    public function test_admin_can_update_order_status_and_stock_is_restored_on_cancellation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $product = Product::first();
        $initialStock = $product->stock_quantity;

        // Create pending order with 2 quantities
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@test.com',
            'customer_phone' => '0911223344',
            'shipping_address' => 'Sample Address',
            'subtotal' => 1000000,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500000,
            'quantity' => 2,
            'total' => 1000000,
        ]);

        // Cancel order as admin
        $response = $this->actingAs($admin)->put(route('admin.orders.updateStatus', $order), [
            'order_status' => Order::STATUS_CANCELLED,
            'payment_status' => Order::PAYMENT_FAILED,
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->order_status);

        // Verify stock was restored (+2)
        $product->refresh();
        $this->assertEquals($initialStock + 2, $product->stock_quantity);
    }
}
