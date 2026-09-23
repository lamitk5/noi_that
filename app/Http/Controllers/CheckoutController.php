<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected \App\Services\Shipping\GhnService $ghnService
    ) {}

    /**
     * Display the checkout page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $items = $this->cartService->getItems();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        // Generate idempotency token for double submit protection
        $token = Str::random(40);
        session()->put('checkout_token', $token);

        $subtotal = $this->cartService->subtotal();
        $shippingFee = (float) session()->get('shipping_fee', config('services.ghn.default_fee', 30000));

        $appliedCoupon = session()->get('applied_coupon');
        $discountAmount = 0.0;
        if ($appliedCoupon && ! empty($appliedCoupon['code'])) {
            $coupon = \App\Models\Coupon::where('code', $appliedCoupon['code'])->first();
            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            } else {
                session()->forget('applied_coupon');
                $appliedCoupon = null;
            }
        }

        $totalPrice = max(0, ($subtotal - $discountAmount) + $shippingFee);
        $provinces = $this->ghnService->getProvinces();

        return view('checkout.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'appliedCoupon' => $appliedCoupon,
            'discountAmount' => $discountAmount,
            'totalPrice' => $totalPrice,
            'user' => $request->user(),
            'checkoutToken' => $token,
            'provinces' => $provinces,
        ]);
    }

    /**
     * Process checkout and create order.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        // Validate double submission token
        $sessionToken = session()->get('checkout_token');
        if (! $sessionToken || $sessionToken !== $request->input('checkout_token')) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Yêu cầu thanh toán không hợp lệ hoặc đã được xử lý.');
        }

        try {
            $order = $this->checkoutService->checkout(
                $request->user(),
                $request->validated()
            );

            // Invalidate token after order created
            session()->forget('checkout_token');

            // Send Order Confirmation Email
            try {
                if (! empty($order->customer_email)) {
                    $order->loadMissing('items');
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderConfirmationMail($order));
                }
            } catch (\Throwable $mailEx) {
                \Illuminate\Support\Facades\Log::warning('Could not send order confirmation email: ' . $mailEx->getMessage());
            }

            if ($order->payment_method === 'vnpay') {
                return redirect()->route('payments.vnpay.create', $order->order_code);
            }

            if ($order->payment_method === 'momo') {
                return redirect()->route('payments.momo.create', $order->order_code);
            }

            return redirect()
                ->route('checkout.success', $order->order_code)
                ->with('success', 'Đặt hàng thành công! Cảm ơn bạn đã tin tưởng Mộc An.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?: 'Không thể hoàn tất đơn hàng.';

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $firstError);
        }
    }

    /**
     * Display order success confirmation page.
     */
    public function success(Request $request, string $orderCode): View
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variant.product.primaryImage'])
            ->firstOrFail();

        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        return view('checkout.success', [
            'order' => $order,
        ]);
    }
}
