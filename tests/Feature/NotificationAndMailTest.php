<?php

namespace Tests\Feature;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationAndMailTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@mocan.vn',
        ]);

        $category = Category::create([
            'name' => 'Kệ Sách',
            'slug' => 'ke-sach',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kệ Sách Mộc An 5 Tầng',
            'slug' => 'ke-sach-moc-an-5-tang',
            'sku' => 'KS-05T',
            'base_price' => 1500000,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KS-05T-V1',
            'price' => 1500000,
            'stock' => 10,
        ]);
    }

    public function test_order_placement_dispatches_notification_and_mail(): void
    {
        Notification::fake();
        Mail::fake();

        $cart = [$this->variant->id => 1];
        $token = 'test-notif-token';

        $response = $this->actingAs($this->customer)
            ->withSession([
                'cart' => $cart,
                'checkout_token' => $token,
            ])
            ->post(route('checkout.store'), [
                'checkout_token' => $token,
                'customer_name' => 'Khách Hàng Test',
                'customer_phone' => '0912345678',
                'customer_email' => 'customer@mocan.vn',
                'shipping_address' => 'Hà Nội',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();

        $order = Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);

        Notification::assertSentTo($this->customer, OrderPlacedNotification::class);
        Mail::assertSent(OrderPlacedMail::class, function ($mail) use ($order) {
            return $mail->hasTo('customer@mocan.vn') && $mail->order->id === $order->id;
        });
    }

    public function test_order_status_transition_dispatches_notification_and_mail(): void
    {
        Notification::fake();
        Mail::fake();

        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-NOTIF-TEST',
            'customer_name' => $this->customer->name,
            'customer_phone' => '0901234567',
            'customer_email' => 'customer@mocan.vn',
            'shipping_address' => 'Hà Nội',
            'total_price' => 1500000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        /** @var OrderWorkflowService $service */
        $service = app(OrderWorkflowService::class);
        $service->transition($order, Order::STATUS_CONFIRMED);

        Notification::assertSentTo($this->customer, OrderStatusUpdatedNotification::class);
        Mail::assertSent(OrderStatusChangedMail::class, function ($mail) use ($order) {
            return $mail->hasTo('customer@mocan.vn') && $mail->order->id === $order->id;
        });
    }

    public function test_customer_can_view_and_mark_notifications_as_read(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-READ-TEST',
            'customer_name' => $this->customer->name,
            'customer_phone' => '0901234567',
            'customer_email' => 'customer@mocan.vn',
            'shipping_address' => 'Hà Nội',
            'total_price' => 1500000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        // Send real notification without faking
        $this->customer->notify(new OrderPlacedNotification($order));

        $this->assertEquals(1, $this->customer->unreadNotifications()->count());

        $notif = $this->customer->unreadNotifications()->first();

        // View notifications page
        $response = $this->actingAs($this->customer)
            ->get(route('account.notifications'));

        $response->assertOk();
        $response->assertSee('Thông báo của bạn');
        $response->assertSee('Đặt hàng thành công');

        // Mark single as read
        $markResponse = $this->actingAs($this->customer)
            ->patch(route('account.notifications.read', $notif->id));

        $markResponse->assertRedirect();
        $this->assertEquals(0, $this->customer->unreadNotifications()->count());
    }
}
