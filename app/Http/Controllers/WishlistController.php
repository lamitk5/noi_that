<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();

        $products = $user
            ? Product::active()
                ->with(['category', 'images', 'variants'])
                ->whereHas('wishlists', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get()
            : collect();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
            ]);
        }

        return view('wishlist.index', compact('products'));
    }

    /**
     * Toggle a product in the signed-in user's wishlist.
     */
    public function toggle(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để lưu sản phẩm yêu thích.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để lưu sản phẩm yêu thích.');
        }

        $userId = Auth::id();
        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
            $message = "Đã bỏ \"{$product->name}\" khỏi danh sách yêu thích.";
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            $inWishlist = true;
            $message = "Đã thêm \"{$product->name}\" vào danh sách yêu thích.";
        }

        $count = Wishlist::where('user_id', $userId)->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'in_wishlist' => $inWishlist,
                'count' => $count,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function remove(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để quản lý danh sách yêu thích.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để quản lý danh sách yêu thích.');
        }

        Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        $message = "Đã bỏ \"{$product->name}\" khỏi danh sách yêu thích.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'in_wishlist' => false,
                'count' => Wishlist::where('user_id', Auth::id())->count(),
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
