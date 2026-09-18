<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MomoService
{
    /**
     * Create MoMo payment and retrieve payUrl.
     */
    public function createPayment(Order $order, PaymentTransaction $transaction): string
    {
        $endpoint = config('services.momo.url');
        $partnerCode = config('services.momo.partner_code');
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');

        if (empty($partnerCode) || empty($accessKey) || empty($secretKey)) {
            throw new RuntimeException('Cổng thanh toán MoMo hiện chưa được cấu hình.');
        }

        $orderId = $transaction->provider_reference;
        $orderInfo = "Thanh toan don hang #{$order->order_code}";
        $amount = (string) (int) round((float) $order->total_price);
        $ipnUrl = route('payments.momo.ipn');
        $redirectUrl = route('payments.momo.return');
        $extraData = '';
        $requestId = $transaction->request_id ?: (string) Str::uuid();
        $requestType = 'captureWallet';

        if (! $transaction->request_id) {
            $transaction->update(['request_id' => $requestId]);
        }

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Mộc An',
            'storeId' => 'MocAnStore',
            'requestId' => $requestId,
            'amount' => (int) $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];

        try {
            $response = Http::timeout(30)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new RuntimeException('Không thể kết nối đến cổng thanh toán MoMo: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            throw new RuntimeException('Cổng thanh toán MoMo phản hồi lỗi HTTP ' . $response->status());
        }

        $data = $response->json();
        if (($data['resultCode'] ?? null) !== 0 || empty($data['payUrl'])) {
            $message = $data['message'] ?? 'Khởi tạo thanh toán MoMo thất bại.';
            throw new RuntimeException($message);
        }

        return $data['payUrl'];
    }

    /**
     * Verify MoMo IPN/Callback signature using HMAC-SHA256.
     */
    public function verifyCallbackSignature(array $data): bool
    {
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');

        if (empty($accessKey) || empty($secretKey) || empty($data['signature'])) {
            return false;
        }

        $rawHash = "accessKey={$accessKey}&amount=" . ($data['amount'] ?? '') .
            "&extraData=" . ($data['extraData'] ?? '') .
            "&message=" . ($data['message'] ?? '') .
            "&orderId=" . ($data['orderId'] ?? '') .
            "&orderInfo=" . ($data['orderInfo'] ?? '') .
            "&orderType=" . ($data['orderType'] ?? '') .
            "&partnerCode=" . ($data['partnerCode'] ?? '') .
            "&payType=" . ($data['payType'] ?? '') .
            "&requestId=" . ($data['requestId'] ?? '') .
            "&responseTime=" . ($data['responseTime'] ?? '') .
            "&resultCode=" . ($data['resultCode'] ?? '') .
            "&transId=" . ($data['transId'] ?? '');

        $expectedSignature = hash_hmac('sha256', $rawHash, $secretKey);

        return hash_equals($expectedSignature, (string) $data['signature']);
    }
}
