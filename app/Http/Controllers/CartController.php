<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display shopping cart.
     */
    public function index(Request $request): View|JsonResponse
    {
        $cart = $this->cartService->getCart();
        $subtotal = $this->cartService->getSubtotal();
        $shippingFee = $this->cartService->getShippingFee();
        $total = $this->cartService->getTotal();
        $count = $this->cartService->count();

        $data = compact('cart', 'subtotal', 'shippingFee', 'total', 'count');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('cart.index', $data);
    }

    /**
     * Add product to cart.
     */
    public function add(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = (int) $request->input('quantity', 1);

        try {
            $cart = $this->cartService->add($product, $quantity);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã thêm \"{$product->name}\" vào giỏ hàng!",
                    'cart_count' => $this->cartService->count(),
                    'cart_total' => $this->cartService->getTotal(),
                ]);
            }

            return redirect()->back()->with('success', "Đã thêm \"{$product->name}\" vào giỏ hàng thành công!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update product quantity in cart.
     */
    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $quantity = (int) $request->input('quantity');

        try {
            $this->cartService->update($product->id, $quantity);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật số lượng thành công!',
                    'cart_count' => $this->cartService->count(),
                    'subtotal' => $this->cartService->getSubtotal(),
                    'shipping_fee' => $this->cartService->getShippingFee(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }

            return redirect()->route('cart.index')->with('success', 'Cập nhật giỏ hàng thành công!');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $this->cartService->remove($product->id);

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

    /**
     * Clear all items in cart.
     */
    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cartService->clear();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã làm trống giỏ hàng.',
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Giỏ hàng đã được làm trống.');
    }
}
