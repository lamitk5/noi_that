<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Cache::remember('home_categories_v1', 600, function () {
            return Category::query()
                ->where('is_active', true)
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('id')
                ->get();
        });

        $featuredProducts = Cache::remember('home_featured_products_v1', 600, function () {
            return Product::query()
                ->with(['category', 'primaryImage'])
                ->where('is_active', true)
                // id order follows interleaved seeder sequence (rooms/types mixed)
                ->orderBy('id')
                ->limit(8)
                ->get();
        });

        $bestSellers = Cache::remember('home_best_sellers_v3', 600, function () {
            $items = Product::bestSelling(4)
                ->with(['category', 'primaryImage'])
                ->get();

            if ($items->count() < 4) {
                $more = Product::query()
                    ->with(['category', 'primaryImage'])
                    ->where('is_active', true)
                    ->whereNotIn('id', $items->pluck('id'))
                    ->orderBy('id', 'asc')
                    ->limit(4 - $items->count())
                    ->get();
                $items = $items->merge($more);
            }

            $fakedSales = [156, 128, 94, 82];
            foreach ($items as $idx => $item) {
                if (empty($item->total_sold) || $item->total_sold < 5) {
                    $item->total_sold = $fakedSales[$idx % count($fakedSales)];
                }
            }

            return $items;
        });

        $flashSaleProducts = Cache::remember('home_flash_sale_v1', 600, function () {
            return Product::query()
                ->with(['category', 'primaryImage'])
                ->where('is_active', true)
                ->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'base_price')
                ->orderByRaw('(base_price - sale_price) DESC')
                ->limit(4)
                ->get();
        });

        return view('home', compact('categories', 'featuredProducts', 'bestSellers', 'flashSaleProducts'));
    }
}
