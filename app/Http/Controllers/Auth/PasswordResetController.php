<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    private const SESSION_KEY = 'password_reset';

    private const CODE_TTL_MINUTES = 10;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    private const MAX_SENDS_PER_TARGET = 3;

    private const SEND_WINDOW_SECONDS = 600;

    private const RESET_WINDOW_MINUTES = 10;

    private const GENERIC_SENT_MESSAGE = 'Nếu tài khoản tồn tại, mã xác thực 6 chữ số đã được gửi tới email hoặc số điện thoại đã đăng ký. Mã có hiệu lực trong 10 phút.';

    public function __construct(protected OtpSender $otp)
    {
    }

    public function showRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account' => ['required', 'string', 'max:255'],
        ], [
            'account.required' => 'Vui lòng nhập email, số điện thoại hoặc tên đăng nhập.',
        ]);

        $account = trim($validated['account']);
        $limiterKey = 'pwreset-send:'.sha1(Str::lower($account));

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS_PER_TARGET)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);

            return back()->withInput()->withErrors([
                'account' => "Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau {$minutes} phút.",
            ]);
        }
        RateLimiter::hit($limiterKey, self::SEND_WINDOW_SECONDS);

        $user = $this->findUser($account);

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user?->id,
            'verified_until' => null,
        ]);

        $info = self::GENERIC_SENT_MESSAGE;
        if ($user) {
            $code = $this->issueCode($user);
            if ($code === false) {
                return back()->withInput()->withErrors([
                    'account' => $this->otp->lastError ?: 'Không gửi được mã xác thực. Vui lòng thử lại sau.',
                ]);
            }
        }

        return redirect()->route('password.verify')->with('info', $info);
    }

    public function resend(Request $request): RedirectResponse
    {
        $state = $request->session()->get(self::SESSION_KEY);
        if (! $state) {
            return redirect()->route('password.request');
        }

        $user = $state['user_id'] ? User::find($state['user_id']) : null;
        $limiterKey = 'pwreset-resend:'.($user?->id ?? $request->session()->getId());

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS_PER_TARGET)) {
            return back()->withErrors(['code' => 'Bạn đã yêu cầu gửi lại quá nhiều lần. Vui lòng thử lại sau.']);
        }
        RateLimiter::hit($limiterKey, self::SEND_WINDOW_SECONDS);

        $info = 'Nếu tài khoản tồn tại, một mã xác thực mới đã được gửi.';
        if ($user) {
            $code = $this->issueCode($user);
            if ($code === null) {
                return back()->withErrors(['code' => 'Vui lòng đợi '.self::RESEND_COOLDOWN_SECONDS.' giây trước khi yêu cầu mã mới.']);
            }
            if ($code === false) {
                return back()->withErrors([
                    'code' => $this->otp->lastError ?: 'Không gửi được mã xác thực. Vui lòng thử lại sau.',
                ]);
            }
        }

        return back()->with('info', $info);
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-verify');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực gồm 6 chữ số.',
            'code.digits' => 'Mã xác thực phải gồm đúng 6 chữ số.',
        ]);

        $state = $request->session()->get(self::SESSION_KEY);
        if (! $state) {
            return redirect()->route('password.request');
        }

        $invalid = back()->withErrors(['code' => 'Mã xác thực không chính xác hoặc đã hết hạn.']);
        $userId = $state['user_id'];
        if (! $userId) {
            return $invalid;
        }

        $attemptsKey = 'pwreset-attempts:'.$userId;
        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->deleteToken($userId);

            return back()->withErrors(['code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng yêu cầu mã mới.']);
        }

        $row = DB::table('password_reset_tokens')->where('email', $this->tokenKey($userId))->first();
        $expired = ! $row || Carbon::parse($row->created_at)->addMinutes(self::CODE_TTL_MINUTES)->isPast();

        if ($expired || ! Hash::check($request->input('code'), $row->token)) {
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(self::CODE_TTL_MINUTES));

            return $invalid;
        }

        Cache::forget($attemptsKey);
        $this->deleteToken($userId);

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $userId,
            'verified_until' => now()->addMinutes(self::RESET_WINDOW_MINUTES)->timestamp,
        ]);
        $request->session()->regenerate();

        return redirect()->route('password.reset');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedUserId($request)) {
            return redirect()->route('password.request')->with('warning', 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thực hiện lại.');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request): RedirectResponse
    {
        $userId = $this->verifiedUserId($request);
        if (! $userId) {
            return redirect()->route('password.request')->with('warning', 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thực hiện lại.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
        ]);

        $user = User::find($userId);
        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
            'is_active' => true,
        ]);
        if ($user->email && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $this->notifyPasswordChanged($user);

        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->route('login')->with('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    /**
     * Returns the plain code, null during the resend cooldown, or false when delivery failed.
     */
    protected function issueCode(User $user): string|false|null
    {
        $key = $this->tokenKey($user->id);
        $existing = DB::table('password_reset_tokens')->where('email', $key)->first();
        if ($existing && Carbon::parse($existing->created_at)->addSeconds(self::RESEND_COOLDOWN_SECONDS)->isFuture()) {
            return null;
        }

        $code = OtpSender::generateCode();
        $sent = $user->email
            ? $this->otp->sendEmail($user->email, $code, 'reset')
            : ($user->phone ? $this->otp->sendSms($user->phone, $code) : false);

        if (! $sent) {
            if (! $this->otp->lastError) {
                $this->otp->lastError = 'Tài khoản chưa có email hoặc số điện thoại để nhận mã xác thực.';
            }

            return false;
        }

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $key],
            ['token' => Hash::make($code), 'created_at' => now()]
        );
        Cache::forget('pwreset-attempts:'.$user->id);

        return $code;
    }

    protected function findUser(string $account): ?User
    {
        $digits = preg_replace('/[^0-9]/', '', $account);

        return User::where('email', Str::lower($account))
            ->orWhere('username', Str::lower($account))
            ->orWhere('phone', $account)
            ->when(strlen($digits) >= 9, fn ($q) => $q->orWhere('phone', $digits))
            ->first();
    }

    protected function verifiedUserId(Request $request): ?int
    {
        $state = $request->session()->get(self::SESSION_KEY);
        if (! $state || ! $state['user_id'] || ! $state['verified_until'] || $state['verified_until'] < now()->timestamp) {
            return null;
        }

        return (int) $state['user_id'];
    }

    protected function tokenKey(int $userId): string
    {
        return 'user:'.$userId;
    }

    protected function deleteToken(int $userId): void
    {
        DB::table('password_reset_tokens')->where('email', $this->tokenKey($userId))->delete();
    }

    protected function notifyPasswordChanged(User $user): void
    {
        if (! $user->email) {
            return;
        }

        try {
            $html = view('emails.password_changed', [
                'name' => $user->name,
                'changedAt' => now()->format('H:i d/m/Y'),
            ])->render();
            Mail::html($html, fn ($m) => $m->to($user->email)->subject('[Mộc An] Mật khẩu của bạn đã được thay đổi'));
        } catch (Throwable $e) {
            Log::warning('Send password changed email failed: '.$e->getMessage());
        }
    }
}
