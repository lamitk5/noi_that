<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $code = strtoupper(trim($validated['code']));
        $subtotal = $cartService->subtotal();

        if ($subtotal <= 0) {
            return back()->with('coupon_error', 'Giỏ hàng của bạn đang trống.');
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return back()->with('coupon_error', 'Mã giảm giá "' . $code . '" không tồn tại hoặc đã hết hiệu lực.');
        }

        $error = null;
        if (! $coupon->isValidFor($subtotal, $error)) {
            return back()->with('coupon_error', $error ?: 'Mã giảm giá không hợp lệ cho đơn hàng này.');
        }

        $discount = $coupon->calculateDiscount($subtotal);

        session()->put('applied_coupon', [
            'code' => $coupon->code,
            'name' => $coupon->name ?: $coupon->code,
            'discount' => $discount,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
        ]);

        return back()->with('coupon_success', 'Áp dụng mã giảm giá ' . $coupon->code . ' thành công! Tiết kiệm: ' . number_format($discount, 0, ',', '.') . '₫');
    }

    public function remove(): RedirectResponse
    {
        session()->forget('applied_coupon');

        return back()->with('coupon_success', 'Đã hủy áp dụng mã giảm giá.');
    }
}
