<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithStock(string $status = 'pending', int $initialStock = 10, int $orderQty = 2, string $paymentMethod = 'cod', string $paymentStatus = 'pending'): array
    {
        $category = Category::create([
            'name' => 'Bàn Ăn ' . uniqid(),
            'slug' => 'ban-an-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Ăn Gỗ Sồi ' . uniqid(),
            'slug' => 'ban-an-go-soi-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 3000000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Sồi Tự Nhiên',
            'price' => 3000000,
            'stock' => $initialStock - $orderQty, // Stock already decremented at checkout
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ]);

        $order = Order::create([
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'customer_name' => 'Khách Huỷ Đơn',
            'customer_phone' => '0912345678',
            'customer_email' => 'huy@example.com',
            'shipping_address' => 'Hồ Chí Minh',
            'total_price' => 3000000 * $orderQty,
            'shipping_fee' => 0,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => $status,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => $variant->color,
            'quantity' => $orderQty,
            'price' => 3000000,
        ]);

        return [$order, $variant, $item];
    }

    public function test_cancellation_from_pending_restores_stock(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('pending', 10, 3);

        $this->assertEquals(7, $variant->stock);

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('canceled', $order->fresh()->order_status);
        $this->assertEquals(10, $variant->fresh()->stock); // 7 + 3 restored
    }

    public function test_cancellation_from_confirmed_restores_stock(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('confirmed', 20, 5);

        $this->assertEquals(15, $variant->stock);

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('canceled', $order->fresh()->order_status);
        $this->assertEquals(20, $variant->fresh()->stock);
    }

    public function test_cancellation_from_packed_restores_stock(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('packed', 15, 2);

        $this->assertEquals(13, $variant->stock);

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('canceled', $order->fresh()->order_status);
        $this->assertEquals(15, $variant->fresh()->stock);
    }

    public function test_cancellation_forbidden_from_shipping(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('shipping', 10, 2);

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('shipping', $order->fresh()->order_status);
        $this->assertEquals(8, $variant->fresh()->stock); // Unchanged
    }

    public function test_cancellation_forbidden_from_completed(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('completed', 10, 2, 'cod', 'paid');

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('completed', $order->fresh()->order_status);
        $this->assertEquals(8, $variant->fresh()->stock); // Unchanged
    }

    public function test_cancellation_cannot_restore_stock_more_than_once(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('pending', 10, 3);

        // First cancel succeeds
        $response1 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);
        $response1->assertSessionHasNoErrors();
        $this->assertEquals(10, $variant->fresh()->stock);

        // Second cancel attempt must fail and NOT restore stock again
        $response2 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);
        $response2->assertSessionHasErrors('status');
        $this->assertEquals('canceled', $order->fresh()->order_status);
        $this->assertEquals(10, $variant->fresh()->stock); // Still 10, NOT 13!
    }

    public function test_paid_online_orders_cannot_be_canceled_without_refund(): void
    {
        $admin = User::factory()->admin()->create();

        // Paid VNPAY order
        [$vnpayOrder, $vnpayVar] = $this->createOrderWithStock('confirmed', 10, 2, 'vnpay', 'paid');
        $respVnpay = $this->actingAs($admin)->post(route('admin.orders.update-status', $vnpayOrder->order_code), [
            'status' => 'canceled',
        ]);
        $respVnpay->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $vnpayOrder->fresh()->order_status);
        $this->assertEquals(8, $vnpayVar->fresh()->stock);

        // Paid MoMo order
        [$momoOrder, $momoVar] = $this->createOrderWithStock('confirmed', 10, 2, 'momo', 'paid');
        $respMomo = $this->actingAs($admin)->post(route('admin.orders.update-status', $momoOrder->order_code), [
            'status' => 'canceled',
        ]);
        $respMomo->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $momoOrder->fresh()->order_status);
        $this->assertEquals(8, $momoVar->fresh()->stock);

        // Paid Bank Transfer order
        [$bankOrder, $bankVar] = $this->createOrderWithStock('confirmed', 10, 2, 'bank_transfer', 'paid');
        $respBank = $this->actingAs($admin)->post(route('admin.orders.update-status', $bankOrder->order_code), [
            'status' => 'canceled',
        ]);
        $respBank->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $bankOrder->fresh()->order_status);
        $this->assertEquals(8, $bankVar->fresh()->stock);
    }

    public function test_orders_with_pending_payment_transactions_cannot_be_canceled(): void
    {
        $admin = User::factory()->admin()->create();
        [$order, $variant] = $this->createOrderWithStock('pending', 10, 2, 'vnpay', 'pending');

        PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'vnpay',
            'provider_reference' => 'TXN-' . uniqid(),
            'amount' => $order->total_price,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'canceled',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);
        $this->assertEquals(8, $variant->fresh()->stock); // Not restored
    }
}
