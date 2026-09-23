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
    public function index(Request $request): View
    {
        $products = $request->user()
            ->wishlistProducts()
            ->with(['category', 'primaryImage', 'variants'])
            ->latest('wishlists.created_at')
            ->paginate(12);

        return view('wishlist.index', compact('products'));
    }

    public function toggle(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $isWishlisted = false;
            $message = 'Đã xóa "' . $product->name . '" khỏi danh sách yêu thích.';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            $isWishlisted = true;
            $message = 'Đã lưu "' . $product->name . '" vào danh sách yêu thích!';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_wishlisted' => $isWishlisted,
                'message' => $message,
                'wishlist_count' => $user->wishlists()->count(),
            ]);
        }

        return back()->with('success', $message);
    }
}
