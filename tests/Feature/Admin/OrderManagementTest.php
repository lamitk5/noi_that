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

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(array $attributes = []): Order
    {
        $category = Category::create([
            'name' => 'Danh mục test ' . uniqid(),
            'slug' => 'danh-muc-test-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn làm việc Mộc An ' . uniqid(),
            'slug' => 'ban-lam-viec-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 2500000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Gỗ Tự Nhiên',
            'price' => 2500000,
            'stock' => 10,
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ]);

        $order = Order::create(array_merge([
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'customer_email' => 'vana@example.com',
            'shipping_address' => '123 Đường Cầu Giấy, Hà Nội',
            'total_price' => 2500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => $variant->color,
            'quantity' => 1,
            'price' => 2500000,
        ]);

        return $order;
    }

    public function test_guest_cannot_access_admin_orders_index(): void
    {
        $response = $this->get(route('admin.orders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_orders_index(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.orders.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_orders_show(): void
    {
        $order = $this->createOrder();

        $response = $this->get(route('admin.orders.show', $order->order_code));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_orders_show(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder();

        $response = $this->actingAs($customer)->get(route('admin.orders.show', $order->order_code));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_orders_index_with_counts(): void
    {
        $admin = User::factory()->admin()->create();

        $order1 = $this->createOrder(['order_status' => 'pending']);
        $order2 = $this->createOrder(['order_status' => 'confirmed']);
        $order3 = $this->createOrder(['order_status' => 'packed']);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Quản lý Đơn hàng');
        $response->assertSee($order1->order_code);
        $response->assertSee($order2->order_code);
        $response->assertSee($order3->order_code);
        $response->assertSee('Đang xử lý');
        $response->assertSee('Đã xác nhận');
        $response->assertSee('Đã đóng gói');
    }

    public function test_admin_can_search_orders_by_code_and_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $order1 = $this->createOrder([
            'order_code' => 'ORD-TARGET-001',
            'customer_name' => 'Trần Văn Đặc Biệt',
            'customer_phone' => '0988888888',
        ]);

        $order2 = $this->createOrder([
            'order_code' => 'ORD-OTHER-002',
            'customer_name' => 'Lê Thị Khác Biệt',
            'customer_phone' => '0911111111',
        ]);

        // Search by code
        $responseCode = $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'TARGET']));
        $responseCode->assertStatus(200);
        $responseCode->assertSee('ORD-TARGET-001');
        $responseCode->assertDontSee('ORD-OTHER-002');

        // Search by name
        $responseName = $this->actingAs($admin)->get(route('admin.orders.index', ['q' => 'Trần Văn']));
        $responseName->assertStatus(200);
        $responseName->assertSee('ORD-TARGET-001');
        $responseName->assertDontSee('ORD-OTHER-002');

        // Search by phone
        $responsePhone = $this->actingAs($admin)->get(route('admin.orders.index', ['q' => '0988888888']));
        $responsePhone->assertStatus(200);
        $responsePhone->assertSee('ORD-TARGET-001');
        $responsePhone->assertDontSee('ORD-OTHER-002');
    }

    public function test_admin_can_filter_orders_by_status_and_payment_status(): void
    {
        $admin = User::factory()->admin()->create();

        $orderPending = $this->createOrder(['order_status' => 'pending', 'payment_status' => 'pending']);
        $orderPackedPaid = $this->createOrder(['order_status' => 'packed', 'payment_status' => 'paid']);

        // Filter by order_status
        $responseStatus = $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'packed']));
        $responseStatus->assertStatus(200);
        $responseStatus->assertSee($orderPackedPaid->order_code);
        $responseStatus->assertDontSee($orderPending->order_code);

        // Filter by payment_status
        $responsePayment = $this->actingAs($admin)->get(route('admin.orders.index', ['payment_status' => 'paid']));
        $responsePayment->assertStatus(200);
        $responsePayment->assertSee($orderPackedPaid->order_code);
        $responsePayment->assertDontSee($orderPending->order_code);
    }

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->createOrder([
            'order_code' => 'ORD-DETAIL-999',
            'customer_name' => 'Phạm Thanh Mai',
            'customer_phone' => '0933333333',
            'shipping_address' => 'Số 88 Phố Huế, Hai Bà Trưng, Hà Nội',
            'note' => 'Giao hàng giờ hành chính',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order->order_code));

        $response->assertStatus(200);
        $response->assertSee('ORD-DETAIL-999');
        $response->assertSee('Phạm Thanh Mai');
        $response->assertSee('0933333333');
        $response->assertSee('Số 88 Phố Huế, Hai Bà Trưng, Hà Nội');
        $response->assertSee('Giao hàng giờ hành chính');
        $response->assertSee('Bàn làm việc Mộc An');
        $response->assertSee('Gỗ Tự Nhiên');
        $response->assertSee('2.500.000₫');
    }

    public function test_admin_orders_pagination_preserves_query_string(): void
    {
        $admin = User::factory()->admin()->create();

        // Create 20 pending orders
        for ($i = 1; $i <= 20; $i++) {
            $this->createOrder([
                'order_code' => sprintf('ORD-PAGE-%02d', $i),
                'order_status' => 'pending',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.orders.index', ['status' => 'pending', 'page' => 2]));

        $response->assertStatus(200);
        $response->assertSee('page=1');
        $response->assertSee('status=pending');
    }
}
