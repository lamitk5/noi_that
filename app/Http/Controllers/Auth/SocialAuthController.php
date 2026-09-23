<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
    protected array $supportedProviders = ['google', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->with('error', 'Phương thức đăng nhập không được hỗ trợ.');
        }

        /** @var \Laravel\Socialite\Contracts\Provider $driver */
        $driver = Socialite::driver($provider);

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if (!in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->with('error', 'Phương thức đăng nhập không được hỗ trợ.');
        }

        try {
            /** @var \Laravel\Socialite\Contracts\Provider $driver */
            $driver = Socialite::driver($provider);
            $socialUser = $driver->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->with('error', 'Đăng nhập qua ' . ucfirst($provider) . ' không thành công. Vui lòng thử lại.');
        }

        $email = $socialUser->getEmail();
        $providerId = (string) $socialUser->getId();
        $name = $socialUser->getName() ?: $socialUser->getNickname() ?: ('Thành viên ' . ucfirst($provider));
        $avatar = $socialUser->getAvatar();

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
            ]);
        } else {
            $fallbackEmail = $email ?: ($provider . '_' . $providerId . '@social.mocan.vn');
            $user = User::create([
                'name' => $name,
                'email' => $fallbackEmail,
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $avatar,
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', 'Đăng nhập thành công qua ' . ucfirst($provider) . '!');
    }
}
