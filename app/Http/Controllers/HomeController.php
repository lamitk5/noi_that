<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Homepage display.
     */
    public function index(Request $request): View|JsonResponse
    {
        $featuredCategories = Category::active()
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->take(6)
            ->get();

        $featuredProducts = Product::active()
            ->featured()
            ->with(['category', 'images'])
            ->take(8)
            ->get();

        $newArrivals = Product::active()
            ->with(['category', 'images'])
            ->latest()
            ->take(8)
            ->get();

        $saleProducts = Product::active()
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->with(['category', 'images'])
            ->take(6)
            ->get();

        $data = compact('featuredCategories', 'featuredProducts', 'newArrivals', 'saleProducts');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('home', $data);
    }
}
