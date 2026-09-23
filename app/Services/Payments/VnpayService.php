<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use RuntimeException;

class VnpayService
{
    public function buildPaymentUrl(Order $order, PaymentTransaction $transaction, ?string $ipAddress = null): string
    {
        $vnpUrl = config('services.vnpay.url');
        $tmnCode = config('services.vnpay.tmn_code');
        $hashSecret = config('services.vnpay.hash_secret');

        if (empty($tmnCode) || empty($hashSecret) || empty($vnpUrl)) {
            throw new RuntimeException('Cổng thanh toán VNPAY hiện chưa được cấu hình.');
        }

        $amount = (int) round((float) $order->total_price * 100);

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amount,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $ipAddress ?: '127.0.0.1',
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => "Thanh toan don hang #{$order->order_code}",
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => route('payments.vnpay.return'),
            'vnp_TxnRef' => $transaction->provider_reference,
            'vnp_ExpireDate' => date('YmdHis', strtotime('+15 minutes')),
        ];

        ksort($inputData);

        $query = '';
        $i = 0;
        $hashData = '';

        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode((string) $value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode((string) $value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode((string) $value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashData, $hashSecret);

        return $vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;
    }

    public function verifySignature(array $inputData): bool
    {
        $hashSecret = config('services.vnpay.hash_secret');
        if (empty($hashSecret) || ! isset($inputData['vnp_SecureHash'])) {
            return false;
        }

        $vnpSecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);

        $i = 0;
        $hashData = '';

        foreach ($inputData as $key => $value) {
            if (str_starts_with($key, 'vnp_')) {
                if ($i === 1) {
                    $hashData .= '&' . urlencode($key) . '=' . urlencode((string) $value);
                } else {
                    $hashData .= urlencode($key) . '=' . urlencode((string) $value);
                    $i = 1;
                }
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        return hash_equals($secureHash, (string) $vnpSecureHash);
    }
}
