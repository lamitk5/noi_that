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

    // Wishlist
    Route::get('/tai-khoan/yeu-thich', [\App\Http\Controllers\WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/san-pham/{product:slug}/yeu-thich', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/tai-khoan/yeu-thich/{product:slug}', [\App\Http\Controllers\WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Loyalty Points & Rewards
    Route::get('/tai-khoan/diem-thuong', [AccountController::class, 'loyalty'])->name('account.loyalty');

    // Notifications
    Route::get('/tai-khoan/thong-bao', [\App\Http\Controllers\NotificationController::class, 'index'])->name('account.notifications');
    Route::patch('/tai-khoan/thong-bao-tat-ca', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('account.notifications.read-all');
    Route::patch('/tai-khoan/thong-bao/{id}', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('account.notifications.read');

    // Checkout Voucher & Loyalty Points
    Route::post('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'applyVoucher'])->name('checkout.apply-voucher');
    Route::delete('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'removeVoucher'])->name('checkout.remove-voucher');
    Route::post('/thanh-toan/diem-thuong', [CheckoutController::class, 'applyPoints'])->name('checkout.apply-points');
    Route::delete('/thanh-toan/diem-thuong', [CheckoutController::class, 'removePoints'])->name('checkout.remove-points');

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

    // Categories
    Route::resource('danh-muc', \App\Http\Controllers\Admin\CategoryController::class)
        ->parameters(['danh-muc' => 'category'])
        ->names('categories');

    // Products
    Route::patch('san-pham/{product}/chuyen-trang-thai', [\App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::resource('san-pham', \App\Http\Controllers\Admin\ProductController::class)
        ->parameters(['san-pham' => 'product'])
        ->names('products');

    // Product Variants
    Route::post('san-pham/{product}/bien-the', [\App\Http\Controllers\Admin\ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::put('san-pham/{product}/bien-the/{variant}', [\App\Http\Controllers\Admin\ProductVariantController::class, 'update'])->name('products.variants.update');
    Route::delete('san-pham/{product}/bien-the/{variant}', [\App\Http\Controllers\Admin\ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

    // Product Images
    Route::post('san-pham/{product}/hinh-anh', [\App\Http\Controllers\Admin\ProductImageController::class, 'store'])->name('products.images.store');
    Route::patch('san-pham/{product}/hinh-anh/{image}/chinh', [\App\Http\Controllers\Admin\ProductImageController::class, 'setPrimary'])->name('products.images.primary');
    Route::delete('san-pham/{product}/hinh-anh/{image}', [\App\Http\Controllers\Admin\ProductImageController::class, 'destroy'])->name('products.images.destroy');

    // Vouchers / Promotions
    Route::patch('khuyen-mai/{voucher}/chuyen-trang-thai', [\App\Http\Controllers\Admin\VoucherController::class, 'toggleStatus'])->name('vouchers.toggle-status');
    Route::resource('khuyen-mai', \App\Http\Controllers\Admin\VoucherController::class)
        ->parameters(['khuyen-mai' => 'voucher'])
        ->names('vouchers');

    // Inventory
    Route::get('ton-kho', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::patch('ton-kho/{variant}', [\App\Http\Controllers\Admin\InventoryController::class, 'update'])->name('inventory.update');

    // Reports & Analytics
    Route::get('bao-cao', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
});
