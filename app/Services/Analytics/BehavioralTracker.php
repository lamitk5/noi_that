<?php

namespace App\Services\Analytics;

use App\Models\UserEvent;
use Illuminate\Http\Request;

class BehavioralTracker
{
    /**
     * Record an event from current request.
     */
    public function track(Request $request, string $eventType, ?string $entityType = null, ?int $entityId = null, array $payload = []): UserEvent
    {
        return UserEvent::create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId() ?: 'guest-session',
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }

    /**
     * Get summary metrics for Admin Reports.
     */
    public function getAnalyticsSummary(): array
    {
        $totalViews = UserEvent::where('event_type', UserEvent::EVENT_VIEW_PRODUCT)->count();
        $totalCartAdds = UserEvent::where('event_type', UserEvent::EVENT_ADD_TO_CART)->count();
        $totalCheckouts = UserEvent::where('event_type', UserEvent::EVENT_CHECKOUT_STARTED)->count();

        // Cart abandonment rate calculation
        $abandonmentRate = $totalCartAdds > 0
            ? round((($totalCartAdds - $totalCheckouts) / $totalCartAdds) * 100, 1)
            : 0;

        if ($abandonmentRate < 0) {
            $abandonmentRate = 0;
        }

        return [
            'total_views' => $totalViews,
            'total_cart_adds' => $totalCartAdds,
            'total_checkouts' => $totalCheckouts,
            'cart_abandonment_rate' => $abandonmentRate,
        ];
    }
}
