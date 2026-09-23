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
        $provinces = $this->ghnService->getProvinces();

        return response()->json([
            'success' => true,
            'data' => $provinces,
        ]);
    }

    /**
     * Get list of districts by province ID from GHN.
     */
    public function getDistricts(int $provinceId): JsonResponse
    {
        $districts = $this->ghnService->getDistricts($provinceId);

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    /**
     * Get list of wards by district ID from GHN.
     */
    public function getWards(int $districtId): JsonResponse
    {
        $wards = $this->ghnService->getWards($districtId);

        return response()->json([
            'success' => true,
            'data' => $wards,
        ]);
    }

    /**
     * Calculate dynamic GHN shipping fee.
     */
    public function calculateFee(Request $request): JsonResponse
    {
        $toDistrictId = (int) $request->input('district_id');
        $toWardCode = (string) $request->input('ward_code');

        if ($toDistrictId <= 0 || empty($toWardCode)) {
            return response()->json([
                'success' => false,
                'shipping_fee' => (float) config('services.ghn.default_fee', 30000),
                'formatted_fee' => number_format((float) config('services.ghn.default_fee', 30000), 0, ',', '.') . '₫',
                'message' => 'Vui lòng chọn đầy đủ Quận/Huyện và Phường/Xã.',
            ]);
        }

        $items = $this->cartService->getItems();
        $totalWeight = max(500, $items->sum('quantity') * 500);
        $orderValue = (float) $this->cartService->subtotal();

        $fee = $this->ghnService->calculateFee($toDistrictId, $toWardCode, $totalWeight, $orderValue);

        // Store selected shipping info in session
        session()->put('shipping_fee', $fee);
        session()->put('shipping_destination', [
            'district_id' => $toDistrictId,
            'ward_code' => $toWardCode,
        ]);

        $subtotal = $this->cartService->subtotal();
        $discount = 0.0;
        $appliedCoupon = session()->get('applied_coupon');
        if ($appliedCoupon && !empty($appliedCoupon['code'])) {
            $coupon = \App\Models\Coupon::where('code', $appliedCoupon['code'])->first();
            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $totalPrice = max(0, ($subtotal - $discount) + $fee);

        return response()->json([
            'success' => true,
            'shipping_fee' => $fee,
            'formatted_fee' => number_format($fee, 0, ',', '.') . '₫',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_price' => $totalPrice,
            'formatted_total' => number_format($totalPrice, 0, ',', '.') . '₫',
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
                $order->update([
                    'ghn_status' => $status,
                ]);

                if ($status === 'delivered') {
                    $order->update([
                        'order_status' => Order::STATUS_COMPLETED,
                        'payment_status' => Order::PAYMENT_PAID,
                    ]);
                } elseif (in_array($status, ['cancel', 'delivery_fail'])) {
                    $order->update([
                        'ghn_status' => $status,
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
