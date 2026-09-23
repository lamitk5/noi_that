<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ? $this->route('product')->id : null;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            // Admin forms may send either price or base_price
            'price' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['required_without:price', 'nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'material' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'variants' => ['sometimes', 'array', 'min:1'],
            'variants.*.size' => ['nullable', 'string', 'max:100'],
            'variants.*.color' => ['required', 'string', 'max:100'],
            'variants.*.material' => ['nullable', 'string', 'max:100'],
            'variants.*.sku' => ['nullable', 'string', 'max:50'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục cho sản phẩm.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'name.required' => 'Tên sản phẩm không được để trống.',
            'price.required' => 'Giá sản phẩm không được để trống.',
            'price.min' => 'Giá sản phẩm phải lớn hơn hoặc bằng 0.',
            'base_price.required' => 'Giá sản phẩm không được để trống.',
            'variants.*.color.required' => 'Vui lòng nhập tên màu gỗ cho biến thể.',
            'variants.*.price.required' => 'Vui lòng nhập giá cho biến thể.',
            'variants.*.stock.required' => 'Vui lòng nhập tồn kho cho biến thể.',
            'variants.min' => 'Sản phẩm cần ít nhất 1 biến thể (kích thước / màu gỗ).',
        ];
    }
}
