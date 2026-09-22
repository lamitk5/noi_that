<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'customer@example.com',
            'name' => 'Nguyễn Văn A',
        ]);

        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn làm việc Mộc An Oak',
            'slug' => 'ban-lam-viec-moc-an-oak',
            'sku' => 'BAN-OAK-01',
            'base_price' => 2500000,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'BAN-OAK-01-140',
            'price' => 2500000,
            'stock' => 10,
            'material' => 'Gỗ sồi',
            'color' => 'Tự nhiên',
        ]);
    }

    public function test_user_can_manage_addresses(): void
    {
        // 1. Create address
        $response = $this->actingAs($this->user)->post(route('account.addresses.store'), [
            'label' => 'Nhà riêng',
            'recipient_name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'city' => 'TP.HCM',
            'address_line' => '123 Nguyễn Trãi, Quận 1, TP.HCM',
            'is_default' => true,
        ]);

        $response->assertRedirect(route('account.addresses.index'));
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'recipient_name' => 'Nguyễn Văn A',
            'is_default' => 1,
        ]);

        $address = UserAddress::first();

        // 2. Update address
        $updateResponse = $this->actingAs($this->user)->put(route('account.addresses.update', $address), [
            'label' => 'Văn phòng',
            'recipient_name' => 'Nguyễn Văn B',
            'phone' => '0987654321',
            'city' => 'TP.HCM',
            'address_line' => '456 Lê Lợi, Quận 1, TP.HCM',
            'is_default' => false,
        ]);

        $updateResponse->assertRedirect(route('account.addresses.index'));
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'recipient_name' => 'Nguyễn Văn B',
        ]);

        // 3. Set default
        $this->actingAs($this->user)->post(route('account.addresses.set-default', $address));
        $this->assertTrue($address->fresh()->is_default);

        // 4. Delete address
        $deleteResponse = $this->actingAs($this->user)->delete(route('account.addresses.destroy', $address));
        $deleteResponse->assertRedirect(route('account.addresses.index'));
        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_search_suggestions_api_returns_expected_structure(): void
    {
        $response = $this->getJson(route('search.suggestions', ['q' => 'Bàn']));

        $response->assertOk()
            ->assertJsonStructure([
                'categories',
                'products',
                'popular',
            ]);

        $this->assertNotEmpty($response->json('products'));
    }

    public function test_product_quick_view_and_compare_data_endpoints(): void
    {
        // Quick view
        $qvResponse = $this->getJson(route('products.quick-view', $this->product));
        $qvResponse->assertOk()
            ->assertJsonFragment([
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]);

        // Compare data
        $cmpResponse = $this->getJson(route('products.compare.data', ['ids' => $this->product->id]));
        $cmpResponse->assertOk()
            ->assertJsonFragment([
                'id' => $this->product->id,
            ]);
    }

    public function test_cart_quick_add_api(): void
    {
        $response = $this->postJson(route('cart.quick-add'), [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'cart_count' => 2,
            ]);
    }

    public function test_order_actions_buy_again_cancel_and_print(): void
    {
        $order = Order::create([
            'order_code' => 'ORD-TEST-001',
            'user_id' => $this->user->id,
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0912345678',
            'customer_email' => 'customer@example.com',
            'shipping_address' => '123 Nguyễn Trãi, Q1, TP.HCM',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_info' => 'Gỗ sồi - Tự nhiên',
            'price' => 2500000,
            'quantity' => 2,
            'total_price' => 5000000,
        ]);

        // Stock was 10, let's say after order it was 8
        $this->variant->update(['stock' => 8]);

        // 1. Printable invoice
        $printResponse = $this->actingAs($this->user)->get(route('orders.print', $order->order_code));
        $printResponse->assertOk()
            ->assertSee('HÓA ĐƠN BÁN HÀNG')
            ->assertSee($order->order_code);

        // 2. Buy again
        $buyAgainResponse = $this->actingAs($this->user)->post(route('orders.buy-again', $order->order_code));
        $buyAgainResponse->assertRedirect(route('cart.index'));
        $cart = session()->get('cart', []);
        $this->assertArrayHasKey($this->variant->id, $cart);

        // 3. Cancel order
        $cancelResponse = $this->actingAs($this->user)->post(route('orders.cancel', $order->order_code));
        $cancelResponse->assertRedirect(route('orders.show', $order->order_code));
        $this->assertEquals(Order::STATUS_CANCELED, $order->fresh()->order_status);

        // Stock restored from 8 back to 10
        $this->assertEquals(10, $this->variant->fresh()->stock);
    }

    public function test_guest_cart_merges_into_authenticated_user_cart(): void
    {
        $cartService = app(CartService::class);

        // Simulate guest session cart
        session()->put('cart', [
            $this->variant->id => 3,
        ]);

        // Simulate user login merge
        $cartService->mergeGuestCart($this->user);

        // Cart in database
        $this->assertDatabaseHas('user_cart_items', [
            'user_id' => $this->user->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 3,
        ]);
    }
}
