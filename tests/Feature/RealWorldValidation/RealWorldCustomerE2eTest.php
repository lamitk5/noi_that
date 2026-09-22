<?php

namespace Tests\Feature\RealWorldValidation;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealWorldCustomerE2eTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Product $product;
    protected ProductVariant $variant1;
    protected ProductVariant $variant2;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Phòng Khách',
            'slug' => 'phong-khach',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bàn Trà Gỗ Sồi Hiện Đại',
            'slug' => 'ban-tra-go-soi-hien-dai',
            'sku' => 'BT-SO-01',
            'base_price' => 3500000,
            'is_active' => true,
            'description' => 'Mô tả bàn trà gỗ sồi cao cấp',
        ]);

        $this->variant1 = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'BT-SO-01-NAT',
            'color' => 'Tự Nhiên',
            'material' => 'Gỗ Sồi',
            'price' => 3500000,
            'stock' => 10,
            'is_default' => true,
        ]);

        $this->variant2 = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'BT-SO-01-WAL',
            'color' => 'Óc Chó',
            'material' => 'Gỗ Sồi sơn óc chó',
            'price' => 3800000,
            'stock' => 5,
            'is_default' => false,
        ]);

        $this->customer = User::create([
            'name' => 'Trần Thu Thảo (Khách VIP)',
            'email' => 'customer@mocan.test',
            'password' => bcrypt('Customer@123'),
            'role' => 'customer',
            'loyalty_points' => 1250,
            'loyalty_tier' => 'silver',
        ]);
    }

    public function test_guest_e2e_shopping_exploration(): void
    {
        // 1. Home
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();
        $homeRes->assertSee('Trợ lý Mộc An');
        // Ensure legacy widget is NOT rendered
        $homeRes->assertDontSee('Tư vấn & Hỗ trợ');

        // 2. Search Autocomplete
        $searchRes = $this->getJson(route('search.suggestions', ['q' => 'Bàn']));
        $searchRes->assertOk();
        $searchRes->assertJsonStructure(['products', 'categories']);

        // 3. Catalog & Filters & Sort
        $catalogRes = $this->get(route('products.index', [
            'category' => 'phong-khach',
            'sort' => 'price_asc',
        ]));
        $catalogRes->assertOk();
        $catalogRes->assertSee('Bàn Trà Gỗ Sồi Hiện Đại');

        // 4. Product Detail (PDP)
        $pdpRes = $this->get(route('products.show', $this->product->slug));
        $pdpRes->assertOk();
        $pdpRes->assertSee('3.500.000');
        $pdpRes->assertSee('Còn hàng');

        // Check recently viewed recorded in session
        $this->assertContains($this->product->id, session()->get('recently_viewed', []));

        // 5. Quick View API
        $quickViewRes = $this->getJson(route('products.quick-view', $this->product->id));
        $quickViewRes->assertOk();
        $quickViewRes->assertJsonStructure(['id', 'name', 'variants']);

        // 6. Compare Products
        $compareRes = $this->get(route('products.compare', ['products' => [$this->product->id]]));
        $compareRes->assertOk();

        $compareDataRes = $this->getJson(route('products.compare.data', ['ids' => $this->product->id]));
        $compareDataRes->assertOk();
        $compareDataRes->assertJsonStructure(['products']);
        $compareDataRes->assertJsonFragment(['id' => $this->product->id]);

        // 7. Add to Cart as Guest
        $addCartRes = $this->post(route('cart.store'), [
            'variant_id' => $this->variant1->id,
            'quantity' => 2,
        ]);
        $addCartRes->assertRedirect(route('cart.index'));
        $this->assertEquals(2, session()->get('cart')[$this->variant1->id]);
    }

    public function test_customer_login_cart_merge_and_checkout_flow(): void
    {
        // 1. Guest puts item in session cart
        $this->withSession(['cart' => [$this->variant1->id => 1]]);

        // 2. Customer Logs In
        $loginRes = $this->post(route('login.store'), [
            'email' => 'customer@mocan.test',
            'password' => 'Customer@123',
        ]);
        $loginRes->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->customer);

        // 3. Cart merged into user_cart_items table
        $this->assertDatabaseHas('user_cart_items', [
            'user_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
        ]);

        // 4. Saved Address
        $addrRes = $this->post(route('account.addresses.store'), [
            'recipient_name' => 'Trần Thu Thảo',
            'phone' => '0901234567',
            'address_line' => '123 Nguyễn Huệ, Phường Bến Nghé',
            'city' => 'Hồ Chí Minh',
            'district' => 'Quận 1',
            'is_default' => 1,
        ]);
        $addrRes->assertRedirect(route('account.addresses.index'));
        $address = $this->customer->addresses()->first();
        $this->assertNotNull($address);

        // 5. Active Voucher Apply
        $voucher = Voucher::create([
            'code' => 'CHAOHAI2026',
            'name' => 'Giảm 200k đơn từ 3 triệu',
            'type' => 'fixed',
            'value' => 200000,
            'min_order_amount' => 3000000,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $voucherRes = $this->from(route('checkout.index'))->post(route('checkout.apply-voucher'), [
            'code' => 'CHAOHAI2026',
        ]);
        $voucherRes->assertRedirect(route('checkout.index'));
        $this->assertEquals($voucher->code, session()->get('applied_voucher_code'));

        // 6. Apply Loyalty Points (Customer has 100 points)
        $pointsRes = $this->from(route('checkout.index'))->post(route('checkout.apply-points'), [
            'points' => 50,
        ]);
        $pointsRes->assertRedirect(route('checkout.index'));
        $this->assertEquals(50, session()->get('applied_loyalty_points'));

        // 7. Checkout COD
        $stockBefore = $this->variant1->fresh()->stock;
        $this->assertEquals(10, $stockBefore);

        // Visit checkout to establish session checkout_token
        $checkoutPageRes = $this->get(route('checkout.index'));
        $checkoutPageRes->assertOk();
        $checkoutToken = session()->get('checkout_token');
        $this->assertNotEmpty($checkoutToken);

        $checkoutRes = $this->post(route('checkout.store'), [
            'customer_name' => 'Trần Thu Thảo',
            'customer_phone' => '0901234567',
            'customer_email' => 'customer@mocan.test',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP HCM',
            'payment_method' => 'cod',
            'checkout_token' => $checkoutToken,
        ]);

        $order = Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $checkoutRes->assertRedirect(route('checkout.success', $order->order_code));

        // Variant stock decremented by 1
        $this->assertEquals($stockBefore - 1, $this->variant1->fresh()->stock);
        $this->assertEquals('pending', $order->order_status);
        $this->assertEquals('pending', $order->payment_status);

        // 8. Order Details, History & Print Invoice
        $orderDetailRes = $this->get(route('orders.show', $order->order_code));
        $orderDetailRes->assertOk();
        $orderDetailRes->assertSee($order->order_code);

        $orderIndexRes = $this->get(route('orders.index'));
        $orderIndexRes->assertOk();
        $orderIndexRes->assertSee($order->order_code);

        $printRes = $this->get(route('orders.print', $order->order_code));
        $printRes->assertOk();
        $printRes->assertSee('HÓA ĐƠN BÁN HÀNG');

        // 9. Buy Again feature loads items into cart
        $buyAgainRes = $this->post(route('orders.buy-again', $order->order_code));
        $buyAgainRes->assertRedirect(route('cart.index'));
        $this->assertNotEmpty(session()->get('cart'));
    }

    public function test_customer_order_cancellation_restores_stock_exactly_once(): void
    {
        $this->actingAs($this->customer);

        // Create a pending COD order with variant2 (quantity = 2)
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-CANCEL-TEST-001',
            'customer_name' => 'Trần Thu Thảo',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ',
            'total_price' => 7000000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant2->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ Óc Chó',
            'quantity' => 2,
            'price' => 3500000,
        ]);

        // Variant stock was 5 before checkout
        $this->variant2->decrement('stock', 2);
        $this->assertEquals(3, $this->variant2->fresh()->stock);

        // First cancel: restores stock to 5
        $cancelRes = $this->post(route('orders.cancel', $order->order_code));
        $cancelRes->assertRedirect();
        $this->assertEquals(5, $this->variant2->fresh()->stock);
        $this->assertEquals('canceled', $order->fresh()->order_status);

        // Duplicate cancel attempt must be rejected and stock remains 5
        $dupCancelRes = $this->post(route('orders.cancel', $order->order_code));
        $dupCancelRes->assertSessionHas('error');
        $this->assertEquals(5, $this->variant2->fresh()->stock);
    }

    public function test_review_submission_strictly_requires_completed_and_paid_order(): void
    {
        $this->actingAs($this->customer);

        // 1. Uncompleted order -> Review fails
        $orderPending = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-REV-PENDING',
            'customer_name' => 'Trần Thu Thảo',
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Nguyễn Huệ',
            'total_price' => 3500000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $orderPending->id,
            'product_variant_id' => $this->variant1->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ Sồi',
            'quantity' => 1,
            'price' => 3500000,
        ]);

        $failReviewRes = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Bàn rất đẹp!',
        ]);
        $failReviewRes->assertSessionHasErrors('purchase');

        // 2. Transition order to completed + paid
        $orderPending->update([
            'order_status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $successReviewRes = $this->post(route('reviews.store', $this->product->slug), [
            'rating' => 5,
            'comment' => 'Bàn rất đẹp, đóng gói cẩn thận!',
        ]);
        $successReviewRes->assertRedirect(route('products.show', $this->product->slug) . '#reviews');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'rating' => 5,
        ]);
    }
}
