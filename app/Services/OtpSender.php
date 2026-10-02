<?php

namespace App\Services;

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

    /**
     * @param  'verify'|'reset'  $purpose
     */
    public function sendEmail(string $email, string $code, string $purpose = 'verify'): bool
    {
        $subject = $purpose === 'reset'
            ? "[Mộc An] Mã đặt lại mật khẩu: {$code}"
            : "[Mộc An] Mã xác thực tài khoản của bạn: {$code}";

        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            if (filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password'))) {
                $mailer = 'smtp';
            } else {
                $this->lastError = 'Máy chủ chưa cấu hình SMTP. Trên Render hãy đặt MAIL_MAILER=smtp, MAIL_USERNAME và MAIL_PASSWORD (mật khẩu ứng dụng Gmail).';
                Log::warning('OTP email skipped because MAIL_MAILER='.config('mail.default'));

                return false;
            }
        }

        $from = (string) config('mail.from.address');
        $smtpUser = (string) config('mail.mailers.smtp.username');
        if (($from === '' || str_ends_with($from, '@example.com')) && $smtpUser !== '') {
            $from = $smtpUser;
        }

        try {
            $html = view('emails.otp', ['code' => $code, 'purpose' => $purpose])->render();
            Mail::mailer($mailer)->html($html, function ($message) use ($email, $subject, $from) {
                $message->to($email)->subject($subject);
                if ($from !== '') {
                    $message->from($from, (string) config('mail.from.name'));
                }
            });

            $this->lastError = null;

            return true;
        } catch (Throwable $e) {
            $this->lastError = 'Không gửi được email. Kiểm tra MAIL_HOST, MAIL_PORT=587, MAIL_USERNAME và MAIL_PASSWORD trên Render.';
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
