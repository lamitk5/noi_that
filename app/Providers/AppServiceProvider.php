<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $cart = session('furniture_cart', []);
            $view->with('cartCount', array_sum(array_column($cart, 'quantity')));

            $wishlistProductIds = [];
            if (\Illuminate\Support\Facades\Auth::check()) {
                $wishlistProductIds = \App\Models\Wishlist::where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->pluck('product_id')
                    ->all();
            }
            $view->with('wishlistCount', count($wishlistProductIds));
            $view->with('wishlistProductIds', $wishlistProductIds);
        });
    }
}
