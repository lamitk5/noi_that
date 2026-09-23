<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\Payments\MomoController;
use App\Http\Controllers\Payments\VnpayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Giao Hàng Nhanh (GHN) APIs & Webhook
Route::get('/api/shipping/ghn/provinces', [ShippingController::class, 'getProvinces'])->name('shipping.ghn.provinces');
Route::get('/api/shipping/ghn/districts/{provinceId}', [ShippingController::class, 'getDistricts'])->name('shipping.ghn.districts');
Route::get('/api/shipping/ghn/wards/{districtId}', [ShippingController::class, 'getWards'])->name('shipping.ghn.wards');
Route::post('/api/shipping/ghn/calculate-fee', [ShippingController::class, 'calculateFee'])->name('shipping.ghn.calculateFee');
Route::post('/api/shipping/ghn/webhook', [ShippingController::class, 'webhook'])->name('shipping.ghn.webhook');

// Serve product images from storage/picture (master folder, not stored as DB blobs)
Route::get('/media/picture/{filename}', function (string $filename) {
    // Decode URI-encoded Vietnamese/spaces; never allow path traversal
    $decoded = rawurldecode($filename);
    $safe = basename(str_replace('\\', '/', $decoded));
    $path = storage_path('picture/'.$safe);

    abort_unless($safe !== '' && is_file($path), 404);

    $mime = function_exists('mime_content_type') ? (@mime_content_type($path) ?: null) : null;
    $mime = $mime ?: match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };

    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('filename', '.*')->name('media.picture');

Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang', [CartController::class, 'store'])->name('cart.store');
Route::post('/gio-hang/combo', [CartController::class, 'addBundle'])->name('cart.bundle');
Route::patch('/gio-hang/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/gio-hang', [CartController::class, 'clear'])->name('cart.clear');

// Coupon Codes
Route::post('/ma-giam-gia/ap-dung', [\App\Http\Controllers\CouponController::class, 'apply'])->name('coupon.apply');
Route::post('/ma-giam-gia/huy', [\App\Http\Controllers\CouponController::class, 'remove'])->name('coupon.remove');

// Invoice Printable / PDF
Route::get('/don-hang/{orderCode}/hoa-don', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('orders.invoice');

// Public Payment Callbacks & IPNs
Route::get('/thanh-toan/vnpay/return', [VnpayController::class, 'return'])->name('payments.vnpay.return');
Route::get('/api/payment/vnpay/ipn', [VnpayController::class, 'ipn'])->name('payments.vnpay.ipn');
Route::get('/thanh-toan/momo/return', [MomoController::class, 'return'])->name('payments.momo.return');
Route::post('/api/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payments.momo.ipn');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store'])->name('register.store');

    // Social OAuth (Google, GitHub)
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/dat-hang-thanh-cong/{order:order_code}', [CheckoutController::class, 'success'])->name('checkout.success');

    // Payment Initiation / Retry
    Route::match(['get', 'post'], '/thanh-toan/vnpay/{order:order_code}', [VnpayController::class, 'create'])->name('payments.vnpay.create');
    Route::match(['get', 'post'], '/thanh-toan/momo/{order:order_code}', [MomoController::class, 'create'])->name('payments.momo.create');

    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account.index');
    Route::get('/tai-khoan/chinh-sua', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/tai-khoan', [AccountController::class, 'update'])->name('account.update');
    Route::get('/tai-khoan/don-hang', [OrderHistoryController::class, 'index'])->name('orders.index');
    Route::get('/tai-khoan/don-hang/{order:order_code}', [OrderHistoryController::class, 'show'])->name('orders.show');

    // Product Reviews
    Route::post('/san-pham/{product:slug}/danh-gia', [\App\Http\Controllers\ReviewController::class, 'store'])->name('products.reviews.store');

    // Wishlist
    Route::get('/tai-khoan/yeu-thich', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/yeu-thich/{product}', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

// Guest & Customer Order Tracking
Route::get('/tra-cuu-don-hang', [\App\Http\Controllers\OrderTrackingController::class, 'index'])->name('orders.track');
Route::get('/orders/track', [\App\Http\Controllers\OrderTrackingController::class, 'index']);

// Static Information & Policy Pages
Route::get('/faq', [\App\Http\Controllers\PageController::class, 'faq'])->name('pages.faq');
Route::get('/chinh-sach-bao-hanh', [\App\Http\Controllers\PageController::class, 'warranty'])->name('pages.warranty');
Route::get('/chinh-sach-doi-tra', [\App\Http\Controllers\PageController::class, 'returnPolicy'])->name('pages.return');
Route::get('/lien-he', [\App\Http\Controllers\PageController::class, 'contact'])->name('pages.contact');

// Admin Backoffice
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'));
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('analytics.export');

    // Category Management
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->except(['show']);

    // Product Management & Image Gallery
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class)->except(['show']);
    Route::post('/products/images/{image}/primary', [\App\Http\Controllers\Admin\ProductController::class, 'setPrimaryImage'])->name('products.images.primary');
    Route::delete('/products/images/{image}', [\App\Http\Controllers\Admin\ProductController::class, 'deleteImage'])->name('products.images.destroy');

    // Order Management
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/create-ghn', [ShippingController::class, 'adminCreateGhn'])->name('orders.createGhn');

    // Coupon Management
    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->except(['show']);

    // Customers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::patch('/customers/{customer}/toggle', [\App\Http\Controllers\Admin\CustomerController::class, 'toggle'])->name('customers.toggle');

    // Reviews moderation
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/hide', [\App\Http\Controllers\Admin\ReviewController::class, 'hide'])->name('reviews.hide');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');
});
