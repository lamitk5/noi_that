<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(string $paymentMethod = 'cod', string $orderStatus = 'pending', string $paymentStatus = 'pending'): Order
    {
        $category = Category::create([
            'name' => 'Ghế Sofa ' . uniqid(),
            'slug' => 'ghe-sofa-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Băng Đôi ' . uniqid(),
            'slug' => 'sofa-bang-doi-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Xám Khói',
            'price' => 5000000,
            'stock' => 15,
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ]);

        $order = Order::create([
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'customer_name' => 'Khách Hàng Thử Nghiệm',
            'customer_phone' => '0912345678',
            'customer_email' => 'khach@example.com',
            'shipping_address' => 'Hà Nội',
            'total_price' => 5000000,
            'shipping_fee' => 0,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => $variant->color,
            'quantity' => 1,
            'price' => 5000000,
        ]);

        return $order;
    }

    public function test_order_can_transition_through_full_lifecycle(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('cod', 'pending', 'pending');

        // pending -> confirmed
        $response1 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ]);
        $response1->assertSessionHasNoErrors();
        $this->assertEquals('confirmed', $order->fresh()->order_status);

        // confirmed -> packed
        $response2 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'packed',
        ]);
        $response2->assertSessionHasNoErrors();
        $this->assertEquals('packed', $order->fresh()->order_status);

        // packed -> shipping
        $response3 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'shipping',
        ]);
        $response3->assertSessionHasNoErrors();
        $this->assertEquals('shipping', $order->fresh()->order_status);

        // shipping -> completed
        $response4 = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'completed',
        ]);
        $response4->assertSessionHasNoErrors();
        $freshOrder = $order->fresh();
        $this->assertEquals('completed', $freshOrder->order_status);
        $this->assertEquals('paid', $freshOrder->payment_status);
    }

    public function test_invalid_forward_skips_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('cod', 'pending', 'pending');

        // pending -> packed (invalid skip)
        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'packed',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);

        // pending -> shipping (invalid skip)
        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'shipping',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);

        // pending -> completed (invalid skip)
        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'completed',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    public function test_backward_transitions_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('cod', 'shipping', 'pending');

        // shipping -> packed (backward)
        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'packed',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('shipping', $order->fresh()->order_status);

        // shipping -> confirmed (backward)
        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('shipping', $order->fresh()->order_status);
    }

    public function test_unpaid_bank_transfer_cannot_be_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('bank_transfer', 'pending', 'pending');

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    public function test_paid_bank_transfer_can_be_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('bank_transfer', 'pending', 'paid');

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('confirmed', $order->fresh()->order_status);
    }

    public function test_unpaid_online_payment_cannot_be_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('vnpay', 'pending', 'pending');

        $response = $this->actingAs($admin)->post(route('admin.orders.update-status', $order->order_code), [
            'status' => 'confirmed',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    public function test_admin_can_mark_bank_transfer_as_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('bank_transfer', 'pending', 'pending');

        $response = $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order->order_code), [
            'payment_note' => 'Đã nhận chuyển khoản VCB 5.000.000đ',
        ]);

        $response->assertSessionHasNoErrors();
        $freshOrder = $order->fresh();
        $this->assertEquals('paid', $freshOrder->payment_status);
        $this->assertStringContainsString('VCB 5.000.000đ', $freshOrder->note);
    }

    public function test_mark_paid_fails_for_non_bank_transfer_order(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('cod', 'pending', 'pending');

        $response = $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order->order_code));

        $response->assertSessionHasErrors('payment');
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    public function test_mark_paid_fails_for_already_paid_order(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder('bank_transfer', 'pending', 'paid');

        $response = $this->actingAs($admin)->post(route('admin.orders.mark-paid', $order->order_code));

        $response->assertSessionHasErrors('payment');
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }
}