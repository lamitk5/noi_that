<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(float $price = 2000000, int $stock = 10): array
    {
        $category = Category::create([
            'name' => 'Phòng ăn',
            'slug' => 'phong-an-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Ăn Tần Bì',
            'slug' => 'ban-an-tan-bi-' . uniqid(),
            'sku' => 'BATB-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BATB-01-' . strtoupper(uniqid()),
            'color' => 'Gỗ sồi',
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    public function test_user_can_apply_fixed_coupon_to_cart(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(1000000);

        Coupon::create([
            'code' => 'GIAM50K',
            'name' => 'Giảm 50K',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 200000,
            'is_active' => true,
        ]);

        // Add product to cart as authenticated user
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        // Apply coupon
        $response = $this->actingAs($user)->post(route('coupon.apply'), [
            'code' => 'GIAM50K',
        ]);

        $response->assertSessionHas('coupon_success');
        $this->assertEquals('GIAM50K', session('applied_coupon.code'));
        $this->assertEquals(50000, session('applied_coupon.discount'));

        // View cart displays discount
        $cartResponse = $this->actingAs($user)->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('GIAM50K');
        $cartResponse->assertSee('-50.000₫');
    }

    public function test_coupon_rejected_if_subtotal_below_minimum(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(500000);

        Coupon::create([
            'code' => 'VIP100',
            'type' => 'fixed',
            'value' => 100000,
            'min_order_amount' => 1000000, // Requires 1,000,000₫
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1, // 500,000₫
        ]);

        $response = $this->actingAs($user)->post(route('coupon.apply'), [
            'code' => 'VIP100',
        ]);

        $response->assertSessionHas('coupon_error');
        $this->assertNull(session('applied_coupon'));
    }

    public function test_user_can_remove_applied_coupon(): void
    {
        session()->put('applied_coupon', [
            'code' => 'TESTCODE',
            'discount' => 50000,
        ]);

        $response = $this->post(route('coupon.remove'));

        $response->assertSessionHas('coupon_success');
        $this->assertNull(session('applied_coupon'));
    }

    public function test_order_checkout_applies_coupon_and_records_discount(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant(2000000);

        $coupon = Coupon::create([
            'code' => 'SALE10',
            'type' => 'percent',
            'value' => 10, // 10% of 2,000,000 = 200,000₫
            'max_discount_amount' => 500000,
            'min_order_amount' => 500000,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('coupon.apply'), [
            'code' => 'SALE10',
        ]);

        $checkoutResponse = $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout_token');

        $orderResponse = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'Khách Demo',
            'customer_phone' => '0912345678',
            'shipping_address' => '456 Phố Huế, Hà Nội',
            'payment_method' => 'cod',
            'checkout_token' => $token,
        ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('SALE10', $order->coupon_code);
        $this->assertEquals(200000, (float) $order->discount_amount);
        $this->assertEquals(1800000, (float) $order->total_price);

        // Coupon used_count incremented
        $this->assertEquals(1, $coupon->fresh()->used_count);

        // Session coupon cleared after checkout
        $this->assertNull(session('applied_coupon'));
    }

    public function test_admin_can_manage_coupons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // View coupon index
        $indexRes = $this->actingAs($admin)->get(route('admin.coupons.index'));
        $indexRes->assertStatus(200);

        // Create coupon
        $createRes = $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'SUMMER2026',
            'name' => 'Giảm mùa hè',
            'type' => 'percent',
            'value' => 15,
            'min_order_amount' => 1000000,
            'is_active' => '1',
        ]);

        $createRes->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => 'SUMMER2026', 'value' => 15]);

        $coupon = Coupon::where('code', 'SUMMER2026')->first();

        // Update coupon
        $updateRes = $this->actingAs($admin)->put(route('admin.coupons.update', $coupon), [
            'code' => 'SUMMER2026',
            'name' => 'Giảm mùa hè cập nhật',
            'type' => 'fixed',
            'value' => 150000,
        ]);

        $updateRes->assertRedirect(route('admin.coupons.index'));
        $this->assertEquals(150000, (float) $coupon->fresh()->value);
        $this->assertEquals('fixed', $coupon->fresh()->type);

        // Delete coupon
        $deleteRes = $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon));
        $deleteRes->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}
