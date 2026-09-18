<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyPointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'loyalty_points' => 100, // 100 points
            'loyalty_tier' => 'bronze',
        ]);

        $category = Category::create([
            'name' => 'Ghế Thư Giãn',
            'slug' => 'ghe-thu-gian',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Bập Bênh Mộc An',
            'slug' => 'ghe-bap-benh-moc-an',
            'sku' => 'GBB-MA-01',
            'base_price' => 3000000,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'GBB-MA-V1',
            'price' => 3000000,
            'stock' => 10,
        ]);
    }

    public function test_customer_can_view_loyalty_dashboard(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('account.loyalty'));

        $response->assertOk();
        $response->assertSee('Điểm thưởng & Hạng thành viên');
        $response->assertSee('100');
        $response->assertSee('Đồng');
    }

    public function test_completing_order_awards_loyalty_points(): void
    {
        // Total price 3,000,000 -> 300 points (1 point per 10,000 VND)
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-LOYALTY-01',
            'customer_name' => $this->customer->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Hà Nội',
            'total_price' => 3000000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'shipping',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => 'Ghế Bập Bênh Mộc An',
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 3000000,
        ]);

        /** @var OrderWorkflowService $service */
        $service = app(OrderWorkflowService::class);
        $service->transition($order, Order::STATUS_COMPLETED);

        // Customer had 100 points, earned 300 points = 400 points
        $this->customer->refresh();
        $this->assertEquals(400, $this->customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => 300,
            'type' => 'earn',
        ]);
    }

    public function test_customer_can_redeem_points_at_checkout(): void
    {
        $cart = [$this->variant->id => 1]; // 3,000,000đ
        $token = 'loyalty-token-999';

        // Redeem 50 points = 50,000đ discount
        $response = $this->actingAs($this->customer)
            ->withSession([
                'cart' => $cart,
                'checkout_token' => $token,
                'applied_loyalty_points' => 50,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $token,
                'customer_name' => 'Nguyễn Test',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Đà Nẵng',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();

        $order = Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(50, $order->points_used);
        $this->assertEquals(50000, $order->points_discount);
        $this->assertEquals(2950000, $order->total_price); // 3,000,000 - 50,000

        // User points decremented from 100 to 50
        $this->assertEquals(50, $this->customer->fresh()->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => -50,
            'type' => 'redeem',
        ]);
    }

    public function test_canceling_order_refunds_points(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-CANCEL-REFUND',
            'customer_name' => $this->customer->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Hà Nội',
            'total_price' => 2950000,
            'points_used' => 50,
            'points_discount' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => 'Ghế Bập Bênh Mộc An',
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 3000000,
        ]);

        $currentPoints = $this->customer->loyalty_points; // 100

        /** @var OrderWorkflowService $service */
        $service = app(OrderWorkflowService::class);
        $service->cancel($order);

        $this->assertEquals($currentPoints + 50, $this->customer->fresh()->loyalty_points);
        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => 50,
            'type' => 'refund',
        ]);
    }
}
