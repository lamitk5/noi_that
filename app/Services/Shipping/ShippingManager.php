<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ShippingManager
{
    public function __construct(
        protected ?GHNService $ghn = null,
        protected ?GHNOrderService $ghnOrders = null
    ) {
        $this->ghn = $ghn ?? app(GHNService::class);
        $this->ghnOrders = $ghnOrders ?? app(GHNOrderService::class);
    }

    public function getGhnService(): GHNService
    {
        return $this->ghn;
    }

    public function getGhnOrderService(): GHNOrderService
    {
        return $this->ghnOrders;
    }

    /**
     * Calculate shipping fee based on GHN destination or subtotal/province fallback.
     */
    public function calculateFee(float $subtotal, int|string|null $toDistrictOrProvince = null, ?string $toWardCode = null, int $weight = 200): float
    {
        if (is_numeric($toDistrictOrProvince) && (int) $toDistrictOrProvince > 0 && $toWardCode && $this->ghn->isConfigured()) {
            try {
                $feeData = $this->ghn->calculateFee((int) $toDistrictOrProvince, $toWardCode, $weight);
                return (float) ($feeData['total'] ?? $feeData['service_fee'] ?? $feeData['fee'] ?? 0);
            } catch (\Throwable $e) {
                Log::warning('GHN calculateFee failed, using fallback rate: ' . $e->getMessage());
            }
        }

        // Subtotal threshold: >= 5,000,000 VND is free shipping
        if ($subtotal >= 5000000) {
            return 0.0;
        }

        // Legacy province-based fallback
        if (is_string($toDistrictOrProvince) && trim($toDistrictOrProvince) !== '') {
            $province = mb_strtolower(trim($toDistrictOrProvince));
            if (str_contains($province, 'hà nội') || str_contains($province, 'ha noi')) {
                return 50000.0;
            }
            if (str_contains($province, 'hồ chí minh') || str_contains($province, 'ho chi minh') || str_contains($province, 'hcm')) {
                return 80000.0;
            }
            return 150000.0;
        }

        return 80000.0;
    }

    /**
     * Idempotent method to ensure GHN shipment is created exactly once for an order.
     */
    public function ensureShipmentCreated(Order $order): bool
    {
        if ($order->ghn_order_code) {
            return false;
        }

        if (! $order->to_district_id || ! $order->to_ward_code || ! $this->ghn->isConfigured()) {
            return false;
        }

        try {
            return $this->ghnOrders->createAndStoreWaybill($order);
        } catch (\Throwable $e) {
            Log::warning("GHN ensureShipmentCreated failed for order #{$order->order_code}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel shipment on GHN if order is cancelled and shipment is cancellable.
     */
    public function cancelShipment(Order $order): bool
    {
        if (! $order->ghn_order_code) {
            return false;
        }

        $this->ghnOrders->cancelWaybill($order);
        $order->update(['shipping_status' => 'cancel']);

        return true;
    }

    /**
     * Dispatch shipment (backward compatibility for Admin/OrderController).
     */
    public function createShipment(string $orderCode, string $provider = 'ghn'): array
    {
        $order = Order::where('order_code', $orderCode)->first();
        if ($order) {
            $this->ensureShipmentCreated($order);
            $order = $order->fresh();

            if ($order->ghn_order_code) {
                return [
                    'provider' => 'ghn',
                    'provider_name' => 'Giao Hàng Nhanh (GHN DEV)',
                    'tracking_code' => $order->ghn_order_code,
                    'estimated_delivery_days' => 2,
                    'status' => $order->shipping_status ?? 'created',
                ];
            }
        }

        // LEGACY_DISABLED: Old GHTK mock behavior is no longer used for customer flows.
        return [
            'provider' => 'ghn',
            'provider_name' => 'Giao Hàng Nhanh (GHN DEV)',
            'tracking_code' => $order?->tracking_code ?: ('GHN-' . strtoupper(substr(md5($orderCode . time()), 0, 10))),
            'estimated_delivery_days' => 2,
            'status' => 'ready_to_pick',
        ];
    }

    /**
     * Get real tracking timeline for order from GHN DEV.
     */
    public function getTrackingTimeline(string $trackingCode, ?Order $order = null): array
    {
        if (! $order) {
            $order = Order::where('ghn_order_code', $trackingCode)
                ->orWhere('tracking_code', $trackingCode)
                ->first();
        }

        if ($order && $order->ghn_order_code) {
            $tracking = $this->ghnOrders->trackingForOrder($order);
            $detail = $tracking['detail'] ?? [];

            $timeline = [];
            if (! empty($detail['log']) && is_array($detail['log'])) {
                foreach ($detail['log'] as $entry) {
                    $timeline[] = [
                        'time' => isset($entry['updated_date']) ? date('d/m/Y H:i', strtotime($entry['updated_date'])) : now()->format('d/m/Y H:i'),
                        'status' => $entry['status'] ?? $tracking['normalized_status'],
                        'location' => 'Kho vận GHN',
                    ];
                }
            }

            if (empty($timeline)) {
                $statusLabels = [
                    'order_created' => 'Đã tạo vận đơn trên hệ thống GHN DEV',
                    'ready_to_pick' => 'Đang chờ bưu tá GHN lấy hàng tại kho Mộc An',
                    'picking' => 'Bưu tá GHN đang lấy hàng',
                    'transporting' => 'Đang luân chuyển qua bưu cục trung chuyển GHN',
                    'delivering' => 'Bưu tá GHN đang phát hàng đến địa chỉ người nhận',
                    'delivered' => 'Giao hàng thành công',
                    'canceled' => 'Đơn vận chuyển đã hủy trên hệ thống GHN',
                ];

                $timeline[] = [
                    'time' => $order->updated_at ? $order->updated_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                    'status' => $statusLabels[$tracking['normalized_status']] ?? ('Trạng thái GHN: ' . $tracking['normalized_status']),
                    'location' => 'Hệ thống Giao Hàng Nhanh (GHN DEV)',
                ];
            }

            return $timeline;
        }

        return [
            [
                'time' => now()->format('d/m/Y H:i'),
                'status' => 'Đã tiếp nhận yêu cầu vận chuyển từ kho Mộc An',
                'location' => 'Kho tổng Mộc An - Hà Nội',
            ],
        ];
    }
}
