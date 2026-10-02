<?php

namespace App\Http\Controllers;

use App\Services\OtpSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    private const SESSION_KEY = 'password_change';

    public function __construct(protected OtpSender $otp)
    {
    }

    /**
     * Show the current customer's profile form.
     */
    public function edit(): View
    {
        return view('account.profile', ['user' => Auth::user()]);
    }

    /**
     * Update the current customer's own info (email checked against DB).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable', 'string', 'max:20',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng bởi tài khoản khác, vui lòng chọn email khác.',
            'phone.unique' => 'Số điện thoại đã được sử dụng bởi tài khoản khác.',
            'current_password.required_with' => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không chính xác.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;
        $user->save();

        if (empty($validated['password'])) {
            return redirect()->route('profile.edit')->with('success', 'Đã cập nhật thông tin cá nhân.');
        }

        $sent = $this->sendPasswordCode($request, $user, Hash::make($validated['password']));
        if (! $sent) {
            return back()->withErrors([
                'password' => $this->otp->lastError ?: 'Không gửi được mã xác thực. Mật khẩu chưa được đổi.',
            ]);
        }

        return redirect()->route('profile.password.verify')->with('info', 'Mã xác thực 6 chữ số đã được gửi. Nhập mã để hoàn tất đổi mật khẩu.');
    }

    public function showPasswordVerify(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingChange($request);
        if (! $pending) {
            return redirect()->route('profile.edit')->with('warning', 'Phiên đổi mật khẩu đã hết hạn. Vui lòng thực hiện lại.');
        }

        return view('account.password-verify', ['target' => $pending['target']]);
    }

    public function verifyPasswordChange(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực gồm 6 chữ số.',
            'code.digits' => 'Mã xác thực phải gồm đúng 6 chữ số.',
        ]);

        $pending = $this->pendingChange($request);
        if (! $pending) {
            return redirect()->route('profile.edit')->with('warning', 'Phiên đổi mật khẩu đã hết hạn. Vui lòng thực hiện lại.');
        }

        if (($pending['attempts'] ?? 0) >= 5) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('profile.edit')->withErrors(['password' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng đổi mật khẩu lại.']);
        }

        if (! Hash::check($request->input('code'), $pending['code'])) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $request->session()->put(self::SESSION_KEY, $pending);

            return back()->withErrors(['code' => 'Mã xác thực không chính xác.']);
        }

        $user = Auth::user();
        $user->password = $pending['password'];
        $user->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $request->session()->forget(self::SESSION_KEY);
        $this->notifyPasswordChanged($user);

        return redirect()->route('profile.edit')->with('success', 'Đã đổi mật khẩu.');
    }

    public function resendPasswordCode(Request $request): RedirectResponse
    {
        $pending = $this->pendingChange($request);
        if (! $pending) {
            return redirect()->route('profile.edit');
        }

        $limiterKey = 'profile-password-resend:'.$pending['user_id'];
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->withErrors(['code' => 'Bạn đã yêu cầu gửi lại quá nhiều lần. Vui lòng thử lại sau ít phút.']);
        }
        RateLimiter::hit($limiterKey, 600);

        $sent = $this->sendPasswordCode($request, Auth::user(), $pending['password']);
        if (! $sent) {
            return back()->withErrors(['code' => $this->otp->lastError ?: 'Không gửi được mã xác thực.']);
        }

        return back()->with('info', 'Mã xác thực mới đã được gửi.');
    }

    protected function sendPasswordCode(Request $request, $user, string $passwordHash): bool
    {
        $type = $user->email ? 'email' : 'phone';
        $target = $type === 'email' ? $user->email : $user->phone;
        if (! $target) {
            $this->otp->lastError = 'Tài khoản chưa có email hoặc số điện thoại để nhận mã xác thực.';

            return false;
        }

        $code = OtpSender::generateCode();
        $sent = $this->otp->send($type, $target, $code, 'reset');
        if (! $sent) {
            return false;
        }

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'password' => $passwordHash,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
            'target' => $target,
        ]);

        return true;
    }

    protected function pendingChange(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || (int) ($pending['user_id'] ?? 0) !== (int) Auth::id()) {
            return null;
        }
        if (($pending['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $pending;
    }

    protected function notifyPasswordChanged($user): void
    {
        if (! $user->email || in_array(config('mail.default'), ['log', 'array'], true)) {
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
