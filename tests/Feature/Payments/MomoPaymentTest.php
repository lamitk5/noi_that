<?php

namespace Tests\Feature\Payments;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MomoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.momo.url' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'services.momo.partner_code' => 'MOMO_TEST_PARTNER',
            'services.momo.access_key' => 'MOMO_TEST_ACCESS_KEY',
            'services.momo.secret_key' => 'MOMO_TEST_SECRET_KEY',
        ]);
    }

    private function createOrderWithVariant(User $user, int $price = 1000000, string $paymentMethod = 'momo', string $paymentStatus = 'pending'): array
    {
        $category = Category::create([
            'name' => 'Phòng ăn ' . uniqid(),
            'slug' => 'phong-an-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Ăn ' . uniqid(),
            'slug' => 'ghe-an-' . uniqid(),
            'sku' => 'GA-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/ghean.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Nâu đậm',
            'size' => 'Tiêu chuẩn',
            'material' => 'Gỗ óc chó',
            'price' => $price,
            'stock' => 15,
            'sku' => 'GA-OCCHO-' . strtoupper(uniqid()),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-MOMO-' . strtoupper(uniqid()),
            'customer_name' => $user->name,
            'customer_phone' => '0912345678',
            'customer_email' => $user->email,
            'shipping_address' => 'TP.HCM',
            'total_price' => $price,
            'shipping_fee' => 0,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Nâu đậm / Tiêu chuẩn',
            'quantity' => 1,
            'price' => $price,
        ]);

        return [$order, $variant];
    }

    public function test_owner_can_initiate_momo_payment(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 3000000, 'momo');

        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'partnerCode' => 'MOMO_TEST_PARTNER',
                'orderId' => 'MOMO_123',
                'requestId' => 'REQ_123',
                'amount' => 3000000,
                'responseTime' => 1600000000,
                'message' => 'Success',
                'resultCode' => 0,
                'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?token=MOMO_TOKEN_XYZ',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('payments.momo.create', $order->order_code));

        $response->assertRedirect('https://test-payment.momo.vn/v2/gateway/pay?token=MOMO_TOKEN_XYZ');

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'provider' => 'momo',
            'amount' => 3000000,
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) use ($order) {
            return $request['partnerCode'] === 'MOMO_TEST_PARTNER'
                && $request['amount'] === 3000000
                && $request['requestType'] === 'captureWallet'
                && ! empty($request['signature']);
        });
    }

    public function test_stranger_cannot_initiate_momo_payment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [$order] = $this->createOrderWithVariant($owner, 1000000, 'momo');

        $response = $this->actingAs($stranger)->post(route('payments.momo.create', $order->order_code));
        $response->assertStatus(403);
    }

    public function test_momo_network_failure_handled_gracefully(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'momo');

        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 99,
                'message' => 'Lỗi hệ thống cổng MoMo',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('payments.momo.create', $order->order_code));
        $response->assertRedirect(route('orders.show', $order->order_code));
        $response->assertSessionHas('error');

        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    public function test_momo_return_url_is_read_only_and_does_not_mutate_payment_status(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'momo', 'pending');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_REF_RETURN_TEST',
            'request_id' => 'REQ_RET_TEST',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('payments.momo.return', [
            'orderId' => 'MOMO_REF_RETURN_TEST',
            'resultCode' => '0',
            'message' => 'Success',
        ]));

        $response->assertStatus(200);
        $response->assertSee('MoMo');

        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }

    public function test_valid_momo_ipn_marks_order_as_paid_idempotently_and_returns_204(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createOrderWithVariant($user, 1000000, 'momo', 'pending');
        $initialStock = $variant->stock;

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_REF_IPN_001',
            'request_id' => 'REQ_MOMO_IPN_001',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $data = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'MOMO_REF_IPN_001',
            'requestId' => 'REQ_MOMO_IPN_001',
            'amount' => 1000000,
            'orderInfo' => 'Thanh toan don hang',
            'orderType' => 'momo_wallet',
            'transId' => 246813579,
            'resultCode' => 0,
            'message' => 'Thành công.',
            'payType' => 'qr',
            'responseTime' => 1600000000,
            'extraData' => '',
        ];

        $rawHash = "accessKey=MOMO_TEST_ACCESS_KEY&amount={$data['amount']}&extraData={$data['extraData']}&message={$data['message']}&orderId={$data['orderId']}&orderInfo={$data['orderInfo']}&orderType={$data['orderType']}&partnerCode={$data['partnerCode']}&payType={$data['payType']}&requestId={$data['requestId']}&responseTime={$data['responseTime']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
        $data['signature'] = hash_hmac('sha256', $rawHash, 'MOMO_TEST_SECRET_KEY');

        // 1st IPN Call: Must return HTTP 204 and mark paid
        $response = $this->post(route('payments.momo.ipn'), $data);
        $response->assertNoContent(204);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals('success', $transaction->fresh()->status);
        $this->assertEquals('246813579', $transaction->fresh()->provider_transaction_id);

        // Crucial check: Stock must NOT be decremented again!
        $this->assertEquals($initialStock, $variant->fresh()->stock);

        // 2nd Duplicate IPN Call: Must return HTTP 204 idempotently
        $duplicateResponse = $this->post(route('payments.momo.ipn'), $data);
        $duplicateResponse->assertNoContent(204);

        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertEquals($initialStock, $variant->fresh()->stock);
    }

    public function test_momo_ipn_with_invalid_signature_is_rejected_with_400(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'momo');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_BAD_SIG',
            'request_id' => 'REQ_BAD_SIG',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $data = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'MOMO_BAD_SIG',
            'requestId' => 'REQ_BAD_SIG',
            'amount' => 1000000,
            'resultCode' => 0,
            'signature' => 'FORGED_SIGNATURE',
        ];

        $response = $this->post(route('payments.momo.ipn'), $data);
        $response->assertStatus(400);

        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }

    public function test_momo_ipn_with_tampered_amount_is_rejected(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000, 'momo');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_TAMPER_AMT',
            'request_id' => 'REQ_TAMPER_AMT',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $data = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'MOMO_TAMPER_AMT',
            'requestId' => 'REQ_TAMPER_AMT',
            'amount' => 10000, // 10,000 instead of 1,000,000
            'orderInfo' => 'Thanh toan don hang',
            'orderType' => 'momo_wallet',
            'transId' => 999999,
            'resultCode' => 0,
            'message' => 'Thành công.',
            'payType' => 'qr',
            'responseTime' => 1600000000,
            'extraData' => '',
        ];

        $rawHash = "accessKey=MOMO_TEST_ACCESS_KEY&amount={$data['amount']}&extraData={$data['extraData']}&message={$data['message']}&orderId={$data['orderId']}&orderInfo={$data['orderInfo']}&orderType={$data['orderType']}&partnerCode={$data['partnerCode']}&payType={$data['payType']}&requestId={$data['requestId']}&responseTime={$data['responseTime']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
        $data['signature'] = hash_hmac('sha256', $rawHash, 'MOMO_TEST_SECRET_KEY');

        $response = $this->post(route('payments.momo.ipn'), $data);
        $response->assertStatus(400);

        $this->assertEquals('pending', $order->fresh()->payment_status);
        $this->assertEquals('pending', $transaction->fresh()->status);
    }

    public function test_retry_creates_new_payment_transaction_without_duplicating_order_or_modifying_stock(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createOrderWithVariant($user, 1000000, 'momo', 'pending');
        $initialStock = $variant->stock;

        // Simulate 1st attempt failed
        $firstTxn = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_ATTEMPT_1',
            'request_id' => 'REQ_1',
            'amount' => 1000000,
            'status' => 'failed',
        ]);

        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 0,
                'payUrl' => 'https://test-payment.momo.vn/pay?token=RETRY_TOKEN',
            ], 200),
        ]);

        // Retry payment
        $response = $this->actingAs($user)->post(route('payments.momo.create', $order->order_code));
        $response->assertRedirect('https://test-payment.momo.vn/pay?token=RETRY_TOKEN');

        // Order count must remain 1
        $this->assertEquals(1, Order::count());
        $this->assertEquals(1, $order->items()->count());

        // Stock must be unchanged
        $this->assertEquals($initialStock, $variant->fresh()->stock);

        // A NEW payment transaction must be created
        $this->assertEquals(2, $order->paymentTransactions()->count());
        $newTxn = $order->paymentTransactions()->latest('id')->first();
        $this->assertNotEquals('MOMO_ATTEMPT_1', $newTxn->provider_reference);
        $this->assertEquals('pending', $newTxn->status);
    }
}
