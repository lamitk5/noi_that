<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(int $price = 1000000, int $stock = 10, bool $isActive = true): array
    {
        $category = Category::create([
            'name' => 'Phòng khách ' . uniqid(),
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Sofa ' . uniqid(),
            'slug' => 'ghe-sofa-' . uniqid(),
            'sku' => 'SOFA-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/sofa.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Xám',
            'size' => 'Vừa',
            'material' => 'Nỉ cao cấp',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'SOFA-XAM-' . strtoupper(uniqid()),
        ]);

        return [$product, $variant];
    }

    public function test_checkout_decrements_variant_stock_correctly(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 10);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 3],
                'checkout_token' => 'token-dec',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-dec',
            ]);

        $this->assertEquals(7, $variant->fresh()->stock);
    }

    public function test_multiple_variants_decrement_independently(): void
    {
        $user = User::factory()->create();
        [$p1, $v1] = $this->createProductWithVariant(1000000, 10);
        [$p2, $v2] = $this->createProductWithVariant(2000000, 5);

        $this->actingAs($user)
            ->withSession([
                'cart' => [
                    $v1->id => 4,
                    $v2->id => 2,
                ],
                'checkout_token' => 'token-multi',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'bank_transfer',
                'checkout_token' => 'token-multi',
            ]);

        $this->assertEquals(6, $v1->fresh()->stock);
        $this->assertEquals(3, $v2->fresh()->stock);
    }

    public function test_exact_remaining_stock_can_be_purchased_reducing_stock_to_zero(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 3);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 3],
                'checkout_token' => 'token-exact',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-exact',
            ]);

        $this->assertEquals(0, $variant->fresh()->stock);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }

    public function test_insufficient_stock_blocks_order_and_rolls_back_everything(): void
    {
        $user = User::factory()->create();
        [$productA, $variantA] = $this->createProductWithVariant(1000000, 10);
        [$productB, $variantB] = $this->createProductWithVariant(2000000, 2);

        // Cart wants 1 of A (available: 10), and 5 of B (available: only 2)
        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [
                    $variantA->id => 1,
                    $variantB->id => 5,
                ],
                'checkout_token' => 'token-insufficient',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-insufficient',
            ]);

        // Transaction must ROLL BACK:
        // 1. No order created
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderItem::count());

        // 2. Neither stock decremented
        $this->assertEquals(10, $variantA->fresh()->stock);
        $this->assertEquals(2, $variantB->fresh()->stock);

        // 3. Cart preserved in session
        $response->assertSessionHas('error');
        $this->assertNotEmpty(session('cart'));
    }

    public function test_product_deactivated_before_checkout_blocks_order(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 10, true);

        // Put in cart
        session(['cart' => [$variant->id => 1]]);

        // Admin deactivates product before checkout
        $product->update(['is_active' => false]);

        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 1],
                'checkout_token' => 'token-deactivated',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-deactivated',
            ]);

        $this->assertEquals(0, Order::count());
        $this->assertEquals(10, $variant->fresh()->stock);
        $response->assertSessionHas('error');
    }

    public function test_order_item_records_authoritative_database_price_at_checkout(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000, 10);

        // Variant originally 1,000,000 in DB
        // Change price in DB to 1,200,000 before checkout
        $variant->update(['price' => 1200000]);

        $this->actingAs($user)
            ->withSession([
                'cart' => [$variant->id => 2],
                'checkout_token' => 'token-price',
            ])
            ->post(route('checkout.store'), [
                'customer_name' => 'Người mua',
                'customer_phone' => '0912345678',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
                'checkout_token' => 'token-price',
            ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        // Total should be 2 * 1,200,000 = 2,400,000
        $this->assertEquals(2400000, (float) $order->total_price);
        $item = $order->items->first();
        $this->assertEquals(1200000, (float) $item->price);
    }
}
