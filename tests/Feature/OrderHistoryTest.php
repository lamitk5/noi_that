<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_order_history(): void
    {
        $response = $this->get(route('orders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_order_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertStatus(200);
    }

    public function test_empty_state_is_displayed_when_user_has_no_orders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Bạn chưa có đơn hàng nào.');
        $response->assertSee('Tiếp tục mua sắm');
    }

    public function test_user_can_see_their_own_orders(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-10001',
            'customer_name' => $user->name,
            'customer_phone' => '0987654321',
            'customer_email' => $user->email,
            'shipping_address' => '123 Đường Mộc An, Hà Nội',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
        ]);

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('ORD-10001');
        $response->assertSee('2.500.000');
        $response->assertSee('Đã xác nhận');
    }

    public function test_user_cannot_see_orders_belonging_to_other_users(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        Order::create([
            'user_id' => $userA->id,
            'order_code' => 'ORD-A-999',
            'customer_name' => 'User A',
            'customer_phone' => '0111222333',
            'customer_email' => $userA->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => 1000000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        Order::create([
            'user_id' => $userB->id,
            'order_code' => 'ORD-B-888',
            'customer_name' => 'User B',
            'customer_phone' => '0999888777',
            'customer_email' => $userB->email,
            'shipping_address' => 'TP.HCM',
            'total_price' => 4500000,
            'shipping_fee' => 0,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        // When User A accesses order history, they must NOT see User B's order
        $responseA = $this->actingAs($userA)->get(route('orders.index'));
        $responseA->assertStatus(200);
        $responseA->assertSee('ORD-A-999');
        $responseA->assertDontSee('ORD-B-888');

        // When User B accesses order history, they must NOT see User A's order
        $responseB = $this->actingAs($userB)->get(route('orders.index'));
        $responseB->assertStatus(200);
        $responseB->assertSee('ORD-B-888');
        $responseB->assertDontSee('ORD-A-999');
    }
}