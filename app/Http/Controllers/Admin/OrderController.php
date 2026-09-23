<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Order::with('user');

        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        }

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View|JsonResponse
    {
        $order->load(['items.variant.product', 'user']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled,canceled'],
                'payment_status' => ['required', 'in:pending,paid,failed'],
            ], [
                'order_status.required' => 'Vui lòng chọn trạng thái đơn hàng.',
                'order_status.in' => 'Trạng thái đơn hàng không hợp lệ.',
                'payment_status.required' => 'Vui lòng chọn trạng thái thanh toán.',
                'payment_status.in' => 'Trạng thái thanh toán không hợp lệ.',
            ]);

            $oldStatus = $order->order_status;
            $newStatus = $validated['order_status'];

            $isOldCancelled = in_array($oldStatus, ['canceled', 'cancelled']);
            $isNewCancelled = in_array($newStatus, ['canceled', 'cancelled']);

            DB::transaction(function () use ($order, $validated, $isOldCancelled, $isNewCancelled, $newStatus) {
                if (!$isOldCancelled && $isNewCancelled) {
                    foreach ($order->items as $item) {
                        if ($item->variant) {
                            $item->variant->increment('stock', $item->quantity);
                        }
                    }
                }

                if ($isOldCancelled && !$isNewCancelled) {
                    foreach ($order->items as $item) {
                        if ($item->variant) {
                            $item->variant->decrement('stock', $item->quantity);
                        }
                    }
                }

                $validated['order_status'] = $isNewCancelled ? 'canceled' : $newStatus;
                $order->update($validated);
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái đơn hàng thành công!',
                    'order' => $order->fresh(),
                ]);
            }

            return redirect()->route('admin.orders.show', $order)->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order status update failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $message = 'Cập nhật trạng thái đơn hàng thất bại: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }
}
