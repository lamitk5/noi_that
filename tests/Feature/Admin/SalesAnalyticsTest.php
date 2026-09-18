<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get(route('admin.reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_reports(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('admin.reports.index'));
        $response->assertStatus(403);
    }

    public function test_admin_dashboard_shows_authoritative_revenue(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        // 1. Completed & Paid -> should count in revenue
        Order::create([
            'order_code' => 'ORD-REV-1',
            'user_id' => $customer->id,
            'customer_name' => 'Khách A',
            'customer_phone' => '0901111111',
            'shipping_address' => 'Hà Nội',
            'total_price' => 5000000,
            'payment_method' => 'vnpay',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        // 2. Pending & Unpaid -> should NOT count in revenue
        Order::create([
            'order_code' => 'ORD-REV-2',
            'user_id' => $customer->id,
            'customer_name' => 'Khách B',
            'customer_phone' => '0902222222',
            'shipping_address' => 'Đà Nẵng',
            'total_price' => 3000000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
        ]);

        // 3. Canceled -> should NOT count in revenue
        Order::create([
            'order_code' => 'ORD-REV-3',
            'user_id' => $customer->id,
            'customer_name' => 'Khách C',
            'customer_phone' => '0903333333',
            'shipping_address' => 'TP.HCM',
            'total_price' => 2000000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_CANCELED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('5.000.000đ'); // Only the completed+paid order
        $response->assertDontSee('10.000.000đ');
    }

    public function test_sales_report_aggregates_revenue_and_bestsellers(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $category = Category::create(['name' => 'Phòng Khách', 'slug' => 'phong-khach', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Nỉ Phong Cách Bắc Âu',
            'slug' => 'sofa-ni-phong-cach-bac-au',
            'sku' => 'SF-BAC-AU',
            'base_price' => 6000000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SF-BAC-AU-GRAY',
            'color' => 'Xám Ghi',
            'price' => 6000000,
            'stock' => 10,
        ]);

        $order = Order::create([
            'order_code' => 'ORD-REPORT-1',
            'user_id' => $customer->id,
            'customer_name' => 'Tran Van Test',
            'customer_phone' => '0909999999',
            'shipping_address' => 'Hải Phòng',
            'total_price' => 12000000,
            'payment_method' => 'vnpay',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Sofa Nỉ Phong Cách Bắc Âu',
            'variant_info' => 'Xám Ghi',
            'quantity' => 2,
            'price' => 6000000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', ['preset' => '30days']));

        $response->assertStatus(200);
        $response->assertSee('Báo cáo');
        $response->assertSee('Phân tích Kinh doanh');
        $response->assertSee('12.000.000đ');
        $response->assertSee('Sofa Nỉ Phong Cách Bắc Âu');
        $response->assertSee('vnpay');
    }

    public function test_sales_report_custom_date_filtering(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        // Order 1: within target range (10 days ago)
        $orderIn = Order::create([
            'order_code' => 'ORD-DATE-IN',
            'user_id' => $customer->id,
            'customer_name' => 'Khách Trong Khoảng',
            'customer_phone' => '0901111111',
            'shipping_address' => 'HN',
            'total_price' => 4000000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);
        $orderIn->created_at = Carbon::now()->subDays(10);
        $orderIn->save();

        // Order 2: outside target range (50 days ago)
        $orderOut = Order::create([
            'order_code' => 'ORD-DATE-OUT',
            'user_id' => $customer->id,
            'customer_name' => 'Khách Ngoài Khoảng',
            'customer_phone' => '0902222222',
            'shipping_address' => 'SG',
            'total_price' => 9000000,
            'payment_method' => 'cod',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);
        $orderOut->created_at = Carbon::now()->subDays(50);
        $orderOut->save();

        $fromDate = Carbon::now()->subDays(15)->format('Y-m-d');
        $toDate = Carbon::now()->subDays(5)->format('Y-m-d');

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('4.000.000đ');
        $response->assertDontSee('9.000.000đ');
    }
}
