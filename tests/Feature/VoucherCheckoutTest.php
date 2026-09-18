<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn làm việc Mộc An Pro',
            'slug' => 'ban-lam-viec-moc-an-pro',
            'sku' => 'BLV-PRO-01',
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BLV-PRO-V1',
            'price' => 2000000,
            'stock' => 15,
            'color' => 'Gỗ sồi tự nhiên',
            'size' => '120x60cm',
        ]);
    }

    public function test_admin_can_view_vouchers_index(): void
    {
        Voucher::create([
            'code' => 'MOCAN10',
            'name' => 'Giảm 10%',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.vouchers.index'));

        $response->assertOk();
        $response->assertSee('MOCAN10');
    }

    public function test_admin_can_create_voucher(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.vouchers.store'), [
                'code' => 'SUMMER50K',
                'name' => 'Giảm 50.000đ chào hè',
                'type' => 'fixed',
                'value' => 50000,
                'min_order_amount' => 500000,
                'usage_limit' => 100,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.vouchers.index'));
        $this->assertDatabaseHas('vouchers', [
            'code' => 'SUMMER50K',
            'value' => 50000,
        ]);
    }

    public function test_customer_can_apply_percentage_voucher_at_checkout(): void
    {
        $voucher = Voucher::create([
            'code' => 'DISCOUNT10',
            'name' => 'Giảm 10%',
            'type' => 'percent',
            'value' => 10,
            'max_discount' => 500000,
            'min_order_amount' => 1000000,
            'is_active' => true,
        ]);

        $cart = [$this->variant->id => 1]; // 2,000,000đ

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => $cart])
            ->post(route('checkout.apply-voucher'), [
                'code' => 'DISCOUNT10',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('DISCOUNT10', session('applied_voucher_code'));

        // Check index page reflects discount
        $indexResponse = $this->actingAs($this->customer)
            ->withSession(['cart' => $cart, 'applied_voucher_code' => 'DISCOUNT10'])
            ->get(route('checkout.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('DISCOUNT10');
        // 10% of 2,000,000 = 200,000
        $indexResponse->assertSee('200.000');
    }

    public function test_cannot_apply_expired_voucher(): void
    {
        Voucher::create([
            'code' => 'EXPIRED',
            'name' => 'Mã hết hạn',
            'type' => 'fixed',
            'value' => 50000,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $cart = [$this->variant->id => 1];

        $response = $this->actingAs($this->customer)
            ->withSession(['cart' => $cart])
            ->post(route('checkout.apply-voucher'), [
                'code' => 'EXPIRED',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('applied_voucher_code'));
    }

    public function test_order_creation_records_voucher_and_usage(): void
    {
        $voucher = Voucher::create([
            'code' => 'SALE100K',
            'name' => 'Giảm 100k',
            'type' => 'fixed',
            'value' => 100000,
            'min_order_amount' => 500000,
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $cart = [$this->variant->id => 1]; // 2,000,000đ
        $token = 'test-token-123';

        $response = $this->actingAs($this->customer)
            ->withSession([
                'cart' => $cart,
                'checkout_token' => $token,
                'applied_voucher_code' => 'SALE100K',
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $token,
                'customer_name' => 'Nguyễn Khách',
                'customer_phone' => '0987654321',
                'shipping_address' => '123 Cầu Giấy, Hà Nội',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();

        $order = Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals($voucher->id, $order->voucher_id);
        $this->assertEquals(100000, $order->discount_amount);
        $this->assertEquals(1900000, $order->total_price); // 2,000,000 - 100,000

        // Voucher used_count incremented
        $this->assertEquals(1, $voucher->fresh()->used_count);

        // Voucher usage record created
        $this->assertDatabaseHas('voucher_usages', [
            'voucher_id' => $voucher->id,
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'discount_amount' => 100000,
        ]);
    }
}
