<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    /**
     * Store a newly created variant for a product.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
        ], [
            'sku.required' => 'Vui lòng nhập mã SKU của biến thể.',
            'sku.unique' => 'Mã SKU này đã tồn tại trong hệ thống.',
            'price.required' => 'Vui lòng nhập giá biến thể.',
            'price.numeric' => 'Giá biến thể phải là chữ số.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
        ]);

        $product->variants()->create([
            'sku' => strtoupper(trim($validated['sku'])),
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'color' => $validated['color'] ? trim($validated['color']) : null,
            'size' => $validated['size'] ? trim($validated['size']) : null,
            'material' => $validated['material'] ? trim($validated['material']) : null,
        ]);

        return back()->with('success', 'Đã thêm biến thể mới thành công.');
    }

    /**
     * Update the specified variant.
     */
    public function update(Request $request, Product $product, $variant): RedirectResponse
    {
        $variantModel = $product->variants()->findOrFail($variant);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantModel->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
        ], [
            'sku.required' => 'Vui lòng nhập mã SKU.',
            'sku.unique' => 'Mã SKU này đã tồn tại.',
            'price.required' => 'Vui lòng nhập giá.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
        ]);

        $variantModel->update([
            'sku' => strtoupper(trim($validated['sku'])),
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'color' => ! empty($validated['color']) ? trim($validated['color']) : null,
            'size' => ! empty($validated['size']) ? trim($validated['size']) : null,
            'material' => ! empty($validated['material']) ? trim($validated['material']) : null,
        ]);

        return back()->with('success', 'Đã cập nhật biến thể thành công.');
    }

    /**
     * Remove the specified variant safely.
     */
    public function destroy(Product $product, $variant): RedirectResponse
    {
        $variantModel = $product->variants()->findOrFail($variant);

        // Safety check: Cannot delete variant if associated with existing orders
        if ($variantModel->orderItems()->exists()) {
            return back()->with('error', 'Không thể xóa biến thể này vì đã có đơn hàng sử dụng nó.');
        }

        $variantModel->delete();

        return back()->with('success', 'Đã xóa biến thể thành công.');
    }
}
