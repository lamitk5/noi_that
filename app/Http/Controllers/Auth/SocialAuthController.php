<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    /**
     * Supported OAuth providers
     *
     * @var array<string>
     */
    protected array $supportedProviders = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->with('error', 'Phương thức đăng nhập không được hỗ trợ.');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret) || $clientId === 'mock-google-client-id') {
            return redirect()->route('login')->with('error', 'Chưa cấu hình GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env để kết nối Google OAuth thật.');
        }

        try {
            /** @var \Laravel\Socialite\Contracts\Provider $driver */
            $driver = Socialite::driver($provider);
            $redirectUrl = config('services.google.redirect') ?: url('/auth/google/callback');
            if (str_starts_with((string) config('app.url'), 'https://') && str_starts_with($redirectUrl, 'http://')) {
                $redirectUrl = preg_replace('/^http:/', 'https:', $redirectUrl);
            }
            if (method_exists($driver, 'redirectUrl')) {
                $driver->redirectUrl($redirectUrl);
            }
            if (method_exists($driver, 'stateless')) {
                $driver = $driver->stateless();
            }
            return $driver->redirect();
        } catch (Throwable $e) {
            return redirect()->route('login')->with('error', 'Lỗi khởi tạo đăng nhập Google: ' . $e->getMessage());
        }
    }

    public function callback(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->with('error', 'Phương thức đăng nhập không được hỗ trợ.');
        }

        try {
            /** @var \Laravel\Socialite\Contracts\Provider $driver */
            $driver = Socialite::driver($provider);
            $redirectUrl = config('services.google.redirect') ?: url('/auth/google/callback');
            if (str_starts_with((string) config('app.url'), 'https://') && str_starts_with($redirectUrl, 'http://')) {
                $redirectUrl = preg_replace('/^http:/', 'https:', $redirectUrl);
            }
            if (method_exists($driver, 'redirectUrl')) {
                $driver->redirectUrl($redirectUrl);
            }
            if (method_exists($driver, 'stateless')) {
                $driver = $driver->stateless();
            }
            $socialUser = $driver->user();

            $email = $socialUser->getEmail();
            $providerId = (string) $socialUser->getId();
            $name = $socialUser->getName() ?: $socialUser->getNickname() ?: 'Google User';
            $avatar = $socialUser->getAvatar();

            return $this->authenticateSocialUser($provider, $providerId, $name, $email, $avatar);
        } catch (Throwable $e) {
            return redirect()->route('login')->with('error', 'Đăng nhập qua Google không thành công: ' . $e->getMessage());
        }
    }

    protected function authenticateSocialUser(string $provider, string $providerId, string $name, ?string $email, ?string $avatar): RedirectResponse
    {
        $user = null;

        if ($email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user && $providerId) {
            $user = User::where('provider', $provider)->where('provider_id', $providerId)->first();
        }

        if ($user) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $avatar ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'is_active' => true,
            ]);
        } else {
            $baseUsername = Str::slug($name, '');
            if (empty($baseUsername)) {
                $baseUsername = 'google_user';
            }
            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $user = User::create([
                'username' => $username,
                'name' => $name,
                'email' => $email ?: ($username . '@gmail.com'),
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $avatar,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended(route('home'))->with('success', 'Đăng nhập bằng Google thành công! Xin chào ' . $user->name . '.');
    }
}
