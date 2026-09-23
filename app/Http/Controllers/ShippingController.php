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
    public function getDistricts(int $provinceId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getDistricts($provinceId),
        ]);
    }

    /**
     * Get list of wards by district ID from GHN.
     */
    public function getWards(int $districtId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getWards($districtId),
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

        $cart = $this->cartService->getCart();
        $totalWeight = max(500, array_sum(array_column($cart, 'quantity')) * 500);
        $orderValue = $this->cartService->getSubtotal();

        $fee = $this->ghnService->calculateFee($toDistrictId, $toWardCode, $totalWeight, $orderValue);

        session()->put('shipping_fee', $fee);
        session()->put('shipping_destination', [
            'district_id' => $toDistrictId,
            'ward_code' => $toWardCode,
        ]);

        $subtotal = $this->cartService->getSubtotal();
        $totalPrice = $subtotal + $fee;

        return response()->json([
            'success' => true,
            'shipping_fee' => $fee,
            'formatted_fee' => number_format($fee, 0, ',', '.') . '₫',
            'subtotal' => $subtotal,
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
