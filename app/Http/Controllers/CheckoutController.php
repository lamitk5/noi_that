<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $isBuyNow = $request->boolean('buy_now') || session('is_buy_now', false);
        $this->cartService->setBuyNowMode($isBuyNow);

        $cart = $this->cartService->getSelectedCart();

        if (empty($cart)) {
            if ($isBuyNow) {
                $this->cartService->setBuyNowMode(false);
                return redirect()->route('products.index')
                    ->with('error', 'Chưa có sản phẩm để mua nhanh.');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa chọn sản phẩm nào để thanh toán.',
                ], 400);
            }

            return redirect()->route('cart.index')
                ->with('error', 'Vui lòng chọn ít nhất 1 sản phẩm trước khi thanh toán.');
        }

        if (! $request->has('ward_code') && ! old('ward_code')) {
            session()->forget(['shipping_fee', 'shipping_destination']);
        }

        $items = $this->cartService->getSelectedItems();
        $subtotal = $this->cartService->getSelectedSubtotal();
        $hasCalculatedShipping = $this->cartService->hasCalculatedShipping();
        $shippingFee = $this->cartService->getShippingFee();
        $coupon = $this->cartService->getCoupon();
        $discountAmount = $this->cartService->getDiscountAmount();
        $availableCoupons = $this->cartService->getAvailableCoupons();
        $total = $this->cartService->getTotal();
        $totalPrice = $total;
        $user = Auth::user();
        $savedAddresses = [];
        if ($user) {
            if (!empty($user->address)) {
                $savedAddresses[] = trim($user->address);
            }
            $recentOrderAddresses = Order::where('user_id', $user->id)
                ->whereNotNull('shipping_address')
                ->where('shipping_address', '!=', '')
                ->latest()
                ->take(5)
                ->pluck('shipping_address')
                ->all();

            foreach ($recentOrderAddresses as $addr) {
                $addr = trim($addr);
                if ($addr !== '' && !in_array($addr, $savedAddresses, true)) {
                    $savedAddresses[] = $addr;
                }
            }
        }
        $checkoutToken = (string) Str::random(40);
        session(['checkout_token' => $checkoutToken]);

        $data = compact('cart', 'items', 'subtotal', 'hasCalculatedShipping', 'shippingFee', 'coupon', 'discountAmount', 'availableCoupons', 'total', 'totalPrice', 'user', 'savedAddresses', 'checkoutToken', 'isBuyNow');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('checkout.index', $data);
    }

    public function process(CheckoutRequest $request): RedirectResponse|JsonResponse
    {
        $isBuyNow = $request->boolean('is_buy_now') || session('is_buy_now', false);
        $this->cartService->setBuyNowMode($isBuyNow);

        $cart = $this->cartService->getSelectedCart();

        if (empty($cart) && session()->has('cart')) {
            $legacyCart = session('cart', []);
            $cart = [];
            foreach ($legacyCart as $variantId => $qty) {
                $variant = \App\Models\ProductVariant::with('product')->find($variantId);
                if ($variant) {
                    $key = "{$variant->product_id}-{$variant->id}";
                    $cart[$key] = [
                        'id' => $variant->product_id,
                        'key' => $key,
                        'variant_id' => $variant->id,
                        'name' => $variant->product?->name ?? 'Sản phẩm nội thất',
                        'price' => (float) $variant->final_price,
                        'quantity' => (int) $qty,
                    ];
                }
            }
        }

        if (empty($cart)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không có sản phẩm nào được chọn.',
                ], 400);
            }

            return redirect()->route($isBuyNow ? 'products.index' : 'cart.index')
                ->with('error', 'Vui lòng chọn ít nhất 1 sản phẩm trước khi thanh toán.');
        }

        $paymentMethod = $request->input('payment_method');

        try {
            $order = DB::transaction(function () use ($request, $cart, $paymentMethod, $isBuyNow) {
                $orderItemsData = [];
                $calculatedSubtotal = 0.0;

                foreach ($cart as $item) {
                    $variant = \App\Models\ProductVariant::lockForUpdate()->find($item['variant_id'] ?? 0);

                    if (! $variant) {
                        throw new \Exception('Biến thể sản phẩm không còn khả dụng.');
                    }

                    $product = Product::find($variant->product_id);
                    if (! $product || ! $product->is_active) {
                        throw new \Exception("Sản phẩm \"{$item['name']}\" hiện không khả dụng.");
                    }

                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception("Biến thể \"{$variant->display_label}\" chỉ còn lại {$variant->stock} món trong kho.");
                    }

                    $unitPrice = (float) $variant->final_price;
                    $variant->decrement('stock', $item['quantity']);
                    $calculatedSubtotal += $unitPrice * $item['quantity'];

                    $orderItemsData[] = [
                        'product_variant_id' => $variant->id,
                        'product_name' => $product->name,
                        'variant_info' => $variant->display_label,
                        'quantity' => $item['quantity'],
                        'price' => $unitPrice,
                    ];
                }

                $shippingFee = (float) session('shipping_fee', (float) config('services.ghn.default_fee', 30000.0));
                $coupon = $this->cartService->getCoupon();
                $discountAmount = $this->cartService->getDiscountAmount();
                $totalAmount = max(0.0, $calculatedSubtotal + $shippingFee - $discountAmount);

                $order = Order::create([
                    'order_code' => Order::generateOrderNumber(),
                    'user_id' => Auth::id(),
                    'customer_name' => $request->input('customer_name'),
                    'customer_email' => $request->input('customer_email'),
                    'customer_phone' => $request->input('customer_phone'),
                    'shipping_address' => $request->input('shipping_address'),
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'coupon_code' => $coupon ? $coupon['code'] : null,
                    'total_price' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => Order::PAYMENT_PENDING,
                    'order_status' => Order::STATUS_PENDING,
                    'note' => $request->input('note') ?? $request->input('notes'),
                    'province_id' => $request->input('province_id'),
                    'province_name' => $request->input('province_name'),
                    'district_id' => $request->input('district_id'),
                    'district_name' => $request->input('district_name'),
                    'ward_code' => $request->input('ward_code'),
                    'ward_name' => $request->input('ward_name'),
                ]);

                foreach ($orderItemsData as $itemData) {
                    $order->items()->create($itemData);
                }

                if ($isBuyNow) {
                    $this->cartService->clearBuyNow();
                } else {
                    $this->cartService->removeSelected();
                }

                session()->forget(['shipping_fee', 'shipping_destination', 'checkout_token']);

                if (config('services.ghn.auto_create_order', true)) {
                    try {
                        app(\App\Services\Shipping\GhnService::class)->createShippingOrder($order);
                    } catch (\Throwable $ghnEx) {
                        \Illuminate\Support\Facades\Log::warning('Automatic GHN shipping order creation failed: '.$ghnEx->getMessage());
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

    public function success(Request $request, string $orderNumber): View|JsonResponse
    {
        $order = Order::where('order_code', $orderNumber)
            ->with(['items.variant'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $order]);
        }

        return view('checkout.success', compact('order'));
    }
}
