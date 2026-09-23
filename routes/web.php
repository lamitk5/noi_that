<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Payments\MomoController;
use App\Http\Controllers\Payments\VnpayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::post('/store', [CartController::class, 'store'])->name('store');
    Route::match(['post', 'patch', 'put'], '/update/{cartKey}', [CartController::class, 'update'])->name('update');
    Route::match(['post', 'delete'], '/remove/{cartKey}', [CartController::class, 'remove'])->name('remove');
    Route::match(['post', 'delete'], '/destroy/{cartKey}', [CartController::class, 'remove'])->name('destroy');
    Route::match(['post', 'delete'], '/clear', [CartController::class, 'clear'])->name('clear');
});

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'process'])->name('process');
    Route::get('/success/{order_number}', [CheckoutController::class, 'success'])->name('success');
});

Route::prefix('wishlist')->name('wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/toggle/{product}', [WishlistController::class, 'toggle'])->name('toggle');
    Route::delete('/remove/{product}', [WishlistController::class, 'remove'])->name('remove');
});

// GHN
Route::get('/api/shipping/ghn/provinces', [ShippingController::class, 'getProvinces'])->name('shipping.ghn.provinces');
Route::get('/api/shipping/ghn/districts/{provinceId}', [ShippingController::class, 'getDistricts'])->name('shipping.ghn.districts');
Route::get('/api/shipping/ghn/wards/{districtId}', [ShippingController::class, 'getWards'])->name('shipping.ghn.wards');
Route::post('/api/shipping/ghn/calculate-fee', [ShippingController::class, 'calculateFee'])->name('shipping.ghn.calculateFee');
Route::post('/api/shipping/ghn/webhook', [ShippingController::class, 'webhook'])->name('shipping.ghn.webhook');

// Payments
Route::get('/thanh-toan/vnpay/return', [VnpayController::class, 'return'])->name('payments.vnpay.return');
Route::get('/api/payment/vnpay/ipn', [VnpayController::class, 'ipn'])->name('payments.vnpay.ipn');
Route::get('/thanh-toan/momo/return', [MomoController::class, 'return'])->name('payments.momo.return');
Route::post('/api/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payments.momo.ipn');
Route::post('/thanh-toan/vnpay/{orderCode}', [VnpayController::class, 'create'])->name('payments.vnpay.create');
Route::post('/thanh-toan/momo/{orderCode}', [MomoController::class, 'create'])->name('payments.momo.create');

Route::get('/orders/track', [OrderTrackingController::class, 'index'])->name('orders.track');
Route::get('/orders', [OrderTrackingController::class, 'index'])->name('orders.index');
Route::get('/don-hang', [OrderTrackingController::class, 'index'])->name('orders.index.alt');
Route::get('/tai-khoan', [OrderTrackingController::class, 'index'])->name('account.index');
Route::get('/ho-so', [OrderTrackingController::class, 'index'])->name('profile.edit');
Route::get('/tai-khoan/chinh-sua', [OrderTrackingController::class, 'index'])->name('account.edit');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('products', AdminProductController::class);
    Route::post('/products/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage'])->name('products.images.primary');
    Route::delete('/products/images/{image}', [AdminProductController::class, 'deleteImage'])->name('products.images.destroy');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/create-ghn', [ShippingController::class, 'adminCreateGhn'])->name('orders.createGhn');
});
