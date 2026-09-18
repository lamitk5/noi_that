<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\Payments\MomoController;
use App\Http\Controllers\Payments\VnpayController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang', [CartController::class, 'store'])->name('cart.store');
Route::patch('/gio-hang/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/gio-hang', [CartController::class, 'clear'])->name('cart.clear');

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
});

Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/dat-hang-thanh-cong/{order:order_code}', [CheckoutController::class, 'success'])->name('checkout.success');

    // Payment Initiation / Retry
    Route::post('/thanh-toan/vnpay/{order:order_code}', [VnpayController::class, 'create'])->name('payments.vnpay.create');
    Route::post('/thanh-toan/momo/{order:order_code}', [MomoController::class, 'create'])->name('payments.momo.create');

    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('account.index');
    Route::get('/tai-khoan/chinh-sua', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/tai-khoan', [AccountController::class, 'update'])->name('account.update');
    Route::get('/tai-khoan/don-hang', [OrderHistoryController::class, 'index'])->name('orders.index');
    Route::get('/tai-khoan/don-hang/{order:order_code}', [OrderHistoryController::class, 'show'])->name('orders.show');

    // Product Reviews
    Route::post('/san-pham/{product:slug}/danh-gia', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/danh-gia/{review}', [\App\Http\Controllers\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/danh-gia/{review}', [\App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/don-hang', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/{order:order_code}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::match(['post', 'patch'], '/don-hang/{order:order_code}/trang-thai', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::match(['post', 'patch'], '/don-hang/{order:order_code}/xac-nhan-thanh-toan', [\App\Http\Controllers\Admin\OrderController::class, 'markPaid'])->name('orders.mark-paid');
});
