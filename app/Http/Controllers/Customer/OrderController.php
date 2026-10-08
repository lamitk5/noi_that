<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'reviewsByProduct' => $this->reviewsForOrders($orders->getCollection(), $user),
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

        $reviewsByProduct = $this->reviewsForOrders(collect([$order]), $user);

        return view('orders.show', compact('order', 'reviewsByProduct'));
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
            ->with('items.variant.product')
            ->firstOrFail();

        $cartService->setBuyNowMode(false);

        $addedCount = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;

            if (! $variant || ! $product) {
                $skipped[] = $item->product_name;
                continue;
            }

            try {
                $cartService->add($product, max(1, (int) $item->quantity), $variant);
                $addedCount++;
            } catch (\InvalidArgumentException $e) {
                $skipped[] = $item->product_name;
            }
        }

        if ($addedCount > 0) {
            $message = "Đã thêm {$addedCount} sản phẩm từ đơn {$order->order_code} vào giỏ hàng!";
            if ($skipped !== []) {
                $message .= ' Không thêm được: '.implode(', ', array_unique($skipped)).'.';
            }

            return redirect()->route('cart.index')->with('success', $message);
        }

        return back()->with('error', 'Các sản phẩm trong đơn hàng này hiện không còn khả dụng.');
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, Review>
     */
    private function reviewsForOrders(Collection $orders, User $user): Collection
    {
        $productIds = $orders
            ->filter(fn (Order $order) => $order->order_status === Order::STATUS_COMPLETED)
            ->flatMap(fn (Order $order) => $order->items->map(fn ($item) => $item->variant?->product_id))
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Review::query()
            ->where('user_id', $user->id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
    }
}
