<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Show checkout page with order summary.
     */
    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng của bạn đang trống.',
                ], 400);
            }
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống. Hãy chọn sản phẩm trước khi thanh toán.');
        }

        $subtotal = $this->cartService->getSubtotal();
        $shippingFee = $this->cartService->getShippingFee();
        $total = $this->cartService->getTotal();
        $user = Auth::user();

        $data = compact('cart', 'subtotal', 'shippingFee', 'total', 'user');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('checkout.index', $data);
    }

    /**
     * Process checkout within DB transaction with pessimistic locking.
     */
    public function process(CheckoutRequest $request): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng của bạn đang trống.',
                ], 400);
            }
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        try {
            $order = DB::transaction(function () use ($request, $cart) {
                // Check and lock stock for each item
                $orderItemsData = [];
                $calculatedSubtotal = 0.0;

                foreach ($cart as $cartKey => $item) {
                    $variant = \App\Models\ProductVariant::lockForUpdate()->find($item['variant_id'] ?? 0);

                    if (!$variant) {
                        throw new \Exception("Biến thể của \"{$item['name']}\" không còn khả dụng.");
                    }

                    $product = Product::find($variant->product_id);
                    if (!$product || !$product->is_active) {
                        throw new \Exception("Sản phẩm \"{$item['name']}\" hiện không khả dụng.");
                    }

                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception("Biến thể \"{$variant->display_label}\" chỉ còn lại {$variant->stock} món trong kho.");
                    }

                    $unitPrice = $variant->final_price;
                    $variant->decrement('stock', $item['quantity']);

                    $itemTotal = $unitPrice * $item['quantity'];
                    $calculatedSubtotal += $itemTotal;

                    $orderItemsData[] = [
                        'product_variant_id' => $variant->id,
                        'product_name' => $product->name,
                        'variant_info' => $variant->display_label,
                        'quantity' => $item['quantity'],
                        'price' => $unitPrice,
                    ];
                }

                $shippingFee = (float) session('shipping_fee', $calculatedSubtotal >= 5000000 ? 0.0 : 50000.0);
                $totalAmount = $calculatedSubtotal + $shippingFee;

                // Create Order record
                $order = Order::create([
                    'order_code' => Order::generateOrderNumber(),
                    'user_id' => Auth::id(),
                    'customer_name' => $request->input('customer_name'),
                    'customer_email' => $request->input('customer_email'),
                    'customer_phone' => $request->input('customer_phone'),
                    'shipping_address' => $request->input('shipping_address'),
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => 0.0,
                    'total_price' => $totalAmount,
                    'payment_method' => $request->input('payment_method'),
                    'payment_status' => Order::PAYMENT_PENDING,
                    'order_status' => Order::STATUS_PENDING,
                    'note' => $request->input('notes'),
                    'province_id' => $request->input('province_id'),
                    'province_name' => $request->input('province_name'),
                    'district_id' => $request->input('district_id'),
                    'district_name' => $request->input('district_name'),
                    'ward_code' => $request->input('ward_code'),
                    'ward_name' => $request->input('ward_name'),
                ]);

                // Create OrderItem records
                foreach ($orderItemsData as $itemData) {
                    $order->items()->create($itemData);
                }

                // Clear cart
                $this->cartService->clear();
                session()->forget(['shipping_fee', 'shipping_destination']);

                // Auto-create GHN shipping order when enabled
                if (config('services.ghn.auto_create_order', true)) {
                    try {
                        app(\App\Services\Shipping\GhnService::class)->createShippingOrder($order);
                    } catch (\Throwable $ghnEx) {
                        \Illuminate\Support\Facades\Log::warning('Automatic GHN shipping order creation failed: ' . $ghnEx->getMessage());
                    }
                }

                return $order;
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công!',
                    'order' => $order->load('items'),
                    'payment_url' => match ($order->payment_method) {
                        'vnpay' => route('payments.vnpay.create', $order->order_code),
                        'momo' => route('payments.momo.create', $order->order_code),
                        default => route('checkout.success', $order->order_code),
                    },
                ], 201);
            }

            if ($order->payment_method === 'vnpay') {
                return redirect()->route('payments.vnpay.create', $order->order_code);
            }

            if ($order->payment_method === 'momo') {
                return redirect()->route('payments.momo.create', $order->order_code);
            }

            return redirect()->route('checkout.success', ['order_number' => $order->order_code])
                ->with('success', 'Đặt hàng thành công!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show order success page.
     */
    public function success(Request $request, string $orderNumber): View|JsonResponse
    {
        $order = Order::where('order_code', $orderNumber)
            ->with(['items.variant'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        }

        return view('checkout.success', compact('order'));
    }
}
