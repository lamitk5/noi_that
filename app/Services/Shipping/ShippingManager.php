<?php

namespace App\Services\Shipping;

class ShippingManager
{
    /**
     * Calculate shipping fee based on total amount and province/city.
     */
    public function calculateFee(float $subtotal, ?string $province = null): float
    {
        // Free shipping for orders over 5,000,000 VND
        if ($subtotal >= 5000000) {
            return 0.0;
        }

        $provinceLower = mb_strtolower(trim((string) $province), 'UTF-8');

        // Flat rates for key hubs vs regional
        if (str_contains($provinceLower, 'hà nội') || str_contains($provinceLower, 'ha noi')) {
            return 50000.0;
        }

        if (str_contains($provinceLower, 'hồ chí minh') || str_contains($provinceLower, 'ho chi minh') || str_contains($provinceLower, 'hcm')) {
            return 80000.0;
        }

        // Other provinces (standard furniture logistics delivery)
        return 150000.0;
    }

    /**
     * Generate mock tracking number and carrier info.
     */
    public function createShipment(string $orderCode, string $provider = 'ghn'): array
    {
        $prefix = strtoupper($provider);
        $trackingCode = $prefix . '-' . strtoupper(substr(md5($orderCode . time()), 0, 10));

        return [
            'provider' => $provider,
            'provider_name' => $provider === 'ghtk' ? 'Giao Hàng Tiết Kiệm (GHTK)' : 'Giao Hàng Nhanh (GHN)',
            'tracking_code' => $trackingCode,
            'estimated_delivery_days' => 2,
            'status' => 'ready_to_pick',
        ];
    }

    /**
     * Get tracking status timeline for a tracking code.
     */
    public function getTrackingTimeline(string $trackingCode): array
    {
        return [
            [
                'time' => now()->subHours(24)->format('d/m/Y H:i'),
                'status' => 'Đã tiếp nhận yêu cầu vận chuyển từ kho Mộc An',
                'location' => 'Kho tổng Mộc An - Hà Nội',
            ],
            [
                'time' => now()->subHours(12)->format('d/m/Y H:i'),
                'status' => 'Đang vận chuyển liên tỉnh',
                'location' => 'Trung tâm phân loại hàng hóa',
            ],
            [
                'time' => now()->subHours(2)->format('d/m/Y H:i'),
                'status' => 'Shipper đang trên đường giao hàng đến địa chỉ',
                'location' => 'Bưu cục phát hàng địa phương',
            ],
        ];
    }
}
