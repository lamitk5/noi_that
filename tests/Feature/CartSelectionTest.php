<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cart_page_renders_with_checkboxes_and_all_selected_by_default(): void
    {
        $products = Product::with('variants')->where('is_active', true)->take(2)->get();
        $p1 = $products[0];
        $v1 = $p1->variants->first();

        $p2 = $products[1];
        $v2 = $p2->variants->first();

        // Add 2 items
        $this->post('/cart/add/' . $p1->id, [
            'product_variant_id' => $v1->id,
            'quantity' => 1,
        ]);

        $this->post('/cart/add/' . $p2->id, [
            'product_variant_id' => $v2->id,
            'quantity' => 1,
        ]);

        $response = $this->get('/cart');
        $response->assertStatus(200);
        $response->assertSee('select-all-cart-items');
        $response->assertSee('cart-item-checkbox');
        $response->assertSee($p1->name);
        $response->assertSee($p2->name);
    }

    public function test_user_can_select_specific_items_via_select_route(): void
    {
        $cartService = app(CartService::class);
        $products = Product::with('variants')->where('is_active', true)->take(2)->get();

        $p1 = $products[0];
        $v1 = $p1->variants->first();
        $p2 = $products[1];
        $v2 = $p2->variants->first();

        $key1 = $cartService->lineKey($p1->id, $v1->id);
        $key2 = $cartService->lineKey($p2->id, $v2->id);

        $cartService->add($p1, 1, $v1);
        $cartService->add($p2, 1, $v2);

        // Select only item 1
        $response = $this->postJson('/cart/select', [
            'selected_keys' => [$key1],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'selected_count' => 1,
            'subtotal' => (float) $v1->price,
        ]);
    }

    public function test_checkout_only_charges_and_clears_selected_items(): void
    {
        $cartService = app(CartService::class);
        $products = Product::with('variants')->where('is_active', true)->where('stock_quantity', '>=', 5)->take(2)->get();

        $p1 = $products[0];
        $v1 = $p1->variants->first();
        $initialStock1 = $v1->stock;

        $p2 = $products[1];
        $v2 = $p2->variants->first();
        $initialStock2 = $v2->stock;

        $key1 = $cartService->lineKey($p1->id, $v1->id);
        $key2 = $cartService->lineKey($p2->id, $v2->id);

        // Add both to cart
        $cartService->add($p1, 2, $v1);
        $cartService->add($p2, 3, $v2);

        // Select ONLY item 1 for checkout
        $this->post('/cart/checkout', [
            'selected_items' => [$key1],
        ])->assertRedirect(route('checkout.index'));

        // Checkout view should only show item 1
        $checkoutView = $this->get('/checkout');
        $checkoutView->assertStatus(200);
        $checkoutView->assertSee($p1->name);
        $checkoutView->assertDontSee($p2->name);

        // Process checkout
        $checkoutResponse = $this->post('/checkout', [
            'customer_name' => 'Trần Văn Tuyển',
            'customer_email' => 'tuyen@example.com',
            'customer_phone' => '0912345678',
            'shipping_address' => '123 Nguyễn Huệ, Quận 1, TP. HCM',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('customer_phone', '0912345678')->first();
        $this->assertNotNull($order);
        $checkoutResponse->assertRedirect(route('checkout.success', ['order_number' => $order->order_code]));

        // Order should contain ONLY item 1
        $this->assertCount(1, $order->items);
        $this->assertEquals($v1->id, $order->items->first()->product_variant_id);
        $this->assertEquals(2, $order->items->first()->quantity);

        // Stock 1 decremented by 2, stock 2 unchanged
        $v1->refresh();
        $v2->refresh();
        $this->assertEquals($initialStock1 - 2, $v1->stock);
        $this->assertEquals($initialStock2, $v2->stock);

        // Cart should still retain item 2!
        $remainingCart = session('furniture_cart');
        $this->assertArrayNotHasKey($key1, $remainingCart);
        $this->assertArrayHasKey($key2, $remainingCart);
        $this->assertEquals(3, $remainingCart[$key2]['quantity']);
    }

    public function test_checkout_fails_if_no_items_selected(): void
    {
        $cartService = app(CartService::class);
        $product = Product::with('variants')->first();
        $variant = $product->variants->first();

        $cartService->add($product, 1, $variant);

        // Try checkout with empty selection
        $response = $this->post('/cart/checkout', [
            'selected_items' => [],
        ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
    }
}
