<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserEvent;
use App\Services\Analytics\BehavioralTracker;
use App\Services\Shipping\ShippingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneDTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_manager_calculates_rates_and_creates_shipment(): void
    {
        $shippingManager = new ShippingManager();

        // Over 5,000,000 VND free shipping
        $this->assertEquals(0, $shippingManager->calculateFee(6000000, 'Hà Nội'));

        // Hanoi local flat rate
        $this->assertEquals(50000, $shippingManager->calculateFee(1500000, 'Thành phố Hà Nội'));

        // HCM rate
        $this->assertEquals(80000, $shippingManager->calculateFee(1500000, 'Hồ Chí Minh'));

        // Other province
        $this->assertEquals(150000, $shippingManager->calculateFee(1500000, 'Đà Nẵng'));

        // Create mock shipment
        $shipment = $shippingManager->createShipment('ORD-20260919-TEST', 'ghn');
        $this->assertArrayHasKey('tracking_code', $shipment);
        $this->assertStringStartsWith('GHN-', $shipment['tracking_code']);
    }

    public function test_social_auth_redirect_and_callback_creates_and_logs_in_user(): void
    {
        $redirectResponse = $this->get(route('auth.social.redirect', 'google'));
        $redirectResponse->assertRedirect(route('auth.social.callback', ['provider' => 'google']));

        $callbackResponse = $this->get(route('auth.social.callback', ['provider' => 'google']));
        $callbackResponse->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('google.customer@mocan-demo.vn', $user->email);
        $this->assertEquals('customer', $user->role);
    }

    public function test_behavioral_tracker_records_events_and_computes_abandonment_rate(): void
    {
        $user = User::factory()->create();

        // 4 Add to cart events
        UserEvent::create([
            'session_id' => 'sess-1',
            'event_type' => UserEvent::EVENT_ADD_TO_CART,
        ]);
        UserEvent::create([
            'session_id' => 'sess-2',
            'event_type' => UserEvent::EVENT_ADD_TO_CART,
        ]);
        UserEvent::create([
            'session_id' => 'sess-3',
            'event_type' => UserEvent::EVENT_ADD_TO_CART,
        ]);
        UserEvent::create([
            'session_id' => 'sess-4',
            'event_type' => UserEvent::EVENT_ADD_TO_CART,
        ]);

        // 1 Checkout started event
        UserEvent::create([
            'session_id' => 'sess-1',
            'event_type' => UserEvent::EVENT_CHECKOUT_STARTED,
        ]);

        $tracker = new BehavioralTracker();
        $summary = $tracker->getAnalyticsSummary();

        $this->assertEquals(4, $summary['total_cart_adds']);
        $this->assertEquals(1, $summary['total_checkouts']);
        // Abandonment rate: (4 - 1) / 4 = 75%
        $this->assertEquals(75.0, $summary['cart_abandonment_rate']);
    }
}
