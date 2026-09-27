<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    /**
     * Show tracking search page or perform tracking query.
     */
    public function index(Request $request): View|JsonResponse
    {
        $order = null;
        $searched = false;

        $orderCode = $request->input('order_number') ?: $request->input('order_code');
        $phone = $request->input('customer_phone');

        if (!empty($orderCode) && !empty($phone)) {
            $searched = true;
            $order = Order::where('order_code', trim((string) $orderCode))
                ->where('customer_phone', trim((string) $phone))
                ->with(['items.variant'])
                ->first();
        }

        if ($request->wantsJson()) {
            if ($searched && !$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng với thông tin đã cung cấp.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        }

        return view('orders.track', compact('order', 'searched'));
    }
}
