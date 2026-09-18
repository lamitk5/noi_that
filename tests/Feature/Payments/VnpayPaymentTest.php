<?php

namespace Tests\Feature\Payments;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payments\VnpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VnpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.vnpay.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'services.vnpay.tmn_code' => 'TEST_TMN_CODE',
            'services.vnpay.hash_secret' => 'SECRET_HASH_KEY_FOR_TESTING',
        ]);
    }

    private function createOrderWithVariant(User $user, int $price = 1000000, string $paymentMethod = 'vnpay', string $paymentStatus = 'pending'): array
    {
        $category = Category::create([
            'name' => 'Phòng khách ' . uniqid(),
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kệ Tivi ' . uniqid(),
            'slug' => 'ke-tivi-' . uniqid(),
            'sku' => 'KT-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/keti.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Gỗ Teak',
            'size' => '200x40cm',
            'material' => 'Gỗ Teak tự nhiên',
            'price' => $price,
            'stock' => 10,
            'sku' => 'KT-TEAK-' . strtoupper(uniqid()),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-VNP-' . strtoupper(uniqid()),
            'customer_name' => $user->name,
            'customer_phone' => '0912345678',
            'customer_email' => $user->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => $price,
            'shipping_fee' => 0,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Gỗ Teak / 200x40cm',
            'quantity' => 1,
            'price' => $price,
        ]);

        return [$order, $variant];
    }

    public function test_owner_can_initiate_vnpay_payment(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 2000000, 'vnpay');

        $response = $this->actingAs($user)->post(route('payments.vnpay.create', $order->order_code));

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', $redirectUrl);
        $this->assertStringContainsString('vnp_Amount=200000000', $redirectUrl);
        $this->assertStringContainsString('vnp_TmnCode=TEST_TMN_CODE', $redirectUrl);
        $this->assertStringContainsString('vnp_SecureHash=', $redirectUrl);

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'amount' => 2000000,
            'status' => 'pending',
        ]);
    }

    public function test_another_user_cannot_initiate_vnpay_payment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [$order] = $this->createOrderWithVariant($owner, 1000000, 'vnpay');

        $response = $this->actingAs($stranger)->post(route('payments.vnpay.create', $order->order_code));
        $response->assertStatus(403);
    }

    public function test_paid_order_cannot_reinitiate_vnpay_payment(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'vnpay', 'paid');

        $response = $this->actingAs($user)->post(route('payments.vnpay.create', $order->order_code));
        $response->assertRedirect(route('orders.show', $order->order_code));
        $response->assertSessionHas('error');
    }

    public function test_vnpay_return_url_is_read_only_and_does_not_mutate_payment_status(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'vnpay', 'pending');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_TEST_REF_123',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        // Build return query with valid signature structure
        $params = [
            'vnp_Amount' => '100000000',
            'vnp_BankCode' => 'NCB',
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => 'Thanh toan don hang',
            'vnp_PayDate' => '20260918120000',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TEST_TMN_CODE',
            'vnp_TransactionNo' => '12345678',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'VNP_TEST_REF_123',
        ];

        ksort($params);
        $hashData = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($k) . '=' . urlencode((string) $v);
            } else {
                $hashData .= urlencode($k) . '=' . urlencode((string) $v);
                $i = 1;
            }
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, 'SECRET_HASH_KEY_FOR_TESTING');

        // Customer browser visits Return URL
        $response = $this->actingAs($user)->get(route('payments.vnpay.return', $params));
        $response->assertStatus(200);
        $response->assertSee('VNPAY');

        // Order and Transaction MUST REMAIN PENDING (Return URL never sets paid!)
        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }

    public function test_valid_vnpay_ipn_marks_order_as_paid_idempotently(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createOrderWithVariant($user, 1500000, 'vnpay', 'pending');
        $initialStock = $variant->stock;

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_IPN_REF_999',
            'amount' => 1500000,
            'status' => 'pending',
        ]);

        $params = [
            'vnp_Amount' => '150000000', // 1,500,000 * 100
            'vnp_BankCode' => 'NCB',
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => 'Thanh toan don hang',
            'vnp_PayDate' => '20260918120000',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TEST_TMN_CODE',
            'vnp_TransactionNo' => '987654321',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'VNP_IPN_REF_999',
        ];

        ksort($params);
        $hashData = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($k) . '=' . urlencode((string) $v);
            } else {
                $hashData .= urlencode($k) . '=' . urlencode((string) $v);
                $i = 1;
            }
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, 'SECRET_HASH_KEY_FOR_TESTING');

        // 1st IPN Call: Must succeed
        $response = $this->get(route('payments.vnpay.ipn', $params));
        $response->assertStatus(200);
        $response->assertJson(['RspCode' => '00', 'Message' => 'Confirm Success']);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('success', $transaction->fresh()->status);
        $this->assertEquals('987654321', $transaction->fresh()->provider_transaction_id);

        // Crucial Check: Stock must NOT be decremented again!
        $this->assertEquals($initialStock, $variant->fresh()->stock);

        // 2nd Duplicate IPN Call: Must return RspCode 02 and leave state unchanged
        $duplicateResponse = $this->get(route('payments.vnpay.ipn', $params));
        $duplicateResponse->assertStatus(200);
        $duplicateResponse->assertJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals($initialStock, $variant->fresh()->stock);
    }

    public function test_vnpay_ipn_with_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'vnpay');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_BAD_SIG',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $params = [
            'vnp_Amount' => '100000000',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'VNP_BAD_SIG',
            'vnp_SecureHash' => 'FORGED_INVALID_HASH',
        ];

        $response = $this->get(route('payments.vnpay.ipn', $params));
        $response->assertStatus(200);
        $response->assertJson(['RspCode' => '97', 'Message' => 'Invalid signature']);

        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }

    public function test_vnpay_ipn_with_tampered_amount_is_rejected(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 2000000, 'vnpay');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_TAMPER_AMT',
            'amount' => 2000000,
            'status' => 'pending',
        ]);

        // Hacker passes 10,000 VND instead of 2,000,000 VND with valid signature for 10,000 VND
        $params = [
            'vnp_Amount' => '1000000', // 10,000 * 100
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'VNP_TAMPER_AMT',
        ];

        ksort($params);
        $hashData = '';
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($k) . '=' . urlencode((string) $v);
            } else {
                $hashData .= urlencode($k) . '=' . urlencode((string) $v);
                $i = 1;
            }
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, 'SECRET_HASH_KEY_FOR_TESTING');

        $response = $this->get(route('payments.vnpay.ipn', $params));
        $response->assertStatus(200);
        $response->assertJson(['RspCode' => '04', 'Message' => 'Invalid amount']);

        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }
}
