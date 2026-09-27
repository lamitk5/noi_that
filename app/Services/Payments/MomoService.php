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

        if (empty($partnerCode) || empty($accessKey) || empty($secretKey) || empty($endpoint)) {
            throw new RuntimeException('Cổng thanh toán MoMo hiện chưa được cấu hình.');
        }

        // Dev mock mode: bật MOMO_MOCK=true trong .env để giả lập thanh toán thành công
        if (config('services.momo.mock', false)) {
            return route('payments.momo.mock', [
                'orderCode'   => $order->order_code,
                'reference'   => $transaction->provider_reference,
            ]);
        }

        $orderId = $transaction->provider_reference;
        // Keep orderInfo ASCII-safe: '#' and unicode can break MoMo signature rebuild
        $orderInfo = 'Thanh toan don hang '.$order->order_code;
        $amount = (string) (int) round((float) $order->total_price);
        $ipnUrl = route('payments.momo.ipn');
        $redirectUrl = route('payments.momo.return');
        $extraData = '';
        // MoMo expects alphanumeric requestId (no hyphens like UUID)
        $requestId = $transaction->request_id ?: $this->generateRequestId();
        $requestType = 'captureWallet';

        if (! $transaction->request_id) {
            $transaction->update(['request_id' => $requestId]);
        }

        $signature = $this->createSignature([
            'accessKey' => $accessKey,
            'amount' => $amount,
            'extraData' => $extraData,
            'ipnUrl' => $ipnUrl,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'partnerCode' => $partnerCode,
            'redirectUrl' => $redirectUrl,
            'requestId' => $requestId,
            'requestType' => $requestType,
        ], $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Moc An',
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
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            throw new RuntimeException('Không thể kết nối đến cổng thanh toán MoMo: '.$e->getMessage());
        }

        $data = $response->json();

        $resultCode = $data['resultCode'] ?? null;
        $momoMessages = [
            11007 => 'Chữ ký MoMo không hợp lệ – kiểm tra lại MOMO_ACCESS_KEY / MOMO_SECRET_KEY trong .env',
            9      => 'Tài khoản MoMo không đủ số dư',
            1006   => 'Giao dịch MoMo bị từ chối',
            1005   => 'URL gọi API MoMo không đúng',
        ];

        if (! $response->successful() || $resultCode !== 0 || empty($data['payUrl'])) {
            $message = $momoMessages[$resultCode]
                ?? ($data['message'] ?? ('Khởi tạo thanh toán MoMo thất bại (HTTP '.$response->status().' / code '.$resultCode.').'));
            throw new RuntimeException($message);
        }

        return $data['payUrl'];
    }

    /**
     * MoMo create-payment HMAC-SHA256 (fields in fixed order).
     */
    public function createSignature(array $fields, string $secretKey): string
    {
        $rawHash = 'accessKey='.$fields['accessKey']
            .'&amount='.$fields['amount']
            .'&extraData='.$fields['extraData']
            .'&ipnUrl='.$fields['ipnUrl']
            .'&orderId='.$fields['orderId']
            .'&orderInfo='.$fields['orderInfo']
            .'&partnerCode='.$fields['partnerCode']
            .'&redirectUrl='.$fields['redirectUrl']
            .'&requestId='.$fields['requestId']
            .'&requestType='.$fields['requestType'];

        return hash_hmac('sha256', $rawHash, $secretKey);
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

        $rawHash = 'accessKey='.$accessKey
            .'&amount='.($data['amount'] ?? '')
            .'&extraData='.($data['extraData'] ?? '')
            .'&message='.($data['message'] ?? '')
            .'&orderId='.($data['orderId'] ?? '')
            .'&orderInfo='.($data['orderInfo'] ?? '')
            .'&orderType='.($data['orderType'] ?? '')
            .'&partnerCode='.($data['partnerCode'] ?? '')
            .'&payType='.($data['payType'] ?? '')
            .'&requestId='.($data['requestId'] ?? '')
            .'&responseTime='.($data['responseTime'] ?? '')
            .'&resultCode='.($data['resultCode'] ?? '')
            .'&transId='.($data['transId'] ?? '');

        $expectedSignature = hash_hmac('sha256', $rawHash, $secretKey);

        return hash_equals($expectedSignature, (string) $data['signature']);
    }

    protected function generateRequestId(): string
    {
        return 'REQ'.strtoupper(Str::random(20));
    }
}
