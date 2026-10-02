<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OtpSender
{
    public function __construct(protected SmsService $sms)
    {
    }

    /**
     * Codes may only be echoed back to the browser on a local dev machine.
     */
    public static function canRevealCode(): bool
    {
        return app()->isLocal();
    }

    public static function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * @param  'verify'|'reset'  $purpose
     */
    public function sendEmail(string $email, string $code, string $purpose = 'verify'): bool
    {
        $subject = $purpose === 'reset'
            ? "[Mộc An] Mã đặt lại mật khẩu: {$code}"
            : "[Mộc An] Mã xác thực tài khoản của bạn: {$code}";

        try {
            $html = view('emails.otp', ['code' => $code, 'purpose' => $purpose])->render();
            Mail::html($html, fn ($message) => $message->to($email)->subject($subject));

            return true;
        } catch (Throwable $e) {
            Log::warning("Send OTP email ({$purpose}) failed: ".$e->getMessage());

            return false;
        }
    }

    public function sendSms(string $phone, string $code): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        $result = $this->sms->sendOtp($phone, $code);
        if (! ($result['success'] ?? false)) {
            Log::warning('Send OTP SMS failed: '.($result['message'] ?? 'Unknown'));
        }

        return (bool) ($result['success'] ?? false);
    }

    public function send(string $type, string $target, string $code, string $purpose = 'verify'): bool
    {
        return $type === 'email'
            ? $this->sendEmail($target, $code, $purpose)
            : $this->sendSms($target, $code);
    }
}
