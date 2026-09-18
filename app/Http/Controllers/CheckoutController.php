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

                foreach ($cart as $productId => $item) {
                    $product = Product::lockForUpdate()->find($productId);

                    if (!$product || !$product->is_active) {
                        throw new \Exception("Sản phẩm \"{$item['name']}\" hiện không khả dụng.");
                    }

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Sản phẩm \"{$product->name}\" chỉ còn lại {$product->stock_quantity} món trong kho.");
                    }

                    $unitPrice = $product->final_price;
                    $itemTotal = $unitPrice * $item['quantity'];
                    $calculatedSubtotal += $itemTotal;

                    // Decrement stock quantity
                    $product->decrement('stock_quantity', $item['quantity']);

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'price' => $unitPrice,
                        'quantity' => $item['quantity'],
                        'total' => $itemTotal,
                    ];
                }

                $shippingFee = $calculatedSubtotal >= 5000000 ? 0.0 : 50000.0;
                $totalAmount = $calculatedSubtotal + $shippingFee;

                // Create Order record
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => Auth::id(),
                    'customer_name' => $request->input('customer_name'),
                    'customer_email' => $request->input('customer_email'),
                    'customer_phone' => $request->input('customer_phone'),
                    'shipping_address' => $request->input('shipping_address'),
                    'subtotal' => $calculatedSubtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => 0.0,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->input('payment_method'),
                    'payment_status' => Order::PAYMENT_PENDING,
                    'order_status' => Order::STATUS_PENDING,
                    'notes' => $request->input('notes'),
                ]);

                // Create OrderItem records
                foreach ($orderItemsData as $itemData) {
                    $order->items()->create($itemData);
                }

                // Clear cart
                $this->cartService->clear();

                return $order;
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công!',
                    'order' => $order->load('items'),
                ], 201);
            }

            return redirect()->route('checkout.success', ['order_number' => $order->order_number])
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
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.product'])
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
