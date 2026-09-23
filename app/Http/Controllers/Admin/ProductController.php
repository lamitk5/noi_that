<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Product::with(['category', 'images']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->with(['category', 'images', 'variants'])->latest()->paginate(15)->withQueryString();
        $categories = Category::active()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        }

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::active()->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(ProductRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        $data['base_price'] = $data['base_price'] ?? $data['price'] ?? 0;
        unset($data['price'], $data['stock_quantity']);
        $data['sale_price'] = $data['sale_price'] !== null && $data['sale_price'] !== ''
            ? $data['sale_price']
            : null;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        $data['sku'] = $data['sku'] ?? 'FURN-' . strtoupper(Str::random(8));
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_active'] = $request->boolean('is_active', true);

        $product = Product::create($data);
        $this->syncVariants($product, $variants);

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            $isFirst = true;
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $isFirst,
                ]);
                $isFirst = false;
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm nội thất thành công!',
                'data' => $product->load('images'),
            ], 201);
        }

        return redirect()->route('admin.products.index')->with('success', 'Tạo sản phẩm mới thành công!');
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->get();
        $product->load(['images', 'category', 'variants']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $variants = $data['variants'] ?? null;
        unset($data['variants']);

        $data['base_price'] = $data['base_price'] ?? $data['price'] ?? $product->base_price;
        unset($data['price'], $data['stock_quantity']);
        $data['sale_price'] = $data['sale_price'] !== null && $data['sale_price'] !== ''
            ? $data['sale_price']
            : null;
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_active'] = $request->boolean('is_active', true);

        $product->update($data);

        if (is_array($variants)) {
            $this->syncVariants($product, $variants);
        }

        // Handle uploaded new images
        if ($request->hasFile('images')) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            $isFirst = !$hasPrimary;

            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $isFirst,
                ]);
                $isFirst = false;
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công!',
                'data' => $product->load('images'),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        foreach ($product->images as $image) {
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $product->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sản phẩm thành công.',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm thành công.');
    }

    public function setPrimaryImage(ProductImage $image): RedirectResponse|JsonResponse
    {
        $productId = $image->product_id;

        // Reset all images of this product
        ProductImage::where('product_id', $productId)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Đã đặt ảnh đại diện cho sản phẩm.');
    }

    public function deleteImage(ProductImage $image): RedirectResponse|JsonResponse
    {
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $productId = $image->product_id;
        $wasPrimary = $image->is_primary;
        $image->delete();

        // If deleted image was primary, set the next one as primary
        if ($wasPrimary) {
            $nextImage = ProductImage::where('product_id', $productId)->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Đã xóa ảnh sản phẩm.');
    }

    /**
     * Replace product size / wood-color variants with the submitted set.
     * Matching is by color + size (both nullable size allowed as single size).
     */
    protected function syncVariants(Product $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $variant) {
            $color = trim((string) ($variant['color'] ?? ''));
            $size = trim((string) ($variant['size'] ?? ''));
            if ($color === '') {
                continue;
            }

            $record = $product->variants()->updateOrCreate(
                [
                    'color' => $color,
                    'size' => $size ?: null,
                ],
                [
                    'material' => $variant['material'] ?? null,
                    'sku' => $variant['sku'] ?: null,
                    'price' => $variant['price'],
                    'stock' => (int) $variant['stock'],
                ]
            );

            $keepIds[] = $record->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();

        // Keep product base_price aligned with cheapest variant for catalog display
        $minVariantPrice = $product->variants()->min('price');
        if ($minVariantPrice !== null) {
            $product->updateQuietly(['base_price' => $minVariantPrice]);
        }
    }
}
