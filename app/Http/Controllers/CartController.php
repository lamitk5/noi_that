<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(Request $request): View|JsonResponse
    {
        $items = $this->cartService->getItems();
        $cart = $this->cartService->getCart();
        $subtotal = $this->cartService->getSubtotal();
        $shippingFee = $this->cartService->getShippingFee();
        $total = $this->cartService->getTotal();
        $count = $this->cartService->count();

        $data = compact('cart', 'items', 'subtotal', 'shippingFee', 'total', 'count');

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
}
