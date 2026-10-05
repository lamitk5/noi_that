<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\Shipping\GhnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShippingController extends Controller
{
    public function __construct(
        protected GhnService $ghnService,
        protected CartService $cartService
    ) {}

    /**
     * Get list of provinces from GHN.
     */
    public function getProvinces(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getProvinces(),
        ]);
    }

    /**
     * Get list of districts by province ID from GHN.
     */
    public function getDistricts(mixed $provinceId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getDistricts((int) $provinceId),
        ]);
    }

    /**
     * Get list of wards by district ID from GHN.
     */
    public function getWards(mixed $districtId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getWards((int) $districtId),
        ]);
    }

    /**
     * Calculate dynamic GHN shipping fee.
     */
    public function calculateFee(Request $request): JsonResponse
    {
        $toDistrictId = (int) $request->input('district_id');
        $toWardCode = (string) $request->input('ward_code');
        $defaultFee = (float) config('services.ghn.default_fee', 30000);

        if ($toDistrictId <= 0 || $toWardCode === '') {
            return response()->json([
                'success' => false,
                'shipping_fee' => $defaultFee,
                'formatted_fee' => number_format($defaultFee, 0, ',', '.') . '₫',
                'message' => 'Vui lòng chọn đầy đủ Quận/Huyện và Phường/Xã.',
            ]);
        }

        $items = $this->cartService->getSelectedItems();
        if ($items->isEmpty()) {
            $items = $this->cartService->getItems();
        }

        $totalWeight = 0;
        $maxLength = 30;
        $maxWidth = 20;
        $totalHeight = 0;

        foreach ($items as $item) {
            $qty = max(1, (int) $item->quantity);
            $product = $item->product;
            $variant = $item->variant;

            $dimString = $variant?->size ?: $product?->dimensions;
            $dims = \App\Services\Shipping\GhnService::parseDimensions($dimString);

            $unitWeight = 500;
            if ($product && !empty($product->weight) && (float) $product->weight > 0) {
                $unitWeight = (int) round(((float) $product->weight) * 1000);
            }
            $totalWeight += max(200, $unitWeight * $qty);
            $maxLength = max($maxLength, min(150, $dims['length']));
            $maxWidth = max($maxWidth, min(150, $dims['width']));
            $totalHeight += min(150, $dims['height']) * $qty;
        }

        $totalWeight = min(50000, max(500, $totalWeight));
        $packageLength = min(150, max(10, $maxLength));
        $packageWidth = min(150, max(10, $maxWidth));
        $packageHeight = min(150, max(10, $totalHeight));

        $orderValue = $this->cartService->getSelectedSubtotal() ?: $this->cartService->getSubtotal();

        $fee = $this->ghnService->calculateFee(
            $toDistrictId,
            $toWardCode,
            $totalWeight,
            $orderValue,
            $packageLength,
            $packageWidth,
            $packageHeight
        );

        session()->put('shipping_fee', $fee);
        session()->put('shipping_destination', [
            'district_id' => $toDistrictId,
            'ward_code' => $toWardCode,
        ]);

        $subtotal = $this->cartService->getSelectedSubtotal() ?: $this->cartService->getSubtotal();
        $quote = $this->cartService->quote($subtotal, $fee);
        $payable = $quote['payable_shipping'];
        $formattedFee = $payable <= 0
            ? 'Miễn phí'
            : number_format($payable, 0, ',', '.') . '₫';

        return response()->json([
            'success' => true,
            'shipping_fee' => $payable,
            'gross_shipping_fee' => $fee,
            'formatted_fee' => $formattedFee,
            'subtotal' => $subtotal,
            'discount_amount' => $quote['price_discount'],
            'shipping_discount' => $quote['shipping_discount'],
            'total_price' => $quote['total'],
            'formatted_total' => number_format($quote['total'], 0, ',', '.') . '₫',
        ]);
    }

    /**
     * GHN Webhook callback to update shipment status.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderCode = $payload['OrderCode'] ?? null;
        $status = $payload['Status'] ?? null;

        Log::info('GHN Webhook received', $payload);

        if ($orderCode && $status) {
            $order = Order::where('ghn_order_code', $orderCode)
                ->orWhere('order_code', $payload['ClientOrderCode'] ?? '')
                ->first();

            if ($order) {
                $order->update(['ghn_status' => $status]);

                if ($status === 'delivered') {
                    $order->update([
                        'order_status' => Order::STATUS_COMPLETED,
                        'payment_status' => Order::PAYMENT_PAID,
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Admin manual trigger to create GHN order.
     */
    public function adminCreateGhn(Order $order): RedirectResponse
    {
        try {
            $code = $this->ghnService->createShippingOrder($order);

            return back()->with('success', "Đã tạo vận đơn GHN thành công! Mã vận đơn: {$code}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể tạo vận đơn GHN: ' . $e->getMessage());
        }
    }
}
