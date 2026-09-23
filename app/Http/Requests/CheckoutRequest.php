<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cod,banking,vnpay,momo'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'province_id' => ['nullable', 'integer'],
            'province_name' => ['nullable', 'string', 'max:100'],
            'district_id' => ['nullable', 'integer'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'ward_code' => ['nullable', 'string', 'max:20'],
            'ward_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập họ tên người nhận hàng.',
            'customer_email.required' => 'Vui lòng nhập địa chỉ email.',
            'customer_email.email' => 'Địa chỉ email không đúng định dạng.',
            'customer_phone.required' => 'Vui lòng cung cấp số điện thoại nhận hàng.',
            'shipping_address.required' => 'Vui lòng cung cấp địa chỉ giao hàng cụ thể.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }
}
