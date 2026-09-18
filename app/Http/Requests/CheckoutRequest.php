<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s().-]+$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer'],
            'checkout_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập họ và tên người nhận.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại giao hàng.',
            'customer_phone.regex' => 'Số điện thoại không đúng định dạng.',
            'customer_email.email' => 'Địa chỉ email không hợp lệ.',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ nhận hàng chi tiết.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'checkout_token.required' => 'Phiên thanh toán không hợp lệ. Vui lòng tải lại trang.',
        ];
    }
}
