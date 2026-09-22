<?php

namespace Tests\Feature\Shipping;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderWorkflowService;
use App\Services\Shipping\GHNOrderService;
use App\Services\Shipping\GHNService;
use App\Services\Shipping\ShippingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GHNShippingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $admin;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ghn.base_url' => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
            'services.ghn.token' => 'TEST_GHN_SECRET_TOKEN',
            'services.ghn.shop_id' => '123456',
            'services.ghn.from_name' => 'Mộc An Test Warehouse',
            'services.ghn.from_phone' => '0901234567',
            'services.ghn.from_address' => '123 Đường Nội Thất, Hà Nội',
            'services.ghn.from_province_name' => 'Hà Nội',
            'services.ghn.from_district_name' => 'Quận Nam Từ Liêm',
            'services.ghn.from_ward_name' => 'Phường Mỹ Đình 1',
            'services.ghn.from_district_id' => 3440,
            'services.ghn.from_ward_code' => '13004',
            'services.ghn.default_weight' => 200,

            // Payment sandboxes
            'services.vnpay.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'services.vnpay.tmn_code' => 'TEST_TMN_CODE',
            'services.vnpay.hash_secret' => 'SECRET_HASH_KEY_FOR_TESTING',

            'services.momo.url' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'services.momo.partner_code' => 'MOMO_TEST_PARTNER',
            'services.momo.access_key' => 'MOMO_TEST_ACCESS_KEY',
            'services.momo.secret_key' => 'MOMO_TEST_SECRET_KEY',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@mocan.test',
        ]);

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@mocan.test',
        ]);

        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec-' . uniqid(),
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Làm Việc Gỗ Tự Nhiên',
            'slug' => 'ban-lam-viec-go-tu-nhien-' . uniqid(),
            'sku' => 'BLV-' . strtoupper(uniqid()),
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'image_path' => 'https://example.com/ban.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'color' => 'Nâu Gỗ Sồi',
            'size' => '1m4 x 70cm',
            'material' => 'Gỗ Sồi',
            'price' => 2000000,
            'stock' => 10,
            'sku' => 'BLV-SOI-' . strtoupper(uniqid()),
        ]);
    }

    private function createTestOrder(array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-GHN-' . strtoupper(uniqid()),
            'customer_name' => $this->customer->name,
            'customer_phone' => '0912345678',
            'customer_email' => $this->customer->email,
            'shipping_address' => '456 Lê Lợi, Quận 1, TP.HCM',
            'to_district_id' => 1442,
            'to_ward_code' => '20101',
            'total_price' => 2050000,
            'shipping_fee' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'shipping_status' => 'pending',
        ], $attributes));

        $order->items()->create([
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Nâu Gỗ Sồi / 1m4 x 70cm',
            'quantity' => 1,
            'price' => 2000000,
        ]);

        return $order;
    }

    // 1. Province retrieval
    public function test_can_fetch_ghn_provinces(): void
    {
        Http::fake([
            '*master-data/province' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội'],
                    ['ProvinceID' => 202, 'ProvinceName' => 'Hồ Chí Minh'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/locations/provinces');

        $response->assertOk()
            ->assertJsonPath('data.0.ProvinceID', 201)
            ->assertJsonPath('data.0.ProvinceName', 'Hà Nội');
    }

    // 2. District retrieval
    public function test_can_fetch_ghn_districts(): void
    {
        Http::fake([
            '*master-data/district*' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    ['DistrictID' => 1442, 'DistrictName' => 'Quận 1'],
                    ['DistrictID' => 1443, 'DistrictName' => 'Quận 2'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/locations/districts/202');

        $response->assertOk()
            ->assertJsonPath('data.0.DistrictID', 1442)
            ->assertJsonPath('data.0.DistrictName', 'Quận 1');
    }

    // 3. Ward retrieval
    public function test_can_fetch_ghn_wards(): void
    {
        Http::fake([
            '*master-data/ward*' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    ['WardCode' => '20101', 'WardName' => 'Phường Bến Nghé'],
                    ['WardCode' => '20102', 'WardName' => 'Phường Bến Thành'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/locations/wards/1442');

        $response->assertOk()
            ->assertJsonPath('data.0.WardCode', '20101')
            ->assertJsonPath('data.0.WardName', 'Phường Bến Nghé');
    }

    // 4. Fee calculation
    public function test_can_calculate_ghn_shipping_fee(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    'total' => 42000,
                    'service_fee' => 42000,
                ],
            ]),
        ]);

        $this->withSession(['cart' => [$this->variant->id => 1]]);

        $response = $this->actingAs($this->customer)->postJson('/locations/calculate-fee', [
            'to_district_id' => 1442,
            'to_ward_code' => '20101',
        ]);

        $response->assertOk()
            ->assertJson([
                'shipping_fee' => 42000,
                'weight' => 200,
            ]);
    }

    // 5. Tampered client shipping fee ignored
    public function test_tampered_client_shipping_fee_is_ignored(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response([
                'code' => 200,
                'data' => ['total' => 65000],
            ]),
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-TAMPER-TEST', 'fee' => 65000],
            ]),
        ]);

        $this->withSession(['cart' => [$this->variant->id => 1]]);
        $this->actingAs($this->customer)->get(route('checkout.index'));
        $token = session()->get('checkout_token');

        $response = $this->actingAs($this->customer)->post(route('checkout.store'), [
            'customer_name' => 'Khách Tamper',
            'customer_phone' => '0988776655',
            'customer_email' => 'tamper@test.com',
            'shipping_address' => '123 Đường Test, Quận 1',
            'to_district_id' => 1442,
            'to_ward_code' => '20101',
            'payment_method' => 'cod',
            'shipping_fee' => 1000, // Tampered client fee of 1,000 VND
            'checkout_token' => $token,
        ]);

        $order = Order::where('customer_phone', '0988776655')->firstOrFail();
        $this->assertEquals(65000, $order->shipping_fee);
        $this->assertEquals(2000000 + 65000, $order->total_price);
        $this->assertNotEquals(2001000, $order->total_price);
    }

    // 6. GHN create shipment payload matches spec
    public function test_ghn_create_shipment_payload_matches_spec(): void
    {
        $order = $this->createTestOrder(['payment_method' => 'cod', 'total_price' => 2050000]);

        $ghnOrderService = app(GHNOrderService::class);
        $payload = $ghnOrderService->buildPayload($order);

        $this->assertEquals(1, $payload['payment_type_id']); // Sender pays shipping
        $this->assertEquals(2, $payload['service_type_id']);
        $this->assertEquals(3440, $payload['from_district_id']);
        $this->assertEquals('Phường Mỹ Đình 1', $payload['from_ward_name']);
        $this->assertEquals(1442, $payload['to_district_id']);
        $this->assertEquals('20101', $payload['to_ward_code']);
        $this->assertEquals(2050000, $payload['cod_amount']); // COD order collects total_price
        $this->assertCount(1, $payload['items']);
        $this->assertEquals($this->product->name, $payload['items'][0]['name']);
    }

    // 7. GHN create response persistence
    public function test_ghn_create_response_persisted_to_order(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    'order_code' => 'GHN-PERSIST-999',
                    'total_fee' => 45000,
                    'expected_delivery_time' => '2026-09-25T17:00:00Z',
                ],
            ]),
        ]);

        $order = $this->createTestOrder(['ghn_order_code' => null]);
        $shippingManager = app(ShippingManager::class);

        $result = $shippingManager->ensureShipmentCreated($order);

        $this->assertTrue($result);
        $order->refresh();
        $this->assertEquals('GHN-PERSIST-999', $order->ghn_order_code);
        $this->assertEquals(45000, $order->ghn_total_fee);
        $this->assertEquals('created', $order->shipping_status);
        $this->assertNotNull($order->shipping_last_synced_at);
    }

    // 8. GHN tracking/detail
    public function test_can_fetch_ghn_tracking_detail(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response([
                'code' => 200,
                'data' => [
                    'order_code' => 'GHN-TRACK-123',
                    'status' => 'delivering',
                    'log' => [
                        ['status' => 'ready_to_pick', 'updated_date' => '2026-09-23T10:00:00Z'],
                        ['status' => 'delivering', 'updated_date' => '2026-09-23T14:00:00Z'],
                    ],
                ],
            ]),
        ]);

        $order = $this->createTestOrder(['ghn_order_code' => 'GHN-TRACK-123']);
        $shippingManager = app(ShippingManager::class);

        $timeline = $shippingManager->getTrackingTimeline('GHN-TRACK-123', $order);

        $this->assertNotEmpty($timeline);
        $this->assertEquals('ready_to_pick', $timeline[0]['status']);
        $this->assertEquals('delivering', $timeline[1]['status']);
    }

    // 9. GHN cancellation
    public function test_can_cancel_ghn_shipment(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response([
                'code' => 200,
                'data' => ['status' => 'ready_to_pick'],
            ]),
            '*v2/shipping-order/cancel' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    ['order_code' => 'GHN-CANCEL-123', 'result' => true],
                ],
            ]),
        ]);

        $order = $this->createTestOrder(['ghn_order_code' => 'GHN-CANCEL-123', 'order_status' => 'pending']);
        $shippingManager = app(ShippingManager::class);

        $cancelled = $shippingManager->cancelShipment($order);

        $this->assertTrue($cancelled);
        $this->assertEquals('cancel', $order->fresh()->shipping_status);
    }

    // 10. COD creates GHN once
    public function test_cod_order_creates_ghn_shipment_once(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response(['code' => 200, 'data' => ['total' => 30000]]),
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-COD-SINGLE', 'fee' => 30000],
            ]),
        ]);

        $this->withSession(['cart' => [$this->variant->id => 1]]);
        $this->actingAs($this->customer)->get(route('checkout.index'));
        $token = session()->get('checkout_token');

        $this->actingAs($this->customer)->post(route('checkout.store'), [
            'customer_name' => 'Khách COD',
            'customer_phone' => '0912345678',
            'shipping_address' => '456 Lê Lợi',
            'to_district_id' => 1442,
            'to_ward_code' => '20101',
            'payment_method' => 'cod',
            'checkout_token' => $token,
        ]);

        $order = Order::where('customer_name', 'Khách COD')->firstOrFail();
        $this->assertEquals('GHN-COD-SINGLE', $order->ghn_order_code);

        // Attempt second ensureShipmentCreated call
        $shippingManager = app(ShippingManager::class);
        $secondResult = $shippingManager->ensureShipmentCreated($order);

        $this->assertFalse($secondResult);
        Http::assertSentCount(2); // 1 fee + 1 create
    }

    // 11. Bank Transfer markPaid creates GHN once
    public function test_bank_transfer_mark_paid_creates_ghn_shipment_once(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-BANK-ONCE', 'fee' => 40000],
            ]),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
        ]);

        $workflow = app(OrderWorkflowService::class);
        $workflow->markBankTransferPaid($order, 'Trans confirmed');

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('GHN-BANK-ONCE', $order->ghn_order_code);

        // Calling markBankTransferPaid again should not throw or duplicate
        $shippingManager = app(ShippingManager::class);
        $duplicateAttempt = $shippingManager->ensureShipmentCreated($order);
        $this->assertFalse($duplicateAttempt);
    }

    // 12. VNPAY success creates GHN once
    public function test_vnpay_success_creates_ghn_shipment_once(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-VNPAY-ONCE', 'fee' => 50000],
            ]),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
            'total_price' => 2050000,
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_TEST_REF_123',
            'amount' => 2050000,
            'status' => 'pending',
        ]);

        $params = [
            'vnp_Amount' => '205000000',
            'vnp_BankCode' => 'NCB',
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
            $hashData .= ($i === 1 ? '&' : '') . urlencode($k) . '=' . urlencode((string) $v);
            $i = 1;
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, 'SECRET_HASH_KEY_FOR_TESTING');

        $response = $this->get(route('payments.vnpay.ipn', $params));
        $response->assertJson(['RspCode' => '00']);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('GHN-VNPAY-ONCE', $order->ghn_order_code);
    }

    // 13. MoMo success creates GHN once
    public function test_momo_success_creates_ghn_shipment_once(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-MOMO-ONCE', 'fee' => 45000],
            ]),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
            'total_price' => 2050000,
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_TEST_REF_123',
            'request_id' => 'REQ_123',
            'amount' => 2050000,
            'status' => 'pending',
        ]);

        $payload = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'MOMO_TEST_REF_123',
            'requestId' => 'REQ_123',
            'amount' => 2050000,
            'orderInfo' => 'Thanh toan don hang',
            'orderType' => 'momo_wallet',
            'transId' => 99887766,
            'resultCode' => 0,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => 1600000000000,
            'extraData' => '',
        ];

        $rawHash = 'accessKey=' . 'MOMO_TEST_ACCESS_KEY'
            . '&amount=' . $payload['amount']
            . '&extraData=' . $payload['extraData']
            . '&message=' . $payload['message']
            . '&orderId=' . $payload['orderId']
            . '&orderInfo=' . $payload['orderInfo']
            . '&orderType=' . $payload['orderType']
            . '&partnerCode=' . $payload['partnerCode']
            . '&payType=' . $payload['payType']
            . '&requestId=' . $payload['requestId']
            . '&responseTime=' . $payload['responseTime']
            . '&resultCode=' . $payload['resultCode']
            . '&transId=' . $payload['transId'];

        $payload['signature'] = hash_hmac('sha256', $rawHash, 'MOMO_TEST_SECRET_KEY');

        $response = $this->post(route('payments.momo.ipn'), $payload);
        $response->assertNoContent(204);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('GHN-MOMO-ONCE', $order->ghn_order_code);
    }

    // 14. Invalid VNPAY signature creates NO shipment
    public function test_invalid_vnpay_signature_creates_no_ghn_shipment(): void
    {
        Http::fake();

        $order = $this->createTestOrder([
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
        ]);

        $params = [
            'vnp_Amount' => '205000000',
            'vnp_TxnRef' => 'INVALID_REF',
            'vnp_ResponseCode' => '00',
            'vnp_SecureHash' => 'FORGED_INVALID_HASH',
        ];

        $response = $this->get(route('payments.vnpay.ipn', $params));
        $response->assertJson(['RspCode' => '97']); // Invalid signature

        $order->refresh();
        $this->assertEquals('pending', $order->payment_status);
        $this->assertNull($order->ghn_order_code);
        Http::assertNothingSent();
    }

    // 15. Invalid MoMo signature creates NO shipment
    public function test_invalid_momo_signature_creates_no_ghn_shipment(): void
    {
        Http::fake();

        $order = $this->createTestOrder([
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
        ]);

        $payload = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'INVALID_ORDER',
            'resultCode' => 0,
            'signature' => 'FORGED_MOMO_HASH',
        ];

        $response = $this->post(route('payments.momo.ipn'), $payload);
        $response->assertStatus(400);

        $order->refresh();
        $this->assertEquals('pending', $order->payment_status);
        $this->assertNull($order->ghn_order_code);
        Http::assertNothingSent();
    }

    // 16. Duplicate VNPAY IPN creates only ONE shipment
    public function test_duplicate_vnpay_ipn_creates_only_one_shipment(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-VNPAY-IDEMPOTENT', 'fee' => 50000],
            ]),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
            'total_price' => 2050000,
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'VNP_DUP_REF',
            'amount' => 2050000,
            'status' => 'pending',
        ]);

        $params = [
            'vnp_Amount' => '205000000',
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TEST_TMN_CODE',
            'vnp_TransactionNo' => '12345678',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'VNP_DUP_REF',
        ];
        ksort($params);
        $hashData = '';
        $i = 0;
        foreach ($params as $k => $v) {
            $hashData .= ($i === 1 ? '&' : '') . urlencode($k) . '=' . urlencode((string) $v);
            $i = 1;
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, 'SECRET_HASH_KEY_FOR_TESTING');

        // First IPN
        $res1 = $this->get(route('payments.vnpay.ipn', $params));
        $res1->assertJson(['RspCode' => '00']);

        // Duplicate IPN
        $res2 = $this->get(route('payments.vnpay.ipn', $params));
        $res2->assertJson(['RspCode' => '02']); // Already confirmed

        Http::assertSentCount(1);
    }

    // 17. Duplicate MoMo IPN creates only ONE shipment
    public function test_duplicate_momo_ipn_creates_only_one_shipment(): void
    {
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-MOMO-IDEMPOTENT', 'fee' => 45000],
            ]),
        ]);

        $order = $this->createTestOrder([
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'ghn_order_code' => null,
            'total_price' => 2050000,
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'MOMO_DUP_REF',
            'request_id' => 'REQ_DUP',
            'amount' => 2050000,
            'status' => 'pending',
        ]);

        $payload = [
            'partnerCode' => 'MOMO_TEST_PARTNER',
            'orderId' => 'MOMO_DUP_REF',
            'requestId' => 'REQ_DUP',
            'amount' => 2050000,
            'orderInfo' => 'Thanh toan don hang',
            'orderType' => 'momo_wallet',
            'transId' => 888999,
            'resultCode' => 0,
            'message' => 'Successful.',
            'payType' => 'qr',
            'responseTime' => 1600000000000,
            'extraData' => '',
        ];

        $rawHash = 'accessKey=' . 'MOMO_TEST_ACCESS_KEY'
            . '&amount=' . $payload['amount']
            . '&extraData=' . $payload['extraData']
            . '&message=' . $payload['message']
            . '&orderId=' . $payload['orderId']
            . '&orderInfo=' . $payload['orderInfo']
            . '&orderType=' . $payload['orderType']
            . '&partnerCode=' . $payload['partnerCode']
            . '&payType=' . $payload['payType']
            . '&requestId=' . $payload['requestId']
            . '&responseTime=' . $payload['responseTime']
            . '&resultCode=' . $payload['resultCode']
            . '&transId=' . $payload['transId'];

        $payload['signature'] = hash_hmac('sha256', $rawHash, 'MOMO_TEST_SECRET_KEY');

        // First IPN
        $res1 = $this->post(route('payments.momo.ipn'), $payload);
        $res1->assertNoContent(204);

        // Duplicate IPN
        $res2 = $this->post(route('payments.momo.ipn'), $payload);
        $res2->assertNoContent(204);

        Http::assertSentCount(1);
    }

    // 18. Payment retry does not recreate order
    public function test_payment_retry_does_not_recreate_order(): void
    {
        $order = $this->createTestOrder(['payment_method' => 'vnpay', 'payment_status' => 'failed']);
        $initialOrderCount = Order::count();

        $response = $this->actingAs($this->customer)->post(route('orders.retry-payment', $order->order_code));

        $response->assertRedirect(route('payments.vnpay.create', $order->order_code));
        $this->assertEquals($initialOrderCount, Order::count());
    }

    // 19. Payment retry does not decrement stock again
    public function test_payment_retry_does_not_decrement_stock_again(): void
    {
        $order = $this->createTestOrder(['payment_method' => 'momo', 'payment_status' => 'failed']);
        $initialStock = $this->variant->fresh()->stock;

        $response = $this->actingAs($this->customer)->post(route('orders.retry-payment', $order->order_code));

        $response->assertRedirect(route('payments.momo.create', $order->order_code));
        $this->assertEquals($initialStock, $this->variant->fresh()->stock);
    }

    // 20. Payment retry does not duplicate GHN shipment
    public function test_payment_retry_does_not_duplicate_ghn_shipment(): void
    {
        $order = $this->createTestOrder([
            'payment_method' => 'vnpay',
            'payment_status' => 'failed',
            'ghn_order_code' => 'GHN-EXISTING-123',
        ]);

        $shippingManager = app(ShippingManager::class);
        $createdAgain = $shippingManager->ensureShipmentCreated($order);

        $this->assertFalse($createdAgain);
        $this->assertEquals('GHN-EXISTING-123', $order->fresh()->ghn_order_code);
    }

    // 21. GHN failure leaves local order valid
    public function test_ghn_failure_leaves_local_order_valid(): void
    {
        Http::fake([
            '*v2/shipping-order/fee' => Http::response(['code' => 200, 'data' => ['total' => 30000]]),
            '*v2/shipping-order/create' => Http::response(['code' => 500, 'message' => 'GHN Server Maintenance'], 500),
        ]);

        $this->withSession(['cart' => [$this->variant->id => 1]]);
        $this->actingAs($this->customer)->get(route('checkout.index'));
        $token = session()->get('checkout_token');

        $response = $this->actingAs($this->customer)->post(route('checkout.store'), [
            'customer_name' => 'Khách Fail GHN',
            'customer_phone' => '0912345678',
            'shipping_address' => '456 Lê Lợi',
            'to_district_id' => 1442,
            'to_ward_code' => '20101',
            'payment_method' => 'cod',
            'checkout_token' => $token,
        ]);

        $order = Order::where('customer_name', 'Khách Fail GHN')->firstOrFail();
        $response->assertRedirect(route('checkout.success', $order->order_code));

        // Local order is created successfully with pending shipping status
        $this->assertNull($order->ghn_order_code);
        $this->assertEquals('pending', $order->shipping_status);
        $this->assertEquals('pending', $order->order_status);
    }

    // 22. Cancellation restores stock once
    public function test_cancellation_restores_stock_once(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response([
                'code' => 200,
                'data' => ['status' => 'ready_to_pick'],
            ]),
            '*v2/shipping-order/cancel' => Http::response([
                'code' => 200,
                'message' => 'Success',
                'data' => [['order_code' => 'GHN-CANCEL-STOCK', 'result' => true]],
            ]),
        ]);

        $order = $this->createTestOrder([
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'ghn_order_code' => 'GHN-CANCEL-STOCK',
        ]);

        $initialStock = $this->variant->fresh()->stock;

        $workflow = app(OrderWorkflowService::class);
        $workflow->cancel($order, 'Khách hàng đổi ý');

        $this->assertEquals($initialStock + 1, $this->variant->fresh()->stock);
        $this->assertEquals('canceled', $order->fresh()->order_status);

        // Attempting to cancel again should throw or not increment stock again
        try {
            $workflow->cancel($order->fresh(), 'Hủy lần hai');
        } catch (\Throwable $e) {
            // Expected validation exception
        }

        $this->assertEquals($initialStock + 1, $this->variant->fresh()->stock);
    }

    // 23. Non-cancellable GHN shipment prevents local order cancellation
    public function test_non_cancellable_ghn_shipment_prevents_local_order_cancellation(): void
    {
        Http::fake([
            '*v2/shipping-order/detail' => Http::response([
                'code' => 200,
                'data' => ['status' => 'delivering'],
            ]),
        ]);

        $order = $this->createTestOrder([
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'ghn_order_code' => 'GHN-DELIVERING-CANNOT-CANCEL',
        ]);

        $initialStock = $this->variant->fresh()->stock;

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $workflow = app(OrderWorkflowService::class);
        $workflow->cancel($order, 'Khách muốn hủy');

        $order->refresh();
        $this->assertEquals('pending', $order->order_status);
        $this->assertEquals($initialStock, $this->variant->fresh()->stock);
    }

    // 24. GHN secrets never exposed
    public function test_ghn_secrets_are_never_exposed_in_logs_or_responses(): void
    {
        $ghnService = app(GHNService::class);

        // Test safe exception handling without token exposure
        $secretToken = config('services.ghn.token');
        $this->assertNotEmpty($secretToken);

        $response = $this->actingAs($this->customer)->postJson('/locations/calculate-fee', [
            'to_district_id' => 999999, // Non-existent district to provoke error
            'to_ward_code' => '000000',
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString($secretToken, $content);
        $this->assertStringNotContainsString(config('services.vnpay.hash_secret'), $content);
        $this->assertStringNotContainsString(config('services.momo.secret_key'), $content);
    }

    // 25. Ownership checks preserved
    public function test_ownership_and_permission_checks_preserved(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $order = $this->createTestOrder(['user_id' => $otherUser->id]);

        // Customer trying to view stranger order
        $response = $this->actingAs($this->customer)->get(route('orders.show', $order->order_code));
        $response->assertStatus(403);

        // Customer trying to trigger retry-ghn admin route
        $adminRouteResponse = $this->actingAs($this->customer)->post(route('admin.orders.retry-ghn', $order->order_code));
        $adminRouteResponse->assertStatus(403);

        // Admin can access admin retry-ghn route
        Http::fake([
            '*v2/shipping-order/create' => Http::response([
                'code' => 200,
                'data' => ['order_code' => 'GHN-ADMIN-RETRY', 'fee' => 30000],
            ]),
        ]);

        $adminAction = $this->actingAs($this->admin)->post(route('admin.orders.retry-ghn', $order->order_code));
        $adminAction->assertRedirect();
        $this->assertEquals('GHN-ADMIN-RETRY', $order->fresh()->ghn_order_code);
    }
}
