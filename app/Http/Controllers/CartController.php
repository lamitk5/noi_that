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

        return view('cart.index', compact('items', 'subtotal'));
    }

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartService->add((int) $validated['variant_id'], (int) $validated['quantity']);

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
}
