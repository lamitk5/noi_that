<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products with filters.
     */
    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['category', 'primaryImage'])
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->latest();

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($catId = $request->query('category_id')) {
            $query->where('category_id', $catId);
        }

        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_active', $request->query('status') === 'active');
        }

        if ($stockStatus = $request->query('stock_status')) {
            $threshold = Product::lowStockThreshold();
            if ($stockStatus === 'out_of_stock') {
                $query->having('variants_sum_stock', '<=', 0);
            } elseif ($stockStatus === 'low_stock') {
                $query->having('variants_sum_stock', '>', 0)
                    ->having('variants_sum_stock', '<=', $threshold);
            } elseif ($stockStatus === 'in_stock') {
                $query->having('variants_sum_stock', '>', $threshold);
            }
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'q' => $request->query('q', ''),
                'category_id' => $request->query('category_id', ''),
                'status' => $request->query('status', ''),
                'stock_status' => $request->query('stock_status', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product with optional initial variant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            // Initial variant fields
            'variant_color' => ['nullable', 'string', 'max:100'],
            'variant_size' => ['nullable', 'string', 'max:100'],
            'variant_material' => ['nullable', 'string', 'max:100'],
            'variant_stock' => ['nullable', 'integer', 'min:0'],
            'variant_sku' => ['nullable', 'string', 'max:100', 'unique:product_variants,sku'],
        ], [
            'category_id.required' => 'Vui lòng chọn danh mục sản phẩm.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'slug.unique' => 'Đường dẫn (slug) sản phẩm này đã tồn tại.',
            'sku.unique' => 'Mã SKU sản phẩm này đã tồn tại.',
            'base_price.required' => 'Vui lòng nhập giá gốc sản phẩm.',
            'base_price.numeric' => 'Giá gốc phải là chữ số.',
            'variant_sku.unique' => 'Mã SKU biến thể đã tồn tại.',
        ]);

        $baseSlug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        if (empty($baseSlug)) {
            $baseSlug = 'san-pham-' . uniqid();
        }

        $slug = $baseSlug;
        $count = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        $sku = ! empty($validated['sku'])
            ? strtoupper(trim($validated['sku']))
            : 'MA-' . strtoupper(Str::random(6));

        while (Product::where('sku', $sku)->exists()) {
            $sku = 'MA-' . strtoupper(Str::random(6));
        }

        $product = DB::transaction(function () use ($validated, $slug, $sku, $request) {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => trim($validated['name']),
                'slug' => $slug,
                'sku' => $sku,
                'base_price' => $validated['base_price'],
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            ]);

            // Create initial base variant
            $variantSku = ! empty($validated['variant_sku'])
                ? strtoupper(trim($validated['variant_sku']))
                : $sku . '-01';

            while (ProductVariant::where('sku', $variantSku)->exists()) {
                $variantSku = $sku . '-' . rand(10, 99);
            }

            $product->variants()->create([
                'color' => $validated['variant_color'] ?? null,
                'size' => $validated['variant_size'] ?? null,
                'material' => $validated['variant_material'] ?? null,
                'price' => $validated['base_price'],
                'stock' => (int) ($validated['variant_stock'] ?? 0),
                'sku' => $variantSku,
            ]);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã thêm sản phẩm mới thành công. Hãy bổ sung hình ảnh hoặc các biến thể nếu cần.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $product->load(['category', 'variants.orderItems', 'images']);
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'base_price' => ['required', 'numeric', 'min:0'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'slug.unique' => 'Đường dẫn (slug) sản phẩm này đã tồn tại.',
            'sku.unique' => 'Mã SKU sản phẩm này đã tồn tại.',
            'base_price.required' => 'Vui lòng nhập giá gốc sản phẩm.',
        ]);

        $slug = $product->slug;
        if (! empty($validated['slug']) && $validated['slug'] !== $product->slug) {
            $slug = Str::slug($validated['slug']);
        }

        $sku = ! empty($validated['sku']) ? strtoupper(trim($validated['sku'])) : $product->sku;

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => trim($validated['name']),
            'slug' => $slug,
            'sku' => $sku,
            'base_price' => $validated['base_price'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Đã cập nhật thông tin sản phẩm thành công.');
    }

    /**
     * Toggle active status of the product.
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'is_active' => ! $product->is_active,
        ]);

        $statusText = $product->is_active ? 'kích hoạt' : 'ẩn';
        return back()->with('success', "Đã {$statusText} sản phẩm thành công.");
    }

    /**
     * Safe deactivate product instead of hard delete.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã ngưng hoạt động sản phẩm (chuyển sang trạng thái Ẩn) để bảo toàn lịch sử đơn hàng.');
    }
}
