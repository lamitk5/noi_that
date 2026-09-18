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
        $data = $request->validated();
        $price = $data['price'] ?? 0;
        $stock = $data['stock_quantity'] ?? 0;
        $data['base_price'] = $price;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        $data['sku'] = $data['sku'] ?? 'FURN-' . strtoupper(Str::random(8));
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_active'] = $request->boolean('is_active', true);

        $product = Product::create($data);

        // Create default variant for frontend catalog, cart, and checkout compatibility
        $product->variants()->create([
            'sku' => $product->sku . '-DEF',
            'color' => $data['color'] ?? 'Tiêu chuẩn',
            'material' => $data['material'] ?? 'Gỗ tự nhiên',
            'size' => $data['dimensions'] ?? 'Tiêu chuẩn',
            'price' => $price,
            'stock' => $stock,
        ]);

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
                'data' => $product->load(['images', 'variants']),
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
        if (isset($data['price'])) {
            $data['base_price'] = $data['price'];
        }
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_active'] = $request->boolean('is_active', true);

        $product->update($data);

        // Update default variant or create if not exists
        $firstVariant = $product->variants()->first();
        if ($firstVariant) {
            $variantUpdates = [];
            if (isset($data['price'])) $variantUpdates['price'] = $data['price'];
            if (isset($data['stock_quantity'])) $variantUpdates['stock'] = $data['stock_quantity'];
            if (isset($data['color'])) $variantUpdates['color'] = $data['color'];
            if (isset($data['material'])) $variantUpdates['material'] = $data['material'];
            if (isset($data['dimensions'])) $variantUpdates['size'] = $data['dimensions'];
            $firstVariant->update($variantUpdates);
        } else {
            $product->variants()->create([
                'sku' => $product->sku . '-DEF',
                'color' => $data['color'] ?? 'Tiêu chuẩn',
                'material' => $data['material'] ?? 'Gỗ tự nhiên',
                'size' => $data['dimensions'] ?? 'Tiêu chuẩn',
                'price' => $data['price'] ?? $product->base_price,
                'stock' => $data['stock_quantity'] ?? 0,
            ]);
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
}
