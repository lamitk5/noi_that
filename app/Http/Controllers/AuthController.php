<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $loginInput = trim((string) ($request->input('email') ?? $request->input('username') ?? $request->input('account')));
        $password = (string) $request->input('password');

        if (empty($loginInput) || empty($password)) {
            return back()->withErrors([
                'email' => 'Vui lòng nhập thông tin đăng nhập và mật khẩu.',
            ])->onlyInput('email');
        }

        $remember = (bool) $request->input('remember', false);
        $cleanPhone = preg_replace('/[^0-9]/', '', $loginInput);

        // Find user by username, email, or phone
        $user = User::where('email', strtolower($loginInput))
            ->orWhere('username', strtolower($loginInput))
            ->orWhere('phone', $loginInput)
            ->when(strlen($cleanPhone) >= 9, fn($q) => $q->orWhere('phone', $cleanPhone))
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Check if user is active/verified
            if (isset($user->is_active) && !$user->is_active) {
                $code = (string) random_int(100000, 999999);
                $type = !empty($user->email) ? 'email' : 'phone';
                $target = $user->email ?: $user->phone;

                if ($type === 'email') {
                    try {
                        $this->sendEmailVerification($user->email, $code);
                    } catch (Throwable $e) {
                        Log::error('Send verification email error: ' . $e->getMessage());
                        if (config('mail.default') !== 'log' && !app()->runningUnitTests()) {
                            return back()->withErrors([
                                'email' => 'Lỗi gửi email xác thực: ' . $e->getMessage(),
                            ])->onlyInput('email');
                        }
                    }
                } else {
                    if (!app()->runningUnitTests()) {
                        $smsResult = (new SmsService())->sendOtp($user->phone, $code);
                        if (!$smsResult['success']) {
                            return back()->withErrors([
                                'email' => 'Lỗi gửi tin nhắn OTP: ' . $smsResult['message'],
                            ])->onlyInput('email');
                        }
                    }
                }

                if ($request->hasSession()) {
                    $request->session()->put('verify_data', [
                        'user_id' => $user->id,
                        'type' => $type,
                        'target' => $target,
                        'code' => $code,
                        'expires_at' => now()->addMinutes(10)->timestamp,
                    ]);
                }

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'require_verification' => true,
                        'message' => 'Tài khoản chưa được kích hoạt. Vui lòng nhập mã xác thực vừa được gửi.',
                    ], 403);
                }

                $warningMsg = 'Tài khoản chưa kích hoạt. Vui lòng nhập mã xác thực vừa được gửi.';
                if (config('mail.default') === 'log') {
                    $warningMsg .= " (Chưa cấu hình SMTP: Mã của bạn là {$code})";
                }

                return redirect()->route('auth.verify')->with('warning', $warningMsg);
            }

            Auth::login($user, $remember);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đăng nhập thành công!',
                    'user' => $user,
                ]);
            }

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))->with('success', 'Đăng nhập thành công! Xin chào ' . $user->name . '.');
            }

            return redirect()->intended(route('home'))->with('success', 'Đăng nhập thành công!');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Tên đăng nhập / Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        return back()->withErrors([
            'email' => 'Tên đăng nhập / Email hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        // Allow fallback if legacy API calls send 'email'
        if (!$request->has('email_or_phone') && $request->has('email')) {
            $request->merge(['email_or_phone' => $request->input('email')]);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email_or_phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.min' => 'Tên đăng nhập phải chứa ít nhất 3 ký tự.',
            'username.alpha_dash' => 'Tên đăng nhập chỉ được chứa chữ cái, số, dấu gạch nối và gạch dưới.',
            'username.unique' => 'Tên đăng nhập này đã được sử dụng.',
            'name.required' => 'Vui lòng nhập họ và tên.',
            'email_or_phone.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải chứa ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $contact = trim($validated['email_or_phone']);
        $email = null;
        $phone = null;
        $type = 'email';

        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($contact);
            if (User::where('email', $email)->exists()) {
                return back()->withErrors([
                    'email_or_phone' => 'Email này đã được đăng ký trên hệ thống.',
                ])->withInput();
            }
            $type = 'email';
        } else {
            $digits = preg_replace('/[^0-9]/', '', $contact);
            if (strlen($digits) < 9 || strlen($digits) > 11) {
                return back()->withErrors([
                    'email_or_phone' => 'Vui lòng nhập email hợp lệ hoặc số điện thoại (9-11 chữ số).',
                ])->withInput();
            }
            $phone = $digits;
            if (User::where('phone', $phone)->exists()) {
                return back()->withErrors([
                    'email_or_phone' => 'Số điện thoại này đã được đăng ký trên hệ thống.',
                ])->withInput();
            }
            $type = 'phone';
        }

        $user = User::create([
            'username' => strtolower($validated['username']),
            'name' => $validated['name'],
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        // Generate 6-digit verification code / OTP
        $code = (string) random_int(100000, 999999);
        $target = $type === 'email' ? $email : $phone;

        // Send Real Email or Real SMS
        if ($type === 'email') {
            try {
                $this->sendEmailVerification($email, $code);
            } catch (Throwable $e) {
                Log::error('Send verification email error: ' . $e->getMessage());
                if (config('mail.default') !== 'log' && !app()->runningUnitTests()) {
                    $user->delete();
                    return back()->withErrors([
                        'email_or_phone' => 'Lỗi gửi email xác thực: ' . $e->getMessage() . '. Vui lòng kiểm tra lại cấu hình SMTP trong file .env.',
                    ])->withInput();
                }
            }
        } else {
            if (!app()->runningUnitTests()) {
                $smsResult = (new SmsService())->sendOtp($phone, $code);
                if (!$smsResult['success']) {
                    $user->delete();
                    return back()->withErrors([
                        'email_or_phone' => 'Lỗi gửi SMS OTP: ' . $smsResult['message'],
                    ])->withInput();
                }
            }
        }

        if ($request->hasSession()) {
            $request->session()->put('verify_data', [
                'user_id' => $user->id,
                'type' => $type,
                'target' => $target,
                'code' => $code,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'require_verification' => true,
                'type' => $type,
                'target' => $target,
                'message' => $type === 'email'
                    ? "Mã xác thực 6 chữ số đã được gửi về email {$email}."
                    : "Mã OTP 6 chữ số đã được gửi về số điện thoại {$phone}.",
            ], 201);
        }

        $infoMsg = $type === 'email'
            ? "Mã xác thực 6 chữ số đã được gửi về email {$email}. Vui lòng nhập mã để hoàn tất đăng ký."
            : "Mã OTP 6 chữ số đã được gửi về số điện thoại {$phone}. Vui lòng nhập mã để hoàn tất đăng ký.";

        if (config('mail.default') === 'log') {
            $infoMsg .= " (Chưa cấu hình SMTP: Mã của bạn là {$code})";
        }

        return redirect()->route('auth.verify')->with('info', $infoMsg);
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $verifyData = $request->session()->get('verify_data');
        if (!$verifyData) {
            return redirect()->route('login')->with('info', 'Không có phiên xác thực nào đang chờ.');
        }

        return view('auth.verify', [
            'type' => $verifyData['type'] ?? 'email',
            'target' => $verifyData['target'] ?? '',
        ]);
    }

    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực gồm 6 chữ số.',
            'code.size' => 'Mã xác thực phải đúng 6 chữ số.',
        ]);

        $verifyData = $request->session()->get('verify_data');
        if (!$verifyData) {
            return redirect()->route('login')->withErrors(['email' => 'Phiên xác thực đã hết hạn. Vui lòng đăng nhập lại.']);
        }

        if (now()->timestamp > ($verifyData['expires_at'] ?? 0)) {
            return back()->withErrors(['code' => 'Mã xác thực đã hết hạn. Vui lòng nhấn gửi lại mã.']);
        }

        if (trim($request->input('code')) !== (string) $verifyData['code']) {
            return back()->withErrors(['code' => 'Mã xác thực không chính xác. Vui lòng kiểm tra lại.'])->withInput();
        }

        $user = User::find($verifyData['user_id']);
        if (!$user) {
            return redirect()->route('register')->withErrors(['email_or_phone' => 'Không tìm thấy tài khoản. Vui lòng đăng ký lại.']);
        }

        $user->is_active = true;
        if ($verifyData['type'] === 'email') {
            $user->email_verified_at = now();
        }
        $user->save();

        $request->session()->forget('verify_data');
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Xác thực tài khoản thành công!',
                'user' => $user,
            ]);
        }

        return redirect()->route('home')->with('success', 'Xác thực tài khoản thành công! Chào mừng ' . $user->name . ' đến với Mộc An.');
    }

    public function resendVerify(Request $request): RedirectResponse|JsonResponse
    {
        $verifyData = $request->session()->get('verify_data');
        if (!$verifyData) {
            return redirect()->route('login')->withErrors(['email' => 'Phiên xác thực đã hết hạn.']);
        }

        $newCode = (string) random_int(100000, 999999);
        $verifyData['code'] = $newCode;
        $verifyData['expires_at'] = now()->addMinutes(10)->timestamp;

        $user = User::find($verifyData['user_id']);
        if ($verifyData['type'] === 'email' && $user && $user->email) {
            try {
                $this->sendEmailVerification($user->email, $newCode);
            } catch (Throwable $e) {
                Log::error('Resend verification email error: ' . $e->getMessage());
                if (config('mail.default') !== 'log' && !app()->runningUnitTests()) {
                    return back()->withErrors(['code' => 'Lỗi gửi email xác thực: ' . $e->getMessage()]);
                }
            }
        } elseif ($user && $user->phone) {
            if (!app()->runningUnitTests()) {
                $smsResult = (new SmsService())->sendOtp($user->phone, $newCode);
                if (!$smsResult['success']) {
                    return back()->withErrors(['code' => 'Lỗi gửi tin nhắn OTP: ' . $smsResult['message']]);
                }
            }
        }

        $request->session()->put('verify_data', $verifyData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mã xác thực mới đã được gửi.',
            ]);
        }

        $resendSuccessMsg = 'Mã xác thực mới đã được gửi!';
        if (config('mail.default') === 'log') {
            $resendSuccessMsg .= " (Chưa cấu hình SMTP: Mã của bạn là {$newCode})";
        }

        return back()->with('success', $resendSuccessMsg);
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã đăng xuất thành công.',
            ]);
        }

        return redirect()->route('home')->with('success', 'Đã đăng xuất.');
    }

    /**
     * Send branded HTML verification email
     */
    protected function sendEmailVerification(string $email, string $code): void
    {
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 28px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #ffffff;'>
            <div style='text-align: center; margin-bottom: 24px;'>
                <h1 style='color: #78350f; font-size: 24px; font-weight: bold; margin: 0;'>MỘC AN</h1>
                <p style='color: #92400e; font-size: 13px; margin: 4px 0 0 0;'>Nội Thất Gỗ Tự Nhiên & Sang Trọng</p>
            </div>
            <div style='border-top: 1px solid #f3f4f6; padding-top: 20px;'>
                <p style='color: #1f2937; font-size: 15px; margin: 0 0 12px 0;'>Xin chào,</p>
                <p style='color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;'>
                    Cảm ơn bạn đã đăng ký tài khoản tại <strong>Mộc An</strong>. Dưới đây là mã xác thực tài khoản của bạn:
                </p>
                <div style='text-align: center; margin: 24px 0;'>
                    <span style='display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #78350f; background: #fef3c7; border: 1px dashed #d97706; padding: 12px 28px; border-radius: 8px;'>{$code}</span>
                </div>
                <p style='color: #6b7280; font-size: 13px; line-height: 1.5; margin: 0 0 8px 0;'>
                    • Mã xác thực có hiệu lực trong <strong>10 phút</strong>.
                </p>
                <p style='color: #ef4444; font-size: 12px; line-height: 1.5; margin: 0;'>
                    • Tuyệt đối không chia sẻ mã này cho bất kỳ ai để bảo vệ tài khoản của bạn.
                </p>
            </div>
            <div style='border-top: 1px solid #f3f4f6; margin-top: 24px; padding-top: 16px; text-align: center; color: #9ca3af; font-size: 12px;'>
                © " . date('Y') . " Mộc An. Mọi quyền được bảo lưu.
            </div>
        </div>";

        Mail::html($html, function ($message) use ($email, $code) {
            $message->to($email)
                ->subject("[Mộc An] Mã xác thực tài khoản của bạn: {$code}");
        });

        Log::info("[EMAIL VERIFICATION] Mã {$code} đã được gửi đến {$email}");
    }
}
