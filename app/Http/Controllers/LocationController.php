<?php

namespace App\Http\Controllers;

use App\Exceptions\GHNException;
use App\Services\CartService;
use App\Services\Shipping\GHNOrderService;
use App\Services\Shipping\GHNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private GHNService $ghn,
        private CartService $cartService,
        private GHNOrderService $ghnOrders,
    ) {}

    public function provinces(): JsonResponse
    {
        try {
            return response()->json(['data' => $this->ghn->getProvinces()]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function districts(int $provinceId): JsonResponse
    {
        abort_unless($provinceId > 0, 404);

        try {
            return response()->json(['data' => $this->ghn->getDistricts($provinceId)]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function wards(int $districtId): JsonResponse
    {
        abort_unless($districtId > 0, 404);

        try {
            return response()->json(['data' => $this->ghn->getWards($districtId)]);
        } catch (GHNException) {
            return $this->unavailable();
        }
    }

    public function calculateFee(Request $request): JsonResponse
    {
        $input = $request->validate([
            'to_district_id' => ['required', 'integer', 'min:1'],
            'to_ward_code' => ['required', 'string', 'max:20'],
        ]);

        $items = $this->cartService->getItems();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Vui lòng chọn ít nhất một sản phẩm trong giỏ hàng.'], 422);
        }

        $weight = $this->ghnOrders->weightForCartItems($items);

        try {
            $feeData = $this->ghn->calculateFee(
                (int) $input['to_district_id'],
                $input['to_ward_code'],
                $weight,
            );
        } catch (GHNException) {
            return $this->unavailable();
        }

        $fee = (int) ($feeData['total'] ?? $feeData['service_fee'] ?? $feeData['fee'] ?? 0);

        return response()->json([
            'shipping_fee' => $fee,
            'weight' => $weight,
            'data' => $feeData,
        ]);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json(['message' => 'Không thể kết nối dịch vụ giao hàng.'], 502);
    }
}
