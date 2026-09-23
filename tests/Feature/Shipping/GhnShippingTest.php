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

        $newOrder = Order::latest()->first();
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
}
