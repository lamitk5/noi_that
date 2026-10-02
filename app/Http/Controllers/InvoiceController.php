<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function show(Request $request, string $orderCode): View
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product'])
            ->firstOrFail();

        abort_unless($this->canView($request, $order), 403, 'Bạn không có quyền xem hóa đơn này.');

        return view('orders.invoice', compact('order'));
    }

    protected function canView(Request $request, Order $order): bool
    {
        $user = $request->user();
        if ($user && ($user->isAdmin() || $user->isStaff() || $user->id === $order->user_id)) {
            return true;
        }

        // Links sent in the order confirmation email are signed.
        if ($request->hasValidSignature()) {
            return true;
        }

        if ($request->session()->get('last_order_code') === $order->order_code) {
            return true;
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $request->input('phone', ''));

        return $phone !== '' && $phone === preg_replace('/[^0-9]/', '', (string) $order->customer_phone);
    }
}
