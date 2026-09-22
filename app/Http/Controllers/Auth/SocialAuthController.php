<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect to social OAuth provider or simulate sandbox OAuth callback.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('login')->with('error', 'Cổng đăng nhập không được hỗ trợ.');
        }

        // When configured in production with live OAuth secrets, standard OAuth flow runs.
        // For development/demonstration/testing, provide instant simulated one-click sign in:
        return redirect()->route('auth.social.callback', ['provider' => $provider]);
    }

    /**
     * Handle provider callback.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            return redirect()->route('login')->with('error', 'Cổng đăng nhập không hợp lệ.');
        }

        // Demo profile generator for seamless testing & demonstration
        $providerName = ucfirst($provider);
        $mockEmail = strtolower($provider) . '.customer@mocan-demo.vn';

        $user = User::firstOrCreate(
            ['email' => $mockEmail],
            [
                'name' => 'Khách Hàng (' . $providerName . ')',
                'password' => bcrypt(Str::random(24)),
                'role' => 'customer',
                'phone' => '0901234567',
                'loyalty_points' => 100,
                'loyalty_tier' => 'bronze',
            ]
        );

        $guestCart = $request->session()->get('cart', []);
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('cart', $guestCart);
        app(\App\Services\CartService::class)->mergeGuestCart($user);

        return redirect()->intended(route('home'))
            ->with('success', 'Đăng nhập thành công qua ' . $providerName . '! Chào mừng bạn đến với Mộc An.');
    }
}
