<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createProductWithVariant(string $name, int $price, int $stock, bool $isActive = true): array
    {
        $category = Category::create([
            'name' => 'Nội thất phòng khách',
            'slug' => 'pk-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str()->slug($name) . '-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/p.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Tự nhiên',
            'size' => 'Tiêu chuẩn',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'VAR-' . strtoupper(uniqid()),
        ]);

        return [$product, $variant];
    }

    public function test_buy_again_adds_only_available_and_in_stock_items(): void
    {
        $user = $this->createCustomer();

        [$activeProduct, $activeVariant] = $this->createProductWithVariant('Ghế Thư Giãn', 1500000, 5, true);
        [$outOfStockProduct, $outOfStockVariant] = $this->createProductWithVariant('Bàn Trà Gỗ', 2000000, 0, true);
        [$inactiveProduct, $inactiveVariant] = $this->createProductWithVariant('Tủ Đầu Giường', 800000, 5, false);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-BUYAGAIN-1',
            'customer_name' => $user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường Số 1',
            'total_price' => 4300000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        $order->items()->createMany([
            [
                'product_variant_id' => $activeVariant->id,
                'product_name' => $activeProduct->name,
                'variant_info' => 'Tự nhiên / Tiêu chuẩn',
                'quantity' => 1,
                'price' => 1500000,
            ],
            [
                'product_variant_id' => $outOfStockVariant->id,
                'product_name' => $outOfStockProduct->name,
                'variant_info' => 'Tự nhiên / Tiêu chuẩn',
                'quantity' => 1,
                'price' => 2000000,
            ],
            [
                'product_variant_id' => $inactiveVariant->id,
                'product_name' => $inactiveProduct->name,
                'variant_info' => 'Tự nhiên / Tiêu chuẩn',
                'quantity' => 1,
                'price' => 800000,
            ],
        ]);

        $response = $this->actingAs($user)
            ->post(route('orders.buy-again', $order->order_code));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('warning');

        // Only the active, in-stock variant should be in the cart
        $cart = session('cart', []);
        $this->assertArrayHasKey($activeVariant->id, $cart);
        $this->assertEquals(1, $cart[$activeVariant->id]);
        $this->assertArrayNotHasKey($outOfStockVariant->id, $cart);
        $this->assertArrayNotHasKey($inactiveVariant->id, $cart);
    }

    public function test_retry_payment_is_only_allowed_for_unpaid_pending_orders(): void
    {
        $user = $this->createCustomer();

        // 1. Pending unpaid order -> allowed
        $pendingOrder = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-RETRY-01',
            'customer_name' => $user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Hà Nội',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'bank_transfer',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
        ]);

        $responseAllowed = $this->actingAs($user)
            ->post(route('orders.retry-payment', $pendingOrder->order_code), [
                'payment_method' => 'vnpay',
            ]);

        $responseAllowed->assertRedirect(route('payments.vnpay.create', $pendingOrder->order_code));
        $this->assertEquals('vnpay', $pendingOrder->fresh()->payment_method);

        // 2. Completed / Paid order -> blocked
        $paidOrder = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-RETRY-02',
            'customer_name' => $user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Hà Nội',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'vnpay',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_CONFIRMED,
        ]);

        $responseBlocked = $this->actingAs($user)
            ->post(route('orders.retry-payment', $paidOrder->order_code), [
                'payment_method' => 'momo',
            ]);

        $responseBlocked->assertSessionHas('error');
        $this->assertEquals('vnpay', $paidOrder->fresh()->payment_method);
    }

    public function test_print_invoice_authorization_guards(): void
    {
        $owner = $this->createCustomer();
        $stranger = $this->createCustomer();
        $admin = $this->createAdmin();

        $order = Order::create([
            'user_id' => $owner->id,
            'order_code' => 'ORD-PRINT-01',
            'customer_name' => $owner->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Đà Nẵng',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
        ]);

        // 1. Owner can view print invoice
        $ownerResponse = $this->actingAs($owner)->get(route('orders.print', $order->order_code));
        $ownerResponse->assertStatus(200);
        $ownerResponse->assertSee($order->order_code);
        $ownerResponse->assertSee('HÓA ĐƠN BÁN HÀNG');

        // 2. Admin can view print invoice
        $adminResponse = $this->actingAs($admin)->get(route('orders.print', $order->order_code));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee($order->order_code);

        // 3. Stranger is blocked with 403
        $strangerResponse = $this->actingAs($stranger)->get(route('orders.print', $order->order_code));
        $strangerResponse->assertStatus(403);

        // 4. Guest is redirected to login
        auth()->logout();
        $guestResponse = $this->get(route('orders.print', $order->order_code));
        $guestResponse->assertRedirect(route('login'));
    }

    public function test_vnpay_ipn_idempotency_prevents_duplicate_mutations(): void
    {
        $user = $this->createCustomer();

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-VNPAY-IDEM',
            'customer_name' => $user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Sài Gòn',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_TEST_REF_123',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        // Mock VnpayService signature validation
        $vnpayServiceMock = $this->mock(\App\Services\Payments\VnpayService::class);
        $vnpayServiceMock->shouldReceive('verifySignature')->andReturn(true);

        $payload = [
            'vnp_TxnRef' => 'VNP_TEST_REF_123',
            'vnp_Amount' => 100000000, // 1,000,000 * 100 in cents
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '14000123',
            'vnp_SecureHash' => 'mock_hash',
        ];

        // First IPN: updates order to paid
        $response1 = $this->getJson(route('payments.vnpay.ipn', $payload));
        $response1->assertJson(['RspCode' => '00']);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('success', $transaction->fresh()->status);
        $firstPaidAt = $transaction->fresh()->paid_at;

        // Second duplicate IPN: recognized as idempotent without mutating again
        $response2 = $this->getJson(route('payments.vnpay.ipn', $payload));
        $response2->assertJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);

        // Data remains unchanged
        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('success', $transaction->fresh()->status);
        $this->assertEquals($firstPaidAt, $transaction->fresh()->paid_at);
    }

    public function test_financial_metrics_and_revenue_strictly_enforce_order_status_completed_and_payment_status_paid(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'order_status'), 'Canonical column orders.order_status must exist');
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'status'), 'Legacy column orders.status must not exist');

        $admin = $this->createAdmin();

        // 1. Completed but UNPAID order (e.g. COD not collected yet) -> Must NOT count in revenue
        Order::create([
            'order_code' => 'ORD-REV-1',
            'customer_name' => 'Khách 1',
            'customer_phone' => '0901111111',
            'shipping_address' => 'HN',
            'total_price' => 1000000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'completed',
        ]);

        // 2. PAID but shipping/pending order -> Must NOT count in final revenue
        Order::create([
            'order_code' => 'ORD-REV-2',
            'customer_name' => 'Khách 2',
            'customer_phone' => '0902222222',
            'shipping_address' => 'HCM',
            'total_price' => 2000000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'order_status' => 'shipping',
        ]);

        // 3. Completed AND Paid order -> MUST count in revenue
        Order::create([
            'order_code' => 'ORD-REV-3',
            'customer_name' => 'Khách 3',
            'customer_phone' => '0903333333',
            'shipping_address' => 'ĐN',
            'total_price' => 5000000,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        // Check Dashboard
        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);
        $stats = $dashboardResponse->viewData('stats');
        $this->assertEquals(5000000.0, (float) $stats['revenue'], 'Dashboard revenue must only include completed AND paid orders');

        // Check Reports
        $reportResponse = $this->actingAs($admin)->get(route('admin.reports.index', ['preset' => 'all_time']));
        $reportResponse->assertStatus(200);
        $metrics = $reportResponse->viewData('metrics');
        $this->assertEquals(5000000.0, (float) $metrics['total_revenue'], 'Report revenue must only include completed AND paid orders');
    }
}
