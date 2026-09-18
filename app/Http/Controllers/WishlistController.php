<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Display the authenticated user's wishlist.
     */
    public function index(Request $request): View
    {
        $products = $request->user()
            ->wishlistProducts()
            ->with(['category', 'primaryImage', 'variants'])
            ->latest('wishlists.created_at')
            ->paginate(12);

        return view('account.wishlist', compact('products'));
    }

    /**
     * Toggle a product in user's wishlist.
     */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
            $message = 'Đã xóa sản phẩm khỏi danh sách yêu thích.';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            $favorited = true;
            $message = 'Đã thêm sản phẩm vào danh sách yêu thích.';
        }

        $count = $user->wishlists()->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'favorited' => $favorited,
                'message' => $message,
                'count' => $count,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove a product from wishlist.
     */
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return redirect()->route('account.wishlist')->with('success', 'Đã xóa sản phẩm khỏi danh sách yêu thích.');
    }
}
