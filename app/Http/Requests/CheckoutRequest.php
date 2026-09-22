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
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer,vnpay,momo'],
            'checkout_token' => ['required', 'string'],
            'save_address' => ['nullable', 'boolean'],
            'province_id' => ['nullable', 'integer', 'min:1'],
            'to_district_id' => ['nullable', 'integer', 'min:1'],
            'to_ward_code' => ['nullable', 'string', 'max:20'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'shipping_fee' => ['nullable', 'numeric'],
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
