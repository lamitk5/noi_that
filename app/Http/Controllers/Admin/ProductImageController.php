<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Store new product image(s).
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'image.required' => 'Vui lòng chọn hình ảnh để tải lên.',
            'image.image' => 'Tập tin tải lên phải là hình ảnh hợp lệ.',
            'image.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, webp.',
            'image.max' => 'Dung lượng ảnh tối đa là 5MB.',
        ]);

        $file = $request->file('image');
        $path = $file->store('products', 'public');

        $isFirst = $product->images()->count() === 0;

        $product->images()->create([
            'image_path' => $path,
            'is_primary' => $isFirst || $request->boolean('is_primary'),
            'sort_order' => $product->images()->count() + 1,
        ]);

        // If marked primary, unset other primaries
        if ($request->boolean('is_primary') && ! $isFirst) {
            $product->images()
                ->where('image_path', '!=', $path)
                ->update(['is_primary' => false]);
        }

        return back()->with('success', 'Tải lên hình ảnh thành công.');
    }

    /**
     * Set the specified image as primary.
     */
    public function setPrimary(Product $product, $image): RedirectResponse
    {
        $imageModel = $product->images()->findOrFail($image);

        $product->images()->update(['is_primary' => false]);
        $imageModel->update(['is_primary' => true]);

        return back()->with('success', 'Đã đặt ảnh làm hình đại diện sản phẩm.');
    }

    /**
     * Remove the specified image.
     */
    public function destroy(Product $product, $image): RedirectResponse
    {
        $imageModel = $product->images()->findOrFail($image);

        $wasPrimary = $imageModel->is_primary;

        // Delete from public storage
        if (Storage::disk('public')->exists($imageModel->image_path)) {
            Storage::disk('public')->delete($imageModel->image_path);
        }

        $imageModel->delete();

        // If the deleted image was primary, make another image primary if exists
        if ($wasPrimary) {
            $next = $product->images()->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Đã xóa hình ảnh thành công.');
    }
}
