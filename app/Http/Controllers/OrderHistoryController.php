<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.variant.product.primaryImage'])
            ->latest()
            ->paginate(10);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, string $orderCode, OrderWorkflowService $workflowService): View
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product.primaryImage', 'voucher', 'paymentTransactions'])
            ->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        $canCancel = $workflowService->canTransition($order, Order::STATUS_CANCELED);
        $canRetryPayment = $order->order_status === Order::STATUS_PENDING
            && $order->payment_status !== Order::PAYMENT_PAID;

        return view('orders.show', [
            'order' => $order,
            'canCancel' => $canCancel,
            'canRetryPayment' => $canRetryPayment,
        ]);
    }

    /**
     * Buy again from a completed/placed order.
     */
    public function buyAgain(Request $request, string $orderCode, CartService $cartService): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product'])
            ->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền thao tác trên đơn hàng này.');
        }

        $result = $cartService->buyAgain($order);

        if ($result['added_count'] === 0) {
            return redirect()->route('cart.index')->withErrors([
                'buy_again' => 'Không thể thêm sản phẩm nào vào giỏ do các sản phẩm đã hết hàng hoặc ngừng kinh doanh.',
            ]);
        }

        $message = "Đã thêm {$result['added_count']} sản phẩm vào giỏ hàng.";
        if (! empty($result['warnings'])) {
            return redirect()->route('cart.index')
                ->with('status', $message)
                ->with('warning', implode(' ', $result['warnings']));
        }

        return redirect()->route('cart.index')->with('status', $message);
    }

    /**
     * Cancel an order through OrderWorkflowService.
     */
    public function cancel(Request $request, string $orderCode, OrderWorkflowService $workflowService): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền hủy đơn hàng này.');
        }

        if (! $workflowService->canTransition($order, Order::STATUS_CANCELED)) {
            return back()->with('error', 'Đơn hàng này không thể hủy ở trạng thái hiện tại hoặc đã được thanh toán trực tuyến.');
        }

        try {
            $workflowService->transition($order, Order::STATUS_CANCELED);

            return redirect()->route('orders.show', $order->order_code)->with('status', 'Đơn hàng #' . $order->order_code . ' đã được hủy thành công.');
        } catch (ValidationException $e) {
            return redirect()->route('orders.show', $order->order_code)->with('error', $e->getMessage());
        }
    }

    /**
     * Print-friendly invoice view.
     */
    public function print(Request $request, string $orderCode): View
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product', 'voucher'])
            ->firstOrFail();

        if ($order->user_id !== $request->user()->id && ! ($request->user() && $request->user()->role === 'admin')) {
            abort(403, 'Bạn không có quyền in đơn hàng này.');
        }

        return view('orders.print', [
            'order' => $order,
        ]);
    }

    /**
     * Retry payment for an unpaid pending order.
     */
    public function retryPayment(Request $request, string $orderCode): RedirectResponse
    {
        $order = Order::where('order_code', $orderCode)->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền thanh toán đơn hàng này.');
        }

        if ($order->payment_status === Order::PAYMENT_PAID || $order->order_status !== Order::STATUS_PENDING) {
            return back()->with('error', 'Đơn hàng đã được thanh toán hoặc không ở trạng thái chờ.');
        }

        $newMethod = $request->input('payment_method');
        if ($newMethod && in_array($newMethod, ['vnpay', 'momo', 'bank_transfer', 'cod'], true)) {
            $order->update(['payment_method' => $newMethod]);
        }

        if ($order->payment_method === 'vnpay') {
            return redirect()->route('payments.vnpay.create', $order->order_code);
        }

        if ($order->payment_method === 'momo') {
            return redirect()->route('payments.momo.create', $order->order_code);
        }

        return redirect()->route('orders.show', $order->order_code)
            ->with('success', 'Phương thức thanh toán đã được cập nhật.');
    }
}