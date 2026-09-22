<?php

namespace Tests\Feature\RealWorldValidation;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealWorldOrderGoldenFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@mocan.test',
            'name' => 'Quản trị viên',
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@mocan.test',
            'name' => 'Khách hàng Thảo',
            'role' => 'customer',
        ]);

        $category = Category::create([
            'name' => 'Bàn ăn cao cấp',
            'slug' => 'ban-an-cao-cap',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Ăn 6 Ghế Gỗ Me Tây',
            'slug' => 'ban-an-6-ghe-go-me-tay',
            'sku' => 'BA-METAY-01',
            'base_price' => 12000000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'image_path' => 'https://images.unsplash.com/photo-1615066390971-03e4e1c36ddf',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'color' => 'Gỗ Tự Nhiên',
            'size' => '1m8 x 80cm',
            'material' => 'Gỗ me tây nguyên tấm',
            'price' => 12000000,
            'stock' => 10,
            'sku' => 'BA-METAY-VAR-01',
        ]);
    }

    public function test_complete_golden_flow_from_checkout_through_fulfillment_revenue_and_review(): void
    {
        // 1. Initial State: Revenue is 0
        $this->actingAs($this->admin);
        $dashboardBefore = $this->get(route('admin.dashboard'));
        $dashboardBefore->assertOk();
        $this->assertEquals(0, $dashboardBefore->viewData('stats')['revenue']);

        // 2. Customer adds item to cart and checks out with COD
        $this->actingAs($this->customer);

        // Put in session cart
        $this->withSession(['cart' => [$this->variant->id => 1]]);

        // Establish checkout token
        $this->get(route('checkout.index'))->assertOk();
        $checkoutToken = session()->get('checkout_token');

        $initialStock = $this->variant->fresh()->stock;
        $this->assertEquals(10, $initialStock);

        $checkoutRes = $this->post(route('checkout.store'), [
            'customer_name' => 'Khách hàng Thảo',
            'customer_phone' => '0912345678',
            'customer_email' => 'customer@mocan.test',
            'shipping_address' => '789 Điện Biên Phủ, Bình Thạnh, TP.HCM',
            'payment_method' => 'cod',
            'checkout_token' => $checkoutToken,
        ]);

        $order = Order::where('user_id', $this->customer->id)->latest('id')->firstOrFail();
        $checkoutRes->assertRedirect(route('checkout.success', $order->order_code));

        // Variant stock decremented
        $this->assertEquals(9, $this->variant->fresh()->stock);
        $this->assertEquals('pending', $order->order_status);
        $this->assertEquals('pending', $order->payment_status);

        // Revenue must STILL be 0 because order is pending
        $this->actingAs($this->admin);
        $dashboardPending = $this->get(route('admin.dashboard'));
        $this->assertEquals(0, $dashboardPending->viewData('stats')['revenue']);

        // Review attempt while order is pending must fail
        $this->actingAs($this->customer);
        $pendingReviewRes = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Bàn rất chất lượng',
        ]);
        $pendingReviewRes->assertSessionHasErrors('purchase');

        // 3. Admin transitions: pending -> confirmed
        $this->actingAs($this->admin);
        $this->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ])->assertRedirect();
        $this->assertEquals('confirmed', $order->fresh()->order_status);

        // 4. Admin transitions: confirmed -> packed
        $this->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'packed',
        ])->assertRedirect();
        $this->assertEquals('packed', $order->fresh()->order_status);

        // 5. Admin transitions: packed -> shipping
        $this->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'shipping',
            'shipping_carrier' => 'ghn',
        ])->assertRedirect();
        $order = $order->fresh();
        $this->assertEquals('shipping', $order->order_status);
        $this->assertNotEmpty($order->tracking_code);

        // Still not completed -> Revenue is still 0
        $dashboardShipping = $this->get(route('admin.dashboard'));
        $this->assertEquals(0, $dashboardShipping->viewData('stats')['revenue']);

        // 6. Admin transitions: shipping -> completed
        $this->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'completed',
        ])->assertRedirect();
        $order = $order->fresh();

        // 7. Verify COD order_status = completed AND payment_status = paid
        $this->assertEquals('completed', $order->order_status);
        $this->assertEquals('paid', $order->payment_status);

        // 8. Financial Integrity: Dashboard revenue now recognizes the completed & paid order
        $dashboardCompleted = $this->get(route('admin.dashboard'));
        $this->assertEquals((float) $order->total_price, $dashboardCompleted->viewData('stats')['revenue']);

        // 9. Verified Review is now accepted
        $this->actingAs($this->customer);
        $reviewRes = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Bàn gỗ Me Tây hoàn thiện quá xuất sắc, giao hàng đóng gói rất cẩn thận!',
        ]);
        $reviewRes->assertRedirect(route('products.show', $this->product->slug) . '#reviews');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'rating' => 5,
        ]);
    }
}
