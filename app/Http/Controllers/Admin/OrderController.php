<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected OrderWorkflowService $orderWorkflowService
    ) {}

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): View
    {
        $query = Order::query()->with(['user', 'items'])->latest('created_at');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('order_code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%")
                    ->orWhere('customer_email', 'like', "%{$q}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($paymentMethod = $request->query('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->query('q', ''),
                'status' => $request->query('status', ''),
                'payment_status' => $request->query('payment_status', ''),
                'payment_method' => $request->query('payment_method', ''),
            ],
        ]);
    }

    /**
     * Display the specified order details.
     */
    public function show(Order $order): View
    {
        $order->load(['user', 'items.variant.product.primaryImage', 'paymentTransactions' => function ($q) {
            $q->latest('created_at');
        }]);

        $allowedStatuses = $this->orderWorkflowService->getAllowedNextStatuses($order);

        return view('admin.orders.show', [
            'order' => $order,
            'allowedStatuses' => $allowedStatuses,
        ]);
    }

    /**
     * Update the status of the specified order.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,packed,shipping,completed,canceled'],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái mới.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);

        try {
            $this->orderWorkflowService->transition($order, $validated['status']);

            // Auto dispatch shipping tracking if transitioned to shipping
            if ($validated['status'] === 'shipping' && empty($order->tracking_code)) {
                $shippingManager = app(\App\Services\Shipping\ShippingManager::class);
                $carrier = $request->input('shipping_carrier', 'ghn');
                $shipment = $shippingManager->createShipment($order->order_code, $carrier);

                $order->update([
                    'shipping_carrier' => $shipment['provider'],
                    'tracking_code' => $shipment['tracking_code'],
                    'shipped_at' => now(),
                ]);
            }

            return redirect()
                ->route('admin.orders.show', $order->order_code)
                ->with('success', 'Đã cập nhật trạng thái đơn hàng thành công.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Không thể cập nhật trạng thái đơn hàng.';

            return redirect()
                ->route('admin.orders.show', $order->order_code)
                ->withErrors($e->errors())
                ->with('error', $message);
        }
    }

    /**
     * Mark a bank transfer order as paid.
     */
    public function markPaid(Request $request, Order $order): RedirectResponse
    {
        try {
            $note = $request->input('payment_note');
            $this->orderWorkflowService->markBankTransferPaid($order, $note);

            return redirect()
                ->route('admin.orders.show', $order->order_code)
                ->with('success', 'Đã xác nhận nhận tiền chuyển khoản thành công.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Không thể xác nhận thanh toán.';

            return redirect()
                ->route('admin.orders.show', $order->order_code)
                ->withErrors($e->errors())
                ->with('error', $message);
        }
    }
}
