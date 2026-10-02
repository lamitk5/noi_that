<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected OtpSender $otp)
    {
    }

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
                $code = OtpSender::generateCode();
                $type = !empty($user->email) ? 'email' : 'phone';
                $target = $user->email ?: $user->phone;
                $sentSuccessfully = $this->otp->send($type, $target, $code);

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
                        'message' => 'Tài khoản chưa được kích hoạt. Vui lòng nhập mã xác thực.',
                    ], 403);
                }

                $warningMsg = $sentSuccessfully
                    ? 'Tài khoản chưa kích hoạt. Vui lòng nhập mã xác thực vừa được gửi.'
                    : 'Tài khoản chưa kích hoạt. Hệ thống chưa gửi được mã xác thực, vui lòng bấm "Gửi lại mã" hoặc liên hệ hỗ trợ.';

                if (OtpSender::canRevealCode()) {
                    $warningMsg .= " (Môi trường local - mã của bạn là {$code})";
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

        $code = OtpSender::generateCode();
        $target = $type === 'email' ? $email : $phone;
        $sentSuccessfully = $this->otp->send($type, $target, $code);

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

        if ($sentSuccessfully) {
            $infoMsg = $type === 'email'
                ? "Mã xác thực 6 chữ số đã được gửi về email {$email}. Vui lòng nhập mã để hoàn tất đăng ký."
                : "Mã OTP 6 chữ số đã được gửi về số điện thoại {$phone}. Vui lòng nhập mã để hoàn tất đăng ký.";
        } else {
            $infoMsg = 'Tài khoản đã được tạo nhưng hệ thống chưa gửi được mã xác thực. Vui lòng bấm "Gửi lại mã xác thực".';
        }

        if (OtpSender::canRevealCode()) {
            $infoMsg .= " (Môi trường local - mã của bạn là {$code})";
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

        if (($verifyData['attempts'] ?? 0) >= 5) {
            return back()->withErrors(['code' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng bấm gửi lại mã.']);
        }

        if (! hash_equals((string) $verifyData['code'], trim((string) $request->input('code')))) {
            $verifyData['attempts'] = ($verifyData['attempts'] ?? 0) + 1;
            $request->session()->put('verify_data', $verifyData);

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

        $limiterKey = 'verify-resend:'.($verifyData['user_id'] ?? $request->session()->getId());
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->withErrors(['code' => 'Bạn đã yêu cầu gửi lại quá nhiều lần. Vui lòng thử lại sau ít phút.']);
        }
        RateLimiter::hit($limiterKey, 600);

        $newCode = OtpSender::generateCode();
        $verifyData['code'] = $newCode;
        $verifyData['expires_at'] = now()->addMinutes(10)->timestamp;
        $verifyData['attempts'] = 0;

        $user = User::find($verifyData['user_id']);
        $sentSuccessfully = false;

        if ($verifyData['type'] === 'email' && $user && $user->email) {
            $sentSuccessfully = $this->otp->sendEmail($user->email, $newCode);
        } elseif ($user && $user->phone) {
            $sentSuccessfully = $this->otp->sendSms($user->phone, $newCode);
        }

        $request->session()->put('verify_data', $verifyData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mã xác thực mới đã được gửi.',
            ]);
        }

        $resendSuccessMsg = $sentSuccessfully
            ? 'Mã xác thực mới đã được gửi!'
            : 'Hệ thống chưa gửi được mã xác thực. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.';

        if (OtpSender::canRevealCode()) {
            $resendSuccessMsg .= " (Môi trường local - mã của bạn là {$newCode})";
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

}
