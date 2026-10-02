<?php

namespace Tests\Feature\Shipping;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhnShippingTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithVariant(User $user, int $price = 1000000, string $paymentMethod = 'cod'): array
    {
        $category = Category::create([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Sofa Gỗ Mộc An',
            'slug' => 'ghe-sofa-' . uniqid(),
            'sku' => 'SF-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/sofa.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Nâu Sồi',
            'size' => '180x80cm',
            'material' => 'Gỗ Sồi',
            'price' => $price,
            'stock' => 10,
            'sku' => 'SF-SOI-' . strtoupper(uniqid()),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-GHN-' . strtoupper(uniqid()),
            'customer_name' => $user->name,
            'customer_phone' => '0912345678',
            'customer_email' => $user->email,
            'shipping_address' => '123 Đường Nguyễn Trãi',
            'province_id' => 202,
            'province_name' => 'Hồ Chí Minh',
            'district_id' => 1442,
            'district_name' => 'Quận 1',
            'ward_code' => '20101',
            'ward_name' => 'Phường Bến Nghé',
            'total_price' => $price,
            'shipping_fee' => 30000,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Nâu Sồi / 180x80cm',
            'quantity' => 1,
            'price' => $price,
        ]);

        return [$order, $variant];
    }

    public function test_can_fetch_provinces_via_api(): void
    {
        Http::fake([
            '*/master-data/province*' => Http::response([
                'code' => 200,
                'data' => [
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'Code' => 'HN'],
                    ['ProvinceID' => 202, 'ProvinceName' => 'Hồ Chí Minh', 'Code' => 'HCM'],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('shipping.ghn.provinces'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'code'],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'Hà Nội']);
    }

    public function test_can_fetch_districts_via_api(): void
    {
        Http::fake([
            '*/master-data/district*' => Http::response([
                'code' => 200,
                'data' => [
                    ['DistrictID' => 1442, 'DistrictName' => 'Quận 1', 'ProvinceID' => 202],
                    ['DistrictID' => 1443, 'DistrictName' => 'Quận 3', 'ProvinceID' => 202],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('shipping.ghn.districts', 202));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'province_id'],
            ],
        ]);
        $response->assertJsonFragment(['name' => 'Quận 1']);
    }

    public function test_can_fetch_wards_via_api(): void
    {
        Http::fake([
            '*/master-data/ward*' => Http::response([
                'code' => 200,
                'data' => [
                    ['WardCode' => '20101', 'WardName' => 'Phường Bến Nghé', 'DistrictID' => 1442],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('shipping.ghn.wards', 1442));

        $response->assertStatus(200);
        $response->assertJsonFragment(['code' => '20101', 'name' => 'Phường Bến Nghé']);
    }

    public function test_can_calculate_shipping_fee_via_api(): void
    {
        Http::fake([
            '*/v2/shipping-order/fee*' => Http::response([
                'code' => 200,
                'data' => [
                    'total' => 38000,
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('shipping.ghn.calculateFee'), [
            'district_id' => 1442,
            'ward_code' => '20101',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shipping_fee' => 38000,
            'formatted_fee' => '38.000₫',
        ]);
    }

    public function test_checkout_automatically_creates_ghn_shipping_code(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createOrderWithVariant($user, 2000000, 'cod');

        Http::fake([
            '*/v2/shipping-order/create*' => Http::response([
                'code' => 200,
                'data' => [
                    'order_code' => 'L59XN890P',
                    'expected_delivery_time' => '2026-09-25T12:00:00Z',
                    'total_fee' => 35000,
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'test-ghn-token',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Khách Hàng GHN',
                'customer_phone' => '0988776655',
                'shipping_address' => 'Số 10 Đường Lê Duẩn',
                'province_id' => 202,
                'province_name' => 'Hồ Chí Minh',
                'district_id' => 1442,
                'district_name' => 'Quận 1',
                'ward_code' => '20101',
                'ward_name' => 'Phường Bến Nghé',
                'payment_method' => 'cod',
                'checkout_token' => 'test-ghn-token',
            ]);

        $newOrder = Order::latest('id')->first();
        $this->assertNotNull($newOrder);
        $this->assertNotNull($newOrder->ghn_order_code);
        $this->assertEquals('L59XN890P', $newOrder->ghn_order_code);
        $this->assertEquals('ready_to_pick', $newOrder->ghn_status);
    }

    public function test_ghn_webhook_updates_shipment_status(): void
    {
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000);
        $order->update([
            'ghn_order_code' => 'GHN_TEST_WH_01',
            'ghn_status' => 'ready_to_pick',
        ]);

        $response = $this->postJson(route('shipping.ghn.webhook'), [
            'OrderCode' => 'GHN_TEST_WH_01',
            'Status' => 'delivering',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('delivering', $order->fresh()->ghn_status);

        // When status is delivered, order is completed and paid
        $deliverResponse = $this->postJson(route('shipping.ghn.webhook'), [
            'OrderCode' => 'GHN_TEST_WH_01',
            'Status' => 'delivered',
        ]);

        $deliverResponse->assertStatus(200);
        $this->assertEquals('delivered', $order->fresh()->ghn_status);
        $this->assertEquals(Order::STATUS_COMPLETED, $order->fresh()->order_status);
        $this->assertEquals(Order::PAYMENT_PAID, $order->fresh()->payment_status);
    }

    public function test_admin_can_manually_trigger_ghn_order_creation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        [$order] = $this->createOrderWithVariant($user, 1000000);

        Http::fake([
            '*/v2/shipping-order/create*' => Http::response([
                'code' => 200,
                'data' => [
                    'order_code' => 'L59MANUAL01',
                    'expected_delivery_time' => '2026-09-26T10:00:00Z',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.createGhn', $order));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('L59MANUAL01', $order->fresh()->ghn_order_code);
    }

    public function test_parse_dimensions_utility(): void
    {
        $dims1 = \App\Services\Shipping\GhnService::parseDimensions('6 ghế (160 x 85 x 75 cm) · Gỗ sồi tự nhiên');
        $this->assertEquals(['length' => 160, 'width' => 85, 'height' => 75], $dims1);

        $dims2 = \App\Services\Shipping\GhnService::parseDimensions('150 x 80 x 90 cm');
        $this->assertEquals(['length' => 150, 'width' => 80, 'height' => 90], $dims2);

        $dims3 = \App\Services\Shipping\GhnService::parseDimensions('180x80cm');
        $this->assertEquals(['length' => 180, 'width' => 80, 'height' => 20], $dims3);

        $dims4 = \App\Services\Shipping\GhnService::parseDimensions(null);
        $this->assertEquals(['length' => 30, 'width' => 20, 'height' => 20], $dims4);
    }

    public function test_ghn_order_creation_uses_actual_variant_dimensions_and_weight(): void
    {
        $user = User::factory()->create();
        [$order, $variant] = $this->createOrderWithVariant($user, 8490000);

        // Update variant size and product weight to mimic the user's "Đảo Bếp" product
        $variant->update(['size' => '6 ghế (160 x 85 x 75 cm)']);
        $variant->product->update(['weight' => 64.9]);

        $capturedPayload = null;
        Http::fake([
            '*/v2/shipping-order/create*' => function (\Illuminate\Http\Client\Request $request) use (&$capturedPayload) {
                $capturedPayload = $request->data();
                return Http::response([
                    'code' => 200,
                    'data' => [
                        'order_code' => 'GHN_DIM_TEST_01',
                        'expected_delivery_time' => '2026-10-05T12:00:00Z',
                    ],
                ], 200);
            },
        ]);

        $ghnService = app(\App\Services\Shipping\GhnService::class);
        $code = $ghnService->createShippingOrder($order);

        $this->assertEquals('GHN_DIM_TEST_01', $code);
        $this->assertNotNull($capturedPayload);

        // Package dimensions (capped at GHN max 150cm)
        $this->assertEquals(150, $capturedPayload['length']);
        $this->assertEquals(85, $capturedPayload['width']);
        $this->assertEquals(75, $capturedPayload['height']);
        $this->assertEquals(50000, $capturedPayload['weight']); // Capped at max 50kg for courier safety

        // Items array dimensions
        $this->assertNotEmpty($capturedPayload['items']);
        $item = $capturedPayload['items'][0];
        $this->assertEquals(150, $item['length']);
        $this->assertEquals(85, $item['width']);
        $this->assertEquals(75, $item['height']);
        $this->assertEquals(64900, $item['weight']);
    }

    public function test_offline_fallback_provides_full_63_provinces_and_districts(): void
    {
        \Illuminate\Support\Facades\Cache::forget('ghn_provinces');
        \Illuminate\Support\Facades\Cache::forget('ghn_districts_217');

        Http::fake([
            '*/master-data/province*' => Http::response(null, 500),
            '*/master-data/district*' => Http::response(null, 500),
        ]);

        $ghnService = app(\App\Services\Shipping\GhnService::class);
        $provinces = $ghnService->getProvinces();

        $this->assertGreaterThanOrEqual(63, count($provinces));
        $names = collect($provinces)->pluck('name')->all();
        $this->assertContains('Hà Nội', $names);
        $this->assertContains('Hồ Chí Minh', $names);
        $this->assertContains('An Giang', $names);
        $this->assertContains('Cần Thơ', $names);

        // Check districts fallback for An Giang (217)
        $districts = $ghnService->getDistricts(217);
        $this->assertNotEmpty($districts);
        $districtNames = collect($districts)->pluck('name')->all();
        $this->assertContains('Thành phố Long Xuyên', $districtNames);
    }

    public function test_checkout_displays_saved_addresses_for_user(): void
    {
        $user = User::factory()->create([
            'address' => 'Số 99 Đường Hoa Hồng, Phường 2, Quận Phú Nhuận, TP.HCM',
            'phone' => '0933445566',
        ]);

        [$order, $variant] = $this->createOrderWithVariant($user);

        app(\App\Services\CartService::class)->setBuyNowItem($variant->product, 1, $variant);

        $response = $this->actingAs($user)->get(route('checkout.index'));

        $response->assertStatus(200);
        $response->assertSee('Số 99 Đường Hoa Hồng');
        $response->assertSee('0933445566');
    }
}
