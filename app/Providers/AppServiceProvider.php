<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\CompareService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $account = Str::lower((string) ($request->input('email') ?? $request->input('username') ?? ''));

            return [
                Limit::perMinute(5)->by('login:'.$account.'|'.$request->ip()),
                Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinutes(10, 10)->by('pwreset-ip:'.$request->ip()));

        RateLimiter::for('ai-chat', fn (Request $request) => Limit::perMinute(20)->by('ai-chat:'.($request->user()?->id ?? $request->ip())));

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            static $shared = null;

            if ($shared === null) {
                $cart = session('furniture_cart', []);
                $wishlistProductIds = [];
                if (\Illuminate\Support\Facades\Auth::check()) {
                    $wishlistProductIds = \App\Models\Wishlist::where('user_id', \Illuminate\Support\Facades\Auth::id())
                        ->pluck('product_id')
                        ->all();
                }

                $compareItems = request()->is('admin', 'admin/*')
                    ? collect()
                    : app(CompareService::class)->products();

                $shared = [
                    'cartCount' => array_sum(array_column($cart, 'quantity')),
                    'wishlistProductIds' => $wishlistProductIds,
                    'wishlistCount' => count($wishlistProductIds),
                    'compareItems' => $compareItems,
                    'compareProductIds' => $compareItems->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'compareCount' => $compareItems->count(),
                ];
            }

            $view->with($shared);
        });
    }
}
