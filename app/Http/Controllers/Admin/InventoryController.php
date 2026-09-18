<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory variants with stock status.
     */
    public function index(Request $request): View
    {
        $threshold = Product::lowStockThreshold();

        $query = ProductVariant::query()
            ->with(['product.category'])
            ->latest();

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('sku', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($p) use ($q) {
                        $p->where('name', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%");
                    });
            });
        }

        $filter = $request->query('filter', 'all');
        if ($filter === 'low_stock') {
            $query->where('stock', '>', 0)->where('stock', '<=', $threshold);
        } elseif ($filter === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        } elseif ($filter === 'in_stock') {
            $query->where('stock', '>', $threshold);
        }

        $variants = $query->paginate(20)->withQueryString();

        // Inventory summary counts
        $totalVariants = ProductVariant::count();
        $totalStock = (int) ProductVariant::sum('stock');
        $lowStockCount = ProductVariant::where('stock', '>', 0)->where('stock', '<=', $threshold)->count();
        $outOfStockCount = ProductVariant::where('stock', '<=', 0)->count();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'threshold' => $threshold,
            'summary' => [
                'total_variants' => $totalVariants,
                'total_stock' => $totalStock,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
            'filters' => [
                'q' => $request->query('q', ''),
                'filter' => $filter,
            ],
        ]);
    }

    /**
     * Update stock level of a specific variant with lockForUpdate.
     */
    public function update(Request $request, $variant): RedirectResponse
    {
        $validated = $request->validate([
            'stock' => ['required', 'integer', 'min:0'],
        ], [
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được âm.',
        ]);

        $variantModel = ProductVariant::findOrFail($variant);

        DB::transaction(function () use ($variantModel, $validated) {
            $lockedVariant = ProductVariant::where('id', $variantModel->id)->lockForUpdate()->firstOrFail();
            $lockedVariant->update([
                'stock' => $validated['stock'],
            ]);
        });

        return back()->with('success', "Đã cập nhật tồn kho SKU {$variantModel->sku} thành công ({$validated['stock']}).");
    }
}
