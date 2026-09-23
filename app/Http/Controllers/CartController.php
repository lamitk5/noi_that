<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cartService): View
    {
        $items = $cartService->getItems();
        $subtotal = $cartService->subtotal();

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

        $totalPrice = max(0, $subtotal - $discountAmount);

        return view('cart.index', compact('items', 'subtotal', 'appliedCoupon', 'discountAmount', 'totalPrice'));
    }

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->guest(route('login'))->with('error', 'Vui lòng đăng nhập tài khoản để mua hàng hoặc thêm vào giỏ.');
        }

        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $cartService->add((int) $validated['variant_id'], (int) $validated['quantity']);

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.index')->with('status', 'Tiến hành thanh toán cho sản phẩm vừa chọn.');
        }

        return redirect()->route('cart.index')->with('status', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, int $variantId, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartService->update($variantId, (int) $validated['quantity']);

        return redirect()->route('cart.index')->with('status', 'Đã cập nhật số lượng giỏ hàng.');
    }

    public function destroy(int $variantId, CartService $cartService): RedirectResponse
    {
        $cartService->remove($variantId);

        return redirect()->route('cart.index')->with('status', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function clear(CartService $cartService): RedirectResponse
    {
        $cartService->clear();

        return redirect()->route('cart.index')->with('status', 'Đã làm trống giỏ hàng.');
    }

    public function addBundle(Request $request, CartService $cartService): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->guest(route('login'))->with('error', 'Vui lòng đăng nhập tài khoản để mua combo hoặc thêm vào giỏ.');
        }

        $validated = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['integer', 'exists:product_variants,id'],
        ]);

        foreach ($validated['variant_ids'] as $vId) {
            $cartService->add((int) $vId, 1);
        }

        return redirect()->route('cart.index')->with('status', 'Đã thêm toàn bộ combo phòng vào giỏ hàng thành công! Đơn hàng được áp dụng ưu đãi combo.');
    }
}
