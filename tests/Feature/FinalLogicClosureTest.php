<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalLogicClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Product $product;
    protected ProductVariant $variant;
    protected OrderWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = app(OrderWorkflowService::class);

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@mocan.test',
            'name' => 'Quản trị viên',
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@mocan.test',
            'name' => 'Nguyễn Văn Khách',
            'role' => 'customer',
            'loyalty_points' => 50,
        ]);

        $category = Category::create([
            'name' => 'Bàn Trà Sofa',
            'slug' => 'ban-tra-sofa',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Trà Gỗ Sồi Hiện Đại',
            'slug' => 'ban-tra-go-soi-hien-dai',
            'sku' => 'BT-SOI-01',
            'base_price' => 3500000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'image_path' => 'https://images.unsplash.com/photo-1533090161767-e6ffed986c88',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'color' => 'Gỗ Tự Nhiên',
            'size' => '120x60x45cm',
            'material' => 'Gỗ sồi nhập khẩu',
            'price' => 3500000,
            'stock' => 5,
            'sku' => 'BT-SOI-VAR-01',
        ]);
    }

    public function test_checkout_fails_when_variant_out_of_stock_or_insufficient(): void
    {
        $this->actingAs($this->customer);

        // Variant only has 5 in stock, attempt to buy 6
        $this->withSession([
            'cart' => [$this->variant->id => 6],
            'checkout_token' => 'valid-checkout-token',
        ]);

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'customer_email' => 'customer@mocan.test',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'payment_method' => 'cod',
            'checkout_token' => 'valid-checkout-token',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('orders', [
            'customer_email' => 'customer@mocan.test',
        ]);
        $this->assertEquals(5, $this->variant->fresh()->stock);
    }

    public function test_completed_order_cannot_regress_to_any_prior_status(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-COMPLETED',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => Order::STATUS_COMPLETED,
            'shipping_status' => 'delivered',
        ]);

        $this->expectException(ValidationException::class);
        $this->workflowService->transition($order, Order::STATUS_SHIPPING);
    }

    public function test_completed_order_cannot_be_canceled(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-COMPLETED-2',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => Order::STATUS_COMPLETED,
            'shipping_status' => 'delivered',
        ]);

        $this->expectException(ValidationException::class);
        $this->workflowService->cancel($order);
    }

    public function test_canceled_order_cannot_progress(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-CANCELED',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_CANCELED,
            'shipping_status' => 'cancelled',
        ]);

        $this->expectException(ValidationException::class);
        $this->workflowService->transition($order, Order::STATUS_CONFIRMED);
    }

    public function test_cod_transition_to_completed_sets_paid_and_awards_loyalty_points(): void
    {
        $initialPoints = $this->customer->loyalty_points;

        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-COD-WORKFLOW',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_SHIPPING,
            'shipping_status' => 'delivering',
        ]);

        $completedOrder = $this->workflowService->transition($order, Order::STATUS_COMPLETED);

        $this->assertEquals(Order::STATUS_COMPLETED, $completedOrder->order_status);
        $this->assertEquals('paid', $completedOrder->payment_status);

        // 3,500,000 / 10,000 = 350 points
        $expectedPoints = $initialPoints + 350;
        $this->assertEquals($expectedPoints, $this->customer->fresh()->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'order_id' => $order->id,
            'type' => 'earn',
            'points' => 350,
        ]);
    }

    public function test_cancellation_restores_stock_and_refunds_redeemed_loyalty_points(): void
    {
        $this->variant->update(['stock' => 3]);
        $this->customer->update(['loyalty_points' => 10]);

        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-CANCEL-RESTORE',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'total_price' => 3480000,
            'shipping_fee' => 0,
            'points_used' => 20,
            'points_discount' => 20000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
            'shipping_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ Tự Nhiên / 120x60x45cm',
            'quantity' => 2,
            'price' => 3500000,
        ]);

        $canceledOrder = $this->workflowService->cancel($order);

        $this->assertEquals(Order::STATUS_CANCELED, $canceledOrder->order_status);
        // Stock should be restored from 3 to 5
        $this->assertEquals(5, $this->variant->fresh()->stock);
        // Customer redeemed 20 points, should get refunded 20 points (10 + 20 = 30)
        $this->assertEquals(30, $this->customer->fresh()->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'order_id' => $order->id,
            'type' => 'refund',
            'points' => 20,
        ]);
    }

    public function test_customer_cannot_review_product_without_completed_and_paid_purchase(): void
    {
        $this->actingAs($this->customer);

        // Case 1: No purchase at all
        $response = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Sản phẩm rất đẹp!',
        ]);
        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);

        // Case 2: Order exists but status is pending/unpaid
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-PENDING-UNPAID',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
            'shipping_status' => 'pending',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ Tự Nhiên',
            'quantity' => 1,
            'price' => 3500000,
        ]);

        $response2 = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Chưa nhận hàng nhưng đánh giá trước!',
        ]);
        $response2->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);

        // Case 3: Complete and mark order as paid -> review now succeeds
        $order->update([
            'order_status' => Order::STATUS_COMPLETED,
            'payment_status' => 'paid',
        ]);

        $response3 = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Gỗ sồi hoàn thiện rất tỉ mỉ, bóng đẹp!',
        ]);
        $response3->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Gỗ sồi hoàn thiện rất tỉ mỉ, bóng đẹp!',
        ]);
    }

    public function test_revenue_and_admin_reports_only_include_completed_paid_orders(): void
    {
        $this->actingAs($this->admin);

        // Pending unpaid order: 5,000,000 VND
        Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-PENDING-UNCOUNTED',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, TP.HCM',
            'total_price' => 5000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
            'shipping_status' => 'pending',
            'created_at' => now(),
        ]);

        // Canceled order: 2,000,000 VND
        Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-CANCELED-UNCOUNTED',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, TP.HCM',
            'total_price' => 2000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'canceled',
            'order_status' => Order::STATUS_CANCELED,
            'shipping_status' => 'cancelled',
            'created_at' => now(),
        ]);

        // Completed + Paid order: 3,500,000 VND
        $completedOrder = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-COMPLETED-COUNTED',
            'customer_name' => 'Nguyễn Văn Khách',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ, TP.HCM',
            'total_price' => 3500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => Order::STATUS_COMPLETED,
            'shipping_status' => 'delivered',
            'created_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $completedOrder->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ Tự Nhiên',
            'quantity' => 1,
            'price' => 3500000,
        ]);

        // Test Dashboard revenue
        $dashboardRes = $this->get(route('admin.dashboard'));
        $dashboardRes->assertOk();
        $this->assertEquals(3500000, $dashboardRes->viewData('stats')['revenue']);
        $this->assertEquals(1, $dashboardRes->viewData('stats')['completed_orders']);

        // Test Report Controller metrics
        $reportRes = $this->get(route('admin.reports.index', ['preset' => '30days']));
        $reportRes->assertOk();
        $this->assertEquals(3500000, $reportRes->viewData('metrics')['total_revenue']);
        $this->assertEquals(1, $reportRes->viewData('metrics')['total_orders']);
    }

    public function test_health_check_sitemap_and_robots(): void
    {
        $health = $this->get(route('health'));
        $health->assertOk();
        $health->assertJsonFragment(['status' => 'healthy', 'database' => 'connected']);

        $sitemap = $this->get(route('seo.sitemap'));
        $sitemap->assertOk();
        $sitemap->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $robots = $this->get(route('seo.robots'));
        $robots->assertOk();
        $robots->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $robots->assertSee('User-agent: *');
        $robots->assertSee('Sitemap:');
    }
}
