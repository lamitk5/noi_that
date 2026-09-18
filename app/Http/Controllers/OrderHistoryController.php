<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, string $orderCode): View
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product.primaryImage'])
            ->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        return view('orders.show', [
            'order' => $order,
        ]);
    }
}