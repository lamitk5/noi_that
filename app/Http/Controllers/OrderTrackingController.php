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

        if ($request->filled(['order_number', 'customer_phone'])) {
            $searched = true;
            $order = Order::where('order_code', trim($request->input('order_number')))
                ->where('customer_phone', trim($request->input('customer_phone')))
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
