<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutConcurrencyHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(int $price = 1000000, int $stock = 1): array
    {
        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Giám Đốc Mộc An ' . uniqid(),
            'slug' => 'ban-giam-doc-' . uniqid(),
            'sku' => 'BGD-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/ban.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Nâu Óc Chó',
            'size' => '180x90cm',
            'material' => 'Gỗ óc chó',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'BGD-OCCHO-' . strtoupper(uniqid()),
        ]);

        return [$product, $variant];
    }

    private function createCustomer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'loyalty_points' => 0,
        ]);
    }

    public function test_submitting_checkout_with_identical_idempotency_token_redirects_to_existing_order_success_page(): void
    {
        $user = $this->createCustomer();
        [$product, $variant] = $this->createProductWithVariant(2000000, 5);

        $idempotencyToken = Str::random(40);

        // First checkout request
        $response1 = $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => $idempotencyToken,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $idempotencyToken,
                'customer_name' => 'Nguyễn Văn A',
                'customer_phone' => '0901234567',
                'customer_email' => 'nguyenvana@example.com',
                'shipping_address' => '123 Đường Lê Lợi, Q1, TP.HCM',
                'payment_method' => 'cod',
            ]);

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $response1->assertRedirect(route('checkout.success', $order->order_code));

        // Second checkout request with the SAME idempotency token (simulating double click or network replay)
        $response2 = $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => $idempotencyToken,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $idempotencyToken,
                'customer_name' => 'Nguyễn Văn A',
                'customer_phone' => '0901234567',
                'customer_email' => 'nguyenvana@example.com',
                'shipping_address' => '123 Đường Lê Lợi, Q1, TP.HCM',
                'payment_method' => 'cod',
            ]);

        // Must NOT create a second order
        $this->assertEquals(1, Order::count());
        // Must redirect to the existing order's success page
        $response2->assertRedirect(route('checkout.success', $order->order_code));
    }

    public function test_concurrent_checkout_for_last_unit_allows_only_one_order_and_prevents_negative_stock(): void
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();
        [$product, $variant] = $this->createProductWithVariant(1500000, 1); // Exactly 1 in stock

        $tokenA = Str::random(40);
        $tokenB = Str::random(40);

        // User A successfully checks out the 1 available unit
        $responseA = $this->actingAs($userA)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => $tokenA,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $tokenA,
                'customer_name' => 'Khách Hàng A',
                'customer_phone' => '0901111111',
                'customer_email' => 'khacha@example.com',
                'shipping_address' => '456 Hai Bà Trưng, Q1, TP.HCM',
                'payment_method' => 'cod',
            ]);

        $this->assertEquals(1, Order::count());
        $variant->refresh();
        $this->assertEquals(0, $variant->stock);

        // User B attempts to checkout the same variant whose stock is now 0
        $responseB = $this->actingAs($userB)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => $tokenB,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $tokenB,
                'customer_name' => 'Khách Hàng B',
                'customer_phone' => '0902222222',
                'customer_email' => 'khachb@example.com',
                'shipping_address' => '789 Nguyễn Huệ, Q1, TP.HCM',
                'payment_method' => 'cod',
            ]);

        // User B must fail with stock error message
        $responseB->assertSessionHas('error');
        $this->assertStringContainsString('không đủ số lượng trong kho', session('error'));

        // Only 1 order created, stock never drops below 0
        $this->assertEquals(1, Order::count());
        $variant->refresh();
        $this->assertEquals(0, $variant->stock);
    }

    public function test_cancelling_order_restores_stock_exactly_once(): void
    {
        $user = $this->createCustomer();
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-TEST-001',
            'customer_name' => $user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Address Test',
            'total_price' => 2000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 2,
            'price' => 1000000,
        ]);

        // Stock was reduced by 2 at checkout
        $variant->decrement('stock', 2);
        $this->assertEquals(3, $variant->fresh()->stock);

        // First cancel request
        $response1 = $this->actingAs($user)
            ->post(route('orders.cancel', $order->order_code));

        $response1->assertRedirect(route('orders.show', $order->order_code));
        $this->assertEquals(Order::STATUS_CANCELED, $order->fresh()->order_status);
        $this->assertEquals(5, $variant->fresh()->stock);

        // Second cancel request (e.g. repeated click or concurrent cancel)
        $response2 = $this->actingAs($user)
            ->post(route('orders.cancel', $order->order_code));

        $response2->assertSessionHas('error');
        // Stock must still be 5, NOT 7
        $this->assertEquals(5, $variant->fresh()->stock);
    }
}
