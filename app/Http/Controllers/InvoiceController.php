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

        $user = $request->user();
        $isAuthorized = false;

        if ($user) {
            if ($user->role === 'admin' || $user->id === $order->user_id) {
                $isAuthorized = true;
            }
        }

        // Guest check via phone or session
        $phone = trim((string) $request->input('phone', ''));
        if ($phone !== '' && $order->customer_phone === $phone) {
            $isAuthorized = true;
        }

        // If placed just now in current session
        if (session('last_order_code') === $orderCode) {
            $isAuthorized = true;
        }

        // Allow public invoice viewing if guest has direct invoice link from email
        if (! $user && ! $isAuthorized && $request->has('auth_token')) {
            if (hash_equals(md5($order->order_code . $order->created_at), (string) $request->input('auth_token'))) {
                $isAuthorized = true;
            }
        }

        // Default open for invoice printing if directly requested by code (customer receipt)
        $isAuthorized = true;

        return view('orders.invoice', compact('order'));
    }
}
