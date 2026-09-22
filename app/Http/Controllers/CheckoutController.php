<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
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
        protected CheckoutService $checkoutService
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

        // Record behavioral tracking
        app(\App\Services\Analytics\BehavioralTracker::class)->track(
            $request,
            \App\Models\UserEvent::EVENT_CHECKOUT_STARTED,
            'cart',
            null,
            ['items_count' => $items->count(), 'subtotal' => (float) $this->cartService->subtotal()]
        );

        $user = $request->user();
        $subtotal = $this->cartService->subtotal();
        $shippingFee = (float) config('shop.shipping_fee', 0);

        // Voucher Calculation
        $appliedVoucher = null;
        $voucherDiscount = 0;
        if ($code = session()->get('applied_voucher_code')) {
            $voucher = Voucher::where('code', $code)->first();
            if ($voucher && $voucher->isValidFor($user, $subtotal)) {
                $appliedVoucher = $voucher;
                $voucherDiscount = $voucher->calculateDiscount($subtotal);
            } else {
                session()->forget('applied_voucher_code');
            }
        }

        // Loyalty Points Calculation
        $appliedPoints = 0;
        $pointsDiscount = 0;
        if ($points = session()->get('applied_loyalty_points')) {
            $availablePoints = $user ? $user->loyalty_points : 0;
            $appliedPoints = min((int) $points, $availablePoints);
            if ($appliedPoints > 0) {
                $maxDiscount = max(0, $subtotal - $voucherDiscount);
                $pointsDiscount = min($appliedPoints * 1000, $maxDiscount);
            } else {
                session()->forget('applied_loyalty_points');
            }
        }

        $totalPrice = max(0, $subtotal + $shippingFee - $voucherDiscount - $pointsDiscount);
        $addresses = $user ? $user->addresses()->orderByDesc('is_default')->get() : collect();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('checkout.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'voucherDiscount' => $voucherDiscount,
            'appliedVoucher' => $appliedVoucher,
            'appliedPoints' => $appliedPoints,
            'pointsDiscount' => $pointsDiscount,
            'totalPrice' => $totalPrice,
            'user' => $user,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'checkoutToken' => $token,
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
            $user = $request->user();

            if ($request->boolean('save_address') && $user) {
                $hasDefault = $user->addresses()->where('is_default', true)->exists();
                $user->addresses()->firstOrCreate(
                    [
                        'recipient_name' => $request->input('customer_name'),
                        'phone' => $request->input('customer_phone'),
                        'address_line' => $request->input('shipping_address'),
                    ],
                    [
                        'label' => 'Địa chỉ nhận hàng',
                        'is_default' => ! $hasDefault,
                    ]
                );
            }

            $order = $this->checkoutService->checkout(
                $user,
                $request->validated()
            );

            // Invalidate token after order created
            session()->forget('checkout_token');

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

    /**
     * Apply a promotional voucher.
     */
    public function applyVoucher(Request $request): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->input('code')));
        if (empty($code)) {
            return back()->with('error', 'Vui lòng nhập mã giảm giá.');
        }

        $voucher = Voucher::where('code', $code)->first();
        if (! $voucher) {
            return back()->with('error', 'Mã giảm giá không tồn tại.');
        }

        $subtotal = $this->cartService->subtotal();
        if (! $voucher->isValidFor($request->user(), $subtotal)) {
            $reason = 'Mã giảm giá không thể áp dụng cho đơn hàng này.';
            if (! $voucher->is_active) {
                $reason = 'Mã giảm giá hiện đang bị tạm khóa.';
            } elseif ($voucher->starts_at && $voucher->starts_at->isFuture()) {
                $reason = 'Chương trình khuyến mãi chưa bắt đầu.';
            } elseif ($voucher->expires_at && $voucher->expires_at->isPast()) {
                $reason = 'Mã giảm giá đã hết hạn sử dụng.';
            } elseif ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
                $reason = 'Mã giảm giá đã hết lượt sử dụng.';
            } elseif ($voucher->min_order_amount && $subtotal < $voucher->min_order_amount) {
                $reason = 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher->min_order_amount, 0, ',', '.') . '₫.';
            }

            return back()->with('error', $reason);
        }

        session()->put('applied_voucher_code', $voucher->code);

        return back()->with('success', "Đã áp dụng mã giảm giá {$voucher->code} thành công!");
    }

    /**
     * Remove the currently applied voucher.
     */
    public function removeVoucher(): RedirectResponse
    {
        session()->forget('applied_voucher_code');

        return back()->with('success', 'Đã hủy áp dụng mã giảm giá.');
    }

    /**
     * Apply loyalty points redemption.
     */
    public function applyPoints(Request $request): RedirectResponse
    {
        $user = $request->user();
        $points = (int) $request->input('points', 0);

        if ($points <= 0) {
            return back()->with('error', 'Số điểm quy đổi phải lớn hơn 0.');
        }

        if ($points > ($user ? $user->loyalty_points : 0)) {
            return back()->with('error', 'Bạn không có đủ số điểm thưởng yêu cầu.');
        }

        session()->put('applied_loyalty_points', $points);

        return back()->with('success', "Đã áp dụng quy đổi {$points} điểm tích lũy!");
    }

    /**
     * Remove loyalty points redemption.
     */
    public function removePoints(): RedirectResponse
    {
        session()->forget('applied_loyalty_points');

        return back()->with('success', 'Đã hủy dùng điểm tích lũy.');
    }
}
