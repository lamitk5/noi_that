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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Product::with(['category', 'images', 'variants']);

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

        $products = $query->latest()->paginate(15)->withQueryString();
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
        try {
            $data = $request->validated();
            $price = (float) ($data['price'] ?? 0);
            $stock = (int) ($data['stock_quantity'] ?? 0);
            $salePrice = $this->normalizeSalePrice($data['sale_price'] ?? null, $price);

            $payload = [
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::random(5),
                'sku' => $data['sku'] ?? ('FURN-'.strtoupper(Str::random(8))),
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'base_price' => $price,
                'sale_price' => $salePrice,
                'material' => $data['material'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'color' => $data['color'] ?? null,
                'is_featured' => $request->boolean('is_featured', false),
                'is_active' => $request->boolean('is_active', true),
            ];

            $product = Product::create($payload);

            $product->variants()->create([
                'sku' => $product->sku.'-DEF',
                'color' => $data['color'] ?? 'Tiêu chuẩn',
                'material' => $data['material'] ?? 'Gỗ tự nhiên',
                'size' => $data['dimensions'] ?? 'Tiêu chuẩn',
                'price' => $price,
                'stock' => $stock,
            ]);

            $this->attachUploadedImages($product, $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lưu sản phẩm thành công!',
                    'data' => $product->load(['images', 'variants']),
                ], 201);
            }

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Lưu sản phẩm thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Product store validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['images', '_token']),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Product store failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Không thể lưu sản phẩm: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->withInput()->with('error', $message);
        }
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->get();
        $product->load(['images', 'category', 'variants']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();

            $price = array_key_exists('price', $data)
                ? (float) $data['price']
                : (float) $product->base_price;

            $salePrice = array_key_exists('sale_price', $data)
                ? $this->normalizeSalePrice($data['sale_price'], $price)
                : $product->sale_price;

            $stock = array_key_exists('stock_quantity', $data)
                ? (int) $data['stock_quantity']
                : $product->totalStock();

            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'name' => $data['name'] ?? $product->name,
                'short_description' => $data['short_description'] ?? $product->short_description,
                'description' => $data['description'] ?? $product->description,
                'base_price' => $price,
                'sale_price' => $salePrice,
                'material' => $data['material'] ?? $product->material,
                'dimensions' => $data['dimensions'] ?? $product->dimensions,
                'color' => $data['color'] ?? $product->color,
                'sku' => $data['sku'] ?? $product->sku,
                'is_featured' => $request->boolean('is_featured', false),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $firstVariant = $product->variants()->first();
            if ($firstVariant) {
                $firstVariant->update([
                    'price' => $price,
                    'stock' => $stock,
                    'color' => $data['color'] ?? $firstVariant->color,
                    'material' => $data['material'] ?? $firstVariant->material,
                    'size' => $data['dimensions'] ?? $firstVariant->size,
                ]);
            } else {
                $product->variants()->create([
                    'sku' => $product->sku.'-DEF',
                    'color' => $data['color'] ?? 'Tiêu chuẩn',
                    'material' => $data['material'] ?? 'Gỗ tự nhiên',
                    'size' => $data['dimensions'] ?? 'Tiêu chuẩn',
                    'price' => $price,
                    'stock' => $stock,
                ]);
            }

            // Only process when admin actually uploaded valid files — keep existing images otherwise
            $this->attachUploadedImages($product, $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật sản phẩm thành công!',
                    'data' => $product->load(['images', 'variants']),
                ]);
            }

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', 'Cập nhật sản phẩm thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Product update validation failed', [
                'product_id' => $product->id,
                'errors' => $e->errors(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Product update failed', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Cập nhật sản phẩm thất bại: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->withInput()->with('error', $message);
        }
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        try {
            foreach ($product->images as $image) {
                $this->deletePictureFile($image->image_path);
            }

            $product->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa sản phẩm thành công.',
                ]);
            }

            return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm thành công.');
        } catch (\Throwable $e) {
            Log::error('Product destroy failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            $message = 'Không thể xóa sản phẩm: '.$e->getMessage();

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message);
        }
    }

    public function setPrimaryImage(ProductImage $image): RedirectResponse|JsonResponse
    {
        $productId = $image->product_id;

        ProductImage::where('product_id', $productId)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Đã đặt ảnh đại diện cho sản phẩm.');
    }

    public function deleteImage(ProductImage $image): RedirectResponse|JsonResponse
    {
        try {
            $this->deletePictureFile($image->image_path);

            $productId = $image->product_id;
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                $nextImage = ProductImage::where('product_id', $productId)->first();
                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }

            return back()->with('success', 'Đã xóa ảnh sản phẩm.');
        } catch (\Throwable $e) {
            Log::error('Delete product image failed', ['image_id' => $image->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Không thể xóa ảnh: '.$e->getMessage());
        }
    }

    /**
     * Attach only valid uploaded files. Empty/null file inputs are ignored
     * so existing images stay intact when admin doesn't pick new files.
     */
    private function attachUploadedImages(Product $product, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $files = array_values(array_filter(
            (array) $request->file('images'),
            static fn ($file) => $file !== null && $file->isValid()
        ));

        if ($files === []) {
            return;
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $isFirst = ! $hasPrimary;
        $sort = (int) $product->images()->max('sort_order') + 1;

        foreach ($files as $file) {
            $path = $this->storePicture($file);
            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $isFirst,
                'sort_order' => $sort++,
            ]);
            $isFirst = false;
        }
    }

    private function normalizeSalePrice(mixed $salePrice, float $basePrice): ?float
    {
        if ($salePrice === null || $salePrice === '') {
            return null;
        }

        $value = (float) $salePrice;
        if ($value <= 0 || $value >= $basePrice) {
            return null;
        }

        return $value;
    }

    /**
     * Save uploaded image into storage/picture (master folder).
     */
    private function storePicture(\Illuminate\Http\UploadedFile $file): string
    {
        $dir = storage_path('picture');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $base = Str::slug($original) ?: 'product';
        $filename = $base.'-'.Str::lower(Str::random(6)).'.'.$extension;

        while (is_file($dir.DIRECTORY_SEPARATOR.$filename)) {
            $filename = $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
        }

        $file->move($dir, $filename);

        return 'picture/'.$filename;
    }

    private function deletePictureFile(?string $imagePath): void
    {
        if (! $imagePath) {
            return;
        }

        if (Str::startsWith($imagePath, 'picture/')) {
            $full = storage_path('picture/'.basename($imagePath));
            if (is_file($full)) {
                @unlink($full);
            }

            return;
        }

        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }
}
