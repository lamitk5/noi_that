<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OtpSender
{
    public ?string $lastError = null;

    public function __construct(protected SmsService $sms)
    {
    }

    public static function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    public static function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    public static function codeMatches(string $code, string $stored): bool
    {
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
            return Hash::check($code, $stored);
        }

        return hash_equals($stored, self::hashCode($code));
    }

    /**
     * @param  'verify'|'reset'  $purpose
     */
    public function sendEmail(string $email, string $code, string $purpose = 'verify'): bool
    {
        $subject = $purpose === 'reset'
            ? "[Mộc An] Mã đặt lại mật khẩu: {$code}"
            : "[Mộc An] Mã xác thực tài khoản của bạn: {$code}";

        $from = (string) config('mail.from.address');
        $smtpUser = (string) config('mail.mailers.smtp.username');
        if (($from === '' || str_ends_with($from, '@example.com')) && $smtpUser !== '') {
            $from = $smtpUser;
        }

        $html = view('emails.otp', ['code' => $code, 'purpose' => $purpose])->render();

        // Render's free tier blocks SMTP ports 25, 465 and 587. HTTPS APIs still work.
        $https = $this->sendViaHttps($email, $subject, $html, $from);
        if ($https !== null) {
            return $https;
        }

        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            if (filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password'))) {
                $mailer = 'smtp';
            } else {
                $this->lastError = 'Chưa cấu hình gửi email. Trên Render hãy thêm BREVO_API_KEY (gói miễn phí chặn Gmail SMTP).';
                Log::warning('OTP email skipped because MAIL_MAILER='.config('mail.default'));

                return false;
            }
        }

        try {
            config(['mail.mailers.smtp.timeout' => 8]);
            Mail::mailer($mailer)->html($html, function ($message) use ($email, $subject, $from) {
                $message->to($email)->subject($subject);
                if ($from !== '') {
                    $message->from($from, (string) config('mail.from.name'));
                }
            });

            $this->lastError = null;

            return true;
        } catch (Throwable $e) {
            $this->lastError = $this->smtpFailureMessage($e->getMessage());
            Log::warning("Send OTP email ({$purpose}) failed: ".$e->getMessage());

            return false;
        }
    }

    /**
     * @return bool|null true/false when an HTTPS provider is configured, null when it is not
     */
    private function sendViaHttps(string $email, string $subject, string $html, string $from): ?bool
    {
        $brevoKey = (string) config('services.brevo.key');
        if ($brevoKey !== '') {
            return $this->sendViaBrevo($brevoKey, $email, $subject, $html, $from);
        }

        $resendKey = (string) config('services.resend.key');
        if ($resendKey !== '') {
            return $this->sendViaResend($resendKey, $email, $subject, $html, $from);
        }

        return null;
    }

    private function sendViaBrevo(string $key, string $email, string $subject, string $html, string $from): bool
    {
        try {
            $response = Http::connectTimeout(4)->timeout(8)
                ->withHeaders([
                    'api-key' => $key,
                    'accept' => 'application/json',
                ])
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => (string) config('mail.from.name'),
                        'email' => $from,
                    ],
                    'to' => [['email' => $email]],
                    'subject' => $subject,
                    'htmlContent' => $html,
                ]);
        } catch (Throwable $e) {
            $this->lastError = 'Không kết nối được Brevo để gửi email.';
            Log::warning('Brevo OTP email failed: '.$e->getMessage());

            return false;
        }

        if ($response->successful()) {
            $this->lastError = null;

            return true;
        }

        $reason = (string) ($response->json('message') ?: 'Brevo từ chối gửi email.');
        $this->lastError = 'Không gửi được email qua Brevo. '.$reason;
        Log::warning('Brevo OTP email rejected: '.$response->status().' '.$reason);

        return false;
    }

    private function sendViaResend(string $key, string $email, string $subject, string $html, string $from): bool
    {
        try {
            $response = Http::connectTimeout(4)->timeout(8)
                ->withToken($key)
                ->acceptJson()
                ->post('https://api.resend.com/emails', [
                    'from' => trim((string) config('mail.from.name').' <'.$from.'>'),
                    'to' => [$email],
                    'subject' => $subject,
                    'html' => $html,
                ]);
        } catch (Throwable $e) {
            $this->lastError = 'Không kết nối được Resend để gửi email.';
            Log::warning('Resend OTP email failed: '.$e->getMessage());

            return false;
        }

        if ($response->successful()) {
            $this->lastError = null;

            return true;
        }

        $reason = (string) ($response->json('message') ?: 'Resend từ chối gửi email.');
        $this->lastError = 'Không gửi được email qua Resend. '.$reason;
        Log::warning('Resend OTP email rejected: '.$response->status().' '.$reason);

        return false;
    }

    private function smtpFailureMessage(string $error): string
    {
        $blocked = str_contains($error, 'timed out')
            || str_contains($error, 'Unable to connect')
            || str_contains($error, 'unreachable')
            || str_contains($error, 'Connection refused')
            || str_contains($error, 'Connection could not be established');

        if ($blocked) {
            return 'Render gói miễn phí chặn cổng SMTP 587 nên Gmail không gửi được. Thêm BREVO_API_KEY trên Render (đăng ký miễn phí tại brevo.com, xác minh email gửi), hoặc nâng gói Render để mở cổng 587.';
        }

        if (str_contains($error, 'authenticate') || str_contains($error, '535')) {
            return 'Gmail từ chối đăng nhập SMTP. Hãy dùng mật khẩu ứng dụng trong MAIL_PASSWORD, không dùng mật khẩu đăng nhập Gmail.';
        }

        return 'Không gửi được email. Kiểm tra MAIL_USERNAME và MAIL_PASSWORD trên Render.';
    }

    public function sendSms(string $phone, string $code): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        $result = $this->sms->sendOtp($phone, $code);
        if (! ($result['success'] ?? false)) {
            $this->lastError = (string) ($result['message'] ?? 'Không gửi được SMS.');
            Log::warning('Send OTP SMS failed: '.$this->lastError);

            return false;
        }

        $this->lastError = null;

        return true;
    }

    public function send(string $type, string $target, string $code, string $purpose = 'verify'): bool
    {
        return $type === 'email'
            ? $this->sendEmail($target, $code, $purpose)
            : $this->sendSms($target, $code);
    }
}
