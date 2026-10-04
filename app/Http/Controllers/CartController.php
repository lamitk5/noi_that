<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CouponRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->cartService->setBuyNowMode(false);
        $items = $this->cartService->getItems();
        $cart = $this->cartService->getCart();
        $selectedKeys = $this->cartService->getSelectedKeys();
        $selectedCount = count($selectedKeys);
        $subtotal = $this->cartService->getSelectedSubtotal();
        $hasCalculatedShipping = $this->cartService->hasCalculatedShipping();
        $shippingFee = $this->cartService->getShippingFee();
        $coupon = $this->cartService->getCoupon();
        $discountAmount = $this->cartService->getDiscountAmount();
        $availableCoupons = $this->cartService->getAvailableCoupons();
        $total = $this->cartService->getTotal();
        $count = $this->cartService->count();

        $couponUsed = $request->user() ? app(CouponRedemptionService::class)->redemptionFor($request->user())?->load('order') : null;

        $data = compact('cart', 'items', 'selectedKeys', 'selectedCount', 'subtotal', 'hasCalculatedShipping', 'shippingFee', 'coupon', 'couponUsed', 'discountAmount', 'availableCoupons', 'total', 'count');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return view('cart.index', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($request->input('variant_id'));

        return $this->add($request, $variant->product);
    }

    public function add(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $quantity = (int) $request->input('quantity', 1);
        $variantId = $request->input('product_variant_id') ?? $request->input('variant_id');
        $variant = $variantId
            ? ProductVariant::where('product_id', $product->id)->find($variantId)
            : null;
        $isBuyNow = (bool) $request->input('buy_now', false);

        // Luồng Mua nhanh: lưu phiên mua ngay riêng, KHÔNG thêm vào giỏ hàng
        if ($isBuyNow) {
            try {
                if (! $variant) {
                    $variant = $product->variants()->where('stock', '>', 0)->first();
                }

                if (! $variant) {
                    throw new \InvalidArgumentException('Sản phẩm chưa có biến thể khả dụng.');
                }

                if (! $product->is_active) {
                    throw new \InvalidArgumentException('Sản phẩm hiện không khả dụng.');
                }

                if ($quantity > $variant->stock) {
                    throw new \InvalidArgumentException("Số lượng yêu cầu ({$quantity}) vượt quá số lượng còn lại trong kho ({$variant->stock}).");
                }

                $this->cartService->setBuyNowItem($product, $quantity, $variant);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => route('checkout.index', ['buy_now' => 1]),
                        'message' => 'Đang chuyển đến trang thanh toán...',
                    ]);
                }

                return redirect()->route('checkout.index', ['buy_now' => 1]);
            } catch (\InvalidArgumentException $e) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                }

                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        // Luồng Thêm vào giỏ hàng thông thường
        $this->cartService->setBuyNowMode(false);
        try {
            $this->cartService->add($product, $quantity, $variant);
            $label = $product->name;

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã thêm \"{$label}\" vào giỏ hàng!",
                    'cart_count' => $this->cartService->count(),
                    'cart_total' => $this->cartService->getTotal(),
                ]);
            }

            return redirect()->back()->with('success', "Đã thêm \"{$label}\" vào giỏ hàng!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $code = (string) ($request->input('coupon_code') ?? $request->input('code') ?? '');

        if (trim($code) === '') {
            $msg = 'Vui lòng nhập mã giảm giá.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        [$allowed, $retryIn] = app(CouponRedemptionService::class)
            ->hitApplyLimit($request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip());

        if (! $allowed) {
            $msg = "Bạn nhập mã quá nhiều lần. Vui lòng thử lại sau {$retryIn} giây.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 429);
            }
            return redirect()->back()->with('error', $msg);
        }

        try {
            $coupon = $this->cartService->applyCoupon($code, $request->user());
            $discount = $this->cartService->getDiscountAmount();
            $msg = 'Áp dụng mã giảm giá "'.$coupon['code'].'" thành công!';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'coupon' => $coupon,
                    'discount_amount' => $discount,
                    'subtotal' => $this->cartService->getSubtotal(),
                    'shipping_fee' => $this->cartService->getShippingFee(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $this->cartService->removeCoupon();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ bỏ mã giảm giá.',
                'subtotal' => $this->cartService->getSubtotal(),
                'shipping_fee' => $this->cartService->getShippingFee(),
                'total' => $this->cartService->getTotal(),
            ]);
        }

        return redirect()->back()->with('success', 'Đã gỡ bỏ mã giảm giá.');
    }

    public function update(Request $request, string $cartKey): RedirectResponse|JsonResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);

        try {
            $this->cartService->update($cartKey, (int) $request->input('quantity'));

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật số lượng thành công!',
                    'cart_count' => $this->cartService->count(),
                    'subtotal' => $this->cartService->getSubtotal(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }

            return redirect()->route('cart.index')->with('success', 'Cập nhật giỏ hàng thành công!');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function remove(Request $request, string $cartKey): RedirectResponse|JsonResponse
    {
        $this->cartService->remove($cartKey);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
                'cart_count' => $this->cartService->count(),
                'total' => $this->cartService->getTotal(),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cartService->clear();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã làm trống giỏ hàng.']);
        }

        return redirect()->route('cart.index')->with('success', 'Giỏ hàng đã được làm trống.');
    }

    public function select(Request $request): JsonResponse
    {
        $request->validate([
            'selected_keys' => ['nullable', 'array'],
            'selected_keys.*' => ['string'],
        ]);

        $keys = (array) $request->input('selected_keys', []);
        $this->cartService->setSelectedKeys($keys);

        $selectedKeys = $this->cartService->getSelectedKeys();
        $subtotal = $this->cartService->getSelectedSubtotal();
        $discountAmount = $this->cartService->getDiscountAmount();
        $shippingFee = $this->cartService->getShippingFee();
        $total = $this->cartService->getTotal();

        return response()->json([
            'success' => true,
            'selected_keys' => $selectedKeys,
            'selected_count' => count($selectedKeys),
            'subtotal' => $subtotal,
            'formatted_subtotal' => number_format($subtotal, 0, ',', '.') . '₫',
            'discount_amount' => $discountAmount,
            'formatted_discount' => number_format($discountAmount, 0, ',', '.') . '₫',
            'shipping_fee' => $shippingFee,
            'formatted_shipping' => number_format($shippingFee, 0, ',', '.') . '₫',
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . '₫',
        ]);
    }

    public function checkoutSelected(Request $request): RedirectResponse
    {
        $keys = (array) ($request->input('selected_items') ?? $request->input('selected_keys') ?? []);

        if (empty($keys)) {
            return redirect()->route('cart.index')->with('error', 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.');
        }

        $this->cartService->setSelectedKeys($keys);

        return redirect()->route('checkout.index');
    }
}
