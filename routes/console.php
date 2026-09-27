<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payment:seed-test-data', function () {
    // 1. Pending VNPAY order
    $vnpOrder = \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-VNPAY-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn VNPAY',
            'customer_email' => 'vnpay@example.test',
            'customer_phone' => '0912345678',
            'shipping_address' => '123 Đường Test, Quận 1, TP.HCM',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 1000000.0,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]
    );

    \App\Models\PaymentTransaction::updateOrCreate(
        ['provider_reference' => 'VNP_TEST_TXN_01'],
        [
            'order_id' => $vnpOrder->id,
            'provider' => 'vnpay',
            'amount' => 1000000.0,
            'status' => 'pending',
        ]
    );

    // 2. Pending MoMo order
    $momoOrder = \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-MOMO-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn MoMo',
            'customer_email' => 'momo@example.test',
            'customer_phone' => '0987654321',
            'shipping_address' => '456 Đường Test, Quận 3, TP.HCM',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 500000.0,
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]
    );

    \App\Models\PaymentTransaction::updateOrCreate(
        ['provider_reference' => 'MOMO_TEST_ORD_01'],
        [
            'order_id' => $momoOrder->id,
            'provider' => 'momo',
            'request_id' => 'REQ_TEST_MOMO_01',
            'amount' => 500000.0,
            'status' => 'pending',
        ]
    );

    // 3. Already paid order
    $paidOrder = \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-PAID-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn Paid',
            'customer_email' => 'paid@example.test',
            'customer_phone' => '0901234567',
            'shipping_address' => '789 Đường Test, Cầu Giấy, Hà Nội',
            'shipping_fee' => 0.0,
            'discount_amount' => 0.0,
            'total_price' => 2000000.0,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]
    );

    \App\Models\PaymentTransaction::updateOrCreate(
        ['provider_reference' => 'VNP_TEST_PAID_01'],
        [
            'order_id' => $paidOrder->id,
            'provider' => 'vnpay',
            'amount' => 2000000.0,
            'status' => 'success',
            'response_code' => '00',
            'paid_at' => now(),
        ]
    );

    // 4. Cancelled orders
    $cancelOrder = \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-CANCEL-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn Cancel',
            'customer_email' => 'cancel@example.test',
            'customer_phone' => '0938123456',
            'shipping_address' => '101 Đường Test, Đà Nẵng',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 1500000.0,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'order_status' => 'cancelled',
        ]
    );

    \App\Models\PaymentTransaction::updateOrCreate(
        ['provider_reference' => 'VNP_TEST_CANCEL_01'],
        [
            'order_id' => $cancelOrder->id,
            'provider' => 'vnpay',
            'amount' => 1500000.0,
            'status' => 'pending',
        ]
    );

    $momoCancelOrder = \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-MOMO-CANCEL-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn MoMo Cancel',
            'customer_email' => 'momocancel@example.test',
            'customer_phone' => '0938123457',
            'shipping_address' => '102 Đường Test, Đà Nẵng',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 500000.0,
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'order_status' => 'cancelled',
        ]
    );

    \App\Models\PaymentTransaction::updateOrCreate(
        ['provider_reference' => 'MOMO_TEST_CANCEL_01'],
        [
            'order_id' => $momoCancelOrder->id,
            'provider' => 'momo',
            'request_id' => 'REQ_TEST_MOMO_CANCEL_01',
            'amount' => 500000.0,
            'status' => 'pending',
        ]
    );

    // 5. COD order (mismatched method)
    \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-COD-01'],
        [
            'user_id' => null,
            'customer_name' => 'Nguyễn Văn COD',
            'customer_email' => 'cod@example.test',
            'customer_phone' => '0979123456',
            'shipping_address' => '202 Đường Test, Hải Phòng',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 800000.0,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]
    );

    // 6. User restricted order
    \App\Models\Order::updateOrCreate(
        ['order_code' => 'ORD-TEST-USER-01'],
        [
            'user_id' => 1, // owned by admin user (id: 1), guest will be rejected with 403
            'customer_name' => 'Nguyễn Văn User Private',
            'customer_email' => 'user1@example.test',
            'customer_phone' => '0868123456',
            'shipping_address' => '303 Đường Test, Cần Thơ',
            'shipping_fee' => 30000.0,
            'discount_amount' => 0.0,
            'total_price' => 3000000.0,
            'payment_method' => 'vnpay',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]
    );

    $this->info('Payment test data seeded successfully.');
})->purpose('Seed test orders and transactions for E2E payment tests');
