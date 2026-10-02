<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    /**
     * Send an OTP code to a phone number using real SMS gateway.
     *
     * @return array{success: bool, message: string}
     */
    public function sendOtp(string $phone, string $code): array
    {
        $provider = strtolower((string) env('SMS_PROVIDER', ''));
        $content = "Ma xac thuc Moc An cua ban la: {$code}. Hieu luc trong 10 phut. Khong chia se ma nay voi ai.";

        // Normalize phone number (0912345678 -> 84912345678)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $e164Phone = '84' . substr($cleanPhone, 1);
        } else {
            $e164Phone = $cleanPhone;
        }

        if (empty($provider)) {
            Log::warning("[SMS REAL] Chưa cấu hình SMS_PROVIDER trong .env. Không thể gửi SMS thật đến {$phone}.");
            return [
                'success' => false,
                'message' => 'Chưa cấu hình dịch vụ SMS trong .env (hỗ trợ: esms, speedsms, twilio).',
            ];
        }

        try {
            return match ($provider) {
                'esms' => $this->sendViaEsms($cleanPhone, $code),
                'speedsms' => $this->sendViaSpeedSms($cleanPhone, $content),
                'twilio' => $this->sendViaTwilio('+' . $e164Phone, $content),
                default => [
                    'success' => false,
                    'message' => "Nhà cung cấp SMS '{$provider}' không được hỗ trợ.",
                ],
            };
        } catch (Throwable $e) {
            Log::error("[SMS ERROR] Gửi tin nhắn đến {$phone} thất bại: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi kết nối cổng SMS: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * eSMS.vn API v4
     */
    protected function sendViaEsms(string $phone, string $code): array
    {
        $apiKey = env('ESMS_API_KEY');
        $secretKey = env('ESMS_SECRET_KEY');
        $brandname = env('ESMS_BRANDNAME', 'Baotrimang');

        if (empty($apiKey) || empty($secretKey)) {
            return ['success' => false, 'message' => 'Thiếu ESMS_API_KEY hoặc ESMS_SECRET_KEY trong .env.'];
        }

        $response = Http::timeout(3)->get('http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_get', [
            'ApiKey' => $apiKey,
            'SecretKey' => $secretKey,
            'Phone' => $phone,
            'Content' => "Ma xac thuc Moc An cua ban la {$code}",
            'SmsType' => '2', // CSKH / OTP
            'Brandname' => $brandname,
        ]);

        $data = $response->json();
        if (($data['CodeResult'] ?? '') == '100') {
            Log::info("[eSMS SUCCESS] Đã gửi OTP {$code} đến {$phone}");
            return ['success' => true, 'message' => 'Đã gửi SMS OTP thành công.'];
        }

        $errMsg = $data['ErrorMessage'] ?? 'Lỗi không xác định từ eSMS';
        Log::error("[eSMS FAILED] {$errMsg}");
        return ['success' => false, 'message' => "eSMS lỗi: {$errMsg}"];
    }

    /**
     * SpeedSMS.vn API
     */
    protected function sendViaSpeedSms(string $phone, string $content): array
    {
        $accessToken = env('SPEEDSMS_ACCESS_TOKEN');
        if (empty($accessToken)) {
            return ['success' => false, 'message' => 'Thiếu SPEEDSMS_ACCESS_TOKEN trong .env.'];
        }

        $sender = env('SPEEDSMS_SENDER', '');
        $response = Http::withBasicAuth($accessToken, 'x')
            ->timeout(10)
            ->post('https://api.speedsms.vn/index.php/sms/send', [
                'to' => [$phone],
                'content' => $content,
                'sms_type' => 2,
                'sender' => $sender,
            ]);

        $data = $response->json();
        if (($data['status'] ?? '') === 'success') {
            Log::info("[SpeedSMS SUCCESS] Đã gửi tin nhắn đến {$phone}");
            return ['success' => true, 'message' => 'Đã gửi SMS OTP thành công.'];
        }

        $errMsg = $data['message'] ?? 'Lỗi không xác định từ SpeedSMS';
        return ['success' => false, 'message' => "SpeedSMS lỗi: {$errMsg}"];
    }

    /**
     * Twilio SMS API
     */
    protected function sendViaTwilio(string $e164Phone, string $content): array
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_FROM');

        if (empty($sid) || empty($token) || empty($from)) {
            return ['success' => false, 'message' => 'Thiếu TWILIO_SID, TWILIO_AUTH_TOKEN hoặc TWILIO_FROM trong .env.'];
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->timeout(10)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $e164Phone,
                'Body' => $content,
            ]);

        if ($response->successful()) {
            Log::info("[Twilio SUCCESS] Đã gửi tin nhắn đến {$e164Phone}");
            return ['success' => true, 'message' => 'Đã gửi SMS OTP thành công.'];
        }

        $err = $response->json()['message'] ?? $response->body();
        return ['success' => false, 'message' => "Twilio lỗi: {$err}"];
    }
}
