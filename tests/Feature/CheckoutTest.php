<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(int $price = 1000000, int $stock = 10, bool $isActive = true, array $variantAttributes = []): array
    {
        $category = Category::create([
            'name' => 'Phòng ăn',
            'slug' => 'phong-an-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Ăn Mộc An ' . uniqid(),
            'slug' => 'ban-an-' . uniqid(),
            'sku' => 'BA-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/ban-an.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'color' => 'Gỗ Sồi',
            'size' => '160x80cm',
            'material' => 'Gỗ sồi tự nhiên',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'BA-SOI-' . strtoupper(uniqid()),
        ], $variantAttributes));

        return [$product, $variant];
    }

    public function test_guest_checkout_redirects_to_login(): void
    {
        $response = $this->get(route('checkout.index'));
        $response->assertRedirect(route('login'));

        $postResponse = $this->post(route('checkout.store'), []);
        $postResponse->assertRedirect(route('login'));
    }

    public function test_empty_cart_redirects_to_cart_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('checkout.index'));
        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');

        $postResponse = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0987654321',
            'shipping_address' => 'Hà Nội',
            'payment_method' => 'cod',
            'checkout_token' => 'valid-token',
        ]);
        $postResponse->assertRedirect(route('cart.index'));
    }

    public function test_authenticated_user_with_cart_can_view_checkout_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Trần Văn Nam',
            'email' => 'nam@example.com',
        ]);
        [$product, $variant] = $this->createProductWithVariant(1500000, 5);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 2]])
            ->get(route('checkout.index'));

        $response->assertStatus(200);
        $response->assertSee('Thanh toán');
        $response->assertSee($product->name);
        $response->assertSee('3.000.000');
        $response->assertSee('Trần Văn Nam');
        $response->assertSee('nam@example.com');
        $response->assertSessionHas('checkout_token');
    }

    public function test_checkout_validates_customer_fields(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'test-token',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => '',
                'customer_phone' => '',
                'shipping_address' => '',
                'payment_method' => 'invalid_method',
                'checkout_token' => 'test-token',
            ]);

        $response->assertSessionHasErrors([
            'customer_name',
            'customer_phone',
            'shipping_address',
            'payment_method',
        ]);
    }

    public function test_valid_checkout_creates_order_and_order_items(): void
    {
        $user = User::factory()->create(['name' => 'Khách Hàng Thật']);
        [$product1, $variant1] = $this->createProductWithVariant(2000000, 5);
        [$product2, $variant2] = $this->createProductWithVariant(500000, 10);

        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [
                    $variant1->id => 1,
                    $variant2->id => 2,
                ],
                'checkout_token' => 'valid-checkout-token',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Nguyễn Thị Hoa',
                'customer_phone' => '0912345678',
                'customer_email' => 'hoa@example.com',
                'shipping_address' => 'Số 12 Phố Huế, Hoàn Kiếm, Hà Nội',
                'note' => 'Giao vào buổi chiều',
                'payment_method' => 'cod',
                'checkout_token' => 'valid-checkout-token',
            ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('Nguyễn Thị Hoa', $order->customer_name);
        $this->assertEquals('0912345678', $order->customer_phone);
        $this->assertEquals('hoa@example.com', $order->customer_email);
        $this->assertEquals('Số 12 Phố Huế, Hoàn Kiếm, Hà Nội', $order->shipping_address);
        $this->assertEquals('Giao vào buổi chiều', $order->note);
        $this->assertEquals(3000000, (float) $order->total_price);
        $this->assertEquals(0, (float) $order->shipping_fee);
        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->order_status);

        $this->assertCount(2, $order->items);

        $item1 = $order->items->where('product_variant_id', $variant1->id)->first();
        $this->assertNotNull($item1);
        $this->assertEquals($product1->name, $item1->product_name);
        $this->assertEquals(1, $item1->quantity);
        $this->assertEquals(2000000, (float) $item1->price);

        $item2 = $order->items->where('product_variant_id', $variant2->id)->first();
        $this->assertNotNull($item2);
        $this->assertEquals($product2->name, $item2->product_name);
        $this->assertEquals(2, $item2->quantity);
        $this->assertEquals(500000, (float) $item2->price);

        $response->assertRedirect(route('checkout.success', $order->order_code));
    }

    public function test_checkout_ignores_client_supplied_security_fields(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'token-123',
            ])
            ->post(route('checkout.store'), [
                'user_id' => $anotherUser->id,
                'total_price' => 100,
                'shipping_fee' => 500,
                'payment_status' => 'paid',
                'order_status' => 'completed',
                'customer_name' => 'Lê Văn C',
                'customer_phone' => '0933333333',
                'shipping_address' => 'Đà Nẵng',
                'payment_method' => 'bank_transfer',
                'checkout_token' => 'token-123',
            ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertNotEquals($anotherUser->id, $order->user_id);
        $this->assertEquals(1000000, (float) $order->total_price);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->order_status);
    }

    public function test_cart_is_cleared_after_successful_checkout(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'token-clear',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Lê Văn C',
                'customer_phone' => '0933333333',
                'shipping_address' => 'Đà Nẵng',
                'payment_method' => 'cod',
                'checkout_token' => 'token-clear',
            ]);

        $this->assertEmpty(session('cart'));
    }

    public function test_success_page_is_accessible_by_owner_and_displays_order_details(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-TEST-001',
            'customer_name' => $user->name,
            'customer_phone' => '0987654321',
            'customer_email' => $user->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('checkout.success', $order->order_code));
        $response->assertStatus(200);
        $response->assertSee('ORD-TEST-001');
        $response->assertSee('2.500.000');
        $response->assertSee('Đặt hàng thành công');
    }

    public function test_success_page_is_forbidden_to_non_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'order_code' => 'ORD-OWNER-001',
            'customer_name' => $owner->name,
            'customer_phone' => '0987654321',
            'customer_email' => $owner->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $response = $this->actingAs($stranger)->get(route('checkout.success', $order->order_code));
        $response->assertStatus(403);
    }

    public function test_double_submission_with_same_token_is_blocked(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        // First submission succeeds
        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'idempotent-token',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Nguyễn A',
                'customer_phone' => '0987654321',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'idempotent-token',
            ]);

        $this->assertEquals(1, Order::count());

        // Replaying same submission (token already consumed or invalid)
        $secondResponse = $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Nguyễn A',
                'customer_phone' => '0987654321',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'idempotent-token',
            ]);

        $this->assertEquals(1, Order::count());
        $secondResponse->assertRedirect(route('cart.index'));
    }

    public function test_order_detail_page_accessible_by_owner_and_forbidden_to_stranger(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-DETAIL-001',
            'customer_name' => $user->name,
            'customer_phone' => '0987654321',
            'customer_email' => $user->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Gỗ Sồi',
            'quantity' => 1,
            'price' => 1000000,
        ]);

        // Owner can access order detail
        $responseOwner = $this->actingAs($user)->get(route('orders.show', $order->order_code));
        $responseOwner->assertStatus(200);
        $responseOwner->assertSee('ORD-DETAIL-001');
        $responseOwner->assertSee($product->name);

        // Stranger gets 403 Forbidden
        $responseStranger = $this->actingAs($stranger)->get(route('orders.show', $order->order_code));
        $responseStranger->assertStatus(403);
    }

    public function test_header_cart_badge_is_zero_after_successful_checkout(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 2],
                'checkout_token' => 'token-badge-check',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Nguyễn A',
                'customer_phone' => '0987654321',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-badge-check',
            ]);

        $homeResponse = $this->actingAs($user)->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('>0<', false);
    }

    public function test_checkout_and_success_pages_contain_semantic_theme_tokens(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $checkoutResponse = $this->actingAs($user)
            ->withSession(['cart' => [$variant->id => 1]])
            ->get(route('checkout.index'));

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('bg-page', false);
        $checkoutResponse->assertSee('bg-surface', false);
        $checkoutResponse->assertSee('border-ui-border', false);
        $checkoutResponse->assertSee('text-heading', false);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-THEME-001',
            'customer_name' => $user->name,
            'customer_phone' => '0987654321',
            'customer_email' => $user->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $successResponse = $this->actingAs($user)->get(route('checkout.success', $order->order_code));
        $successResponse->assertStatus(200);
        $successResponse->assertSee('bg-page', false);
        $successResponse->assertSee('bg-surface', false);
        $successResponse->assertSee('border-ui-border', false);
        $successResponse->assertSee('text-heading', false);
    }
}
