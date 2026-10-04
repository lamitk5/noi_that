<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of customer orders with full status filter tabs.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $currentStatus = $request->query('status', 'all');

        // Base query for this customer
        $baseQuery = Order::where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if (!empty($user->email)) {
                $q->orWhere('customer_email', $user->email);
            }
            if (!empty($user->phone)) {
                $q->orWhere('customer_phone', $user->phone);
            }
        });

        // Compute counts for all 5 order statuses + all
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('order_status', Order::STATUS_PENDING)->count(),
            'confirmed' => (clone $baseQuery)->where('order_status', Order::STATUS_CONFIRMED)->count(),
            'shipping' => (clone $baseQuery)->where('order_status', Order::STATUS_SHIPPING)->count(),
            'completed' => (clone $baseQuery)->where('order_status', Order::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->whereIn('order_status', [Order::STATUS_CANCELLED, 'canceled'])->count(),
        ];

        // Apply selected status filter
        $query = (clone $baseQuery)->with(['items.variant.product.images'])->latest();

        if ($currentStatus !== 'all' && array_key_exists($currentStatus, $counts)) {
            if ($currentStatus === 'cancelled') {
                $query->whereIn('order_status', [Order::STATUS_CANCELLED, 'canceled']);
            } else {
                $query->where('order_status', $currentStatus);
            }
        }

        $orders = $query->paginate(8)->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'counts' => $counts,
            'currentStatus' => $currentStatus,
        ]);
    }

    /**
     * Show detail of an order.
     */
    public function show(Request $request, string $orderCode): View|JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('order_code', $orderCode)
            ->where(function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->email)) {
                        $q->orWhere('customer_email', $user->email);
                    }
                    if (!empty($user->phone)) {
                        $q->orWhere('customer_phone', $user->phone);
                    }
                }
            })
            ->with(['items.variant.product.images', 'paymentTransactions'])
            ->firstOrFail();

        if ($request->boolean('status')) {
            return response()->json([
                'order_status' => (string) $order->order_status,
                'payment_status' => (string) $order->payment_status,
                'ghn_status' => $order->ghn_status,
            ]);
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Customer cancels an order (only if still pending).
     */
    public function cancel(Request $request, string $orderCode): RedirectResponse
    {
        $user = Auth::user();

        $order = Order::where('order_code', $orderCode)
            ->where(function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id)
                        ->orWhere('customer_email', $user->email);
                }
            })
            ->firstOrFail();

        if ($order->order_status !== Order::STATUS_PENDING) {
            return back()->with('error', 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái Chờ xử lý.');
        }

        $reason = $request->input('cancel_reason', 'Khách hàng yêu cầu hủy đơn');

        $order->update([
            'order_status' => Order::STATUS_CANCELLED,
            'note' => trim(($order->note ? $order->note . " | " : "") . "Lý do hủy: " . $reason),
        ]);

        return back()->with('success', 'Đơn hàng ' . $order->order_code . ' đã được hủy thành công.');
    }

    /**
     * Re-add all products from a previous order into the cart.
     */
    public function reorder(string $orderCode, CartService $cartService): RedirectResponse
    {
        $user = Auth::user();

        $order = Order::where('order_code', $orderCode)
            ->where(function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id)
                        ->orWhere('customer_email', $user->email);
                }
            })
            ->with('items.variant')
            ->firstOrFail();

        $addedCount = 0;
        foreach ($order->items as $item) {
            $variant = $item->variant;
            if ($variant) {
                $cartService->add($variant, (int) $item->quantity);
                $addedCount++;
            }
        }

        if ($addedCount > 0) {
            return redirect()->route('cart.index')->with('success', "Đã thêm {$addedCount} sản phẩm từ đơn {$order->order_code} vào giỏ hàng!");
        }

        return back()->with('error', 'Các sản phẩm trong đơn hàng này hiện không còn khả dụng.');
    }
}
