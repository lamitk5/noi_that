<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Payments\MomoController;
use App\Http\Controllers\Payments\VnpayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomMixController;
use App\Http\Controllers\VisualSearchController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/tim-bang-anh', [VisualSearchController::class, 'index'])->name('products.visual');
Route::post('/tim-bang-anh', [VisualSearchController::class, 'search'])->middleware('throttle:ai-chat')->name('products.visual.search');
Route::get('/phoi-combo', [RoomMixController::class, 'show'])->name('rooms.mix');
Route::post('/phoi-combo', [RoomMixController::class, 'save'])->name('rooms.mix.save');
Route::post('/phoi-combo/mua', [RoomMixController::class, 'buy'])->name('rooms.mix.buy');
Route::get('/bao-gia', [QuotationController::class, 'create'])->name('quotes.create');
Route::post('/bao-gia', [QuotationController::class, 'store'])->name('quotes.store');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('reviews.store');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::post('/store', [CartController::class, 'store'])->name('store');
    Route::match(['post', 'patch', 'put'], '/update/{cartKey}', [CartController::class, 'update'])->name('update');
    Route::match(['post', 'delete'], '/remove/{cartKey}', [CartController::class, 'remove'])->name('remove');
    Route::match(['post', 'delete'], '/destroy/{cartKey}', [CartController::class, 'remove'])->name('destroy');
    Route::match(['post', 'delete'], '/clear', [CartController::class, 'clear'])->name('clear');
    Route::post('/select', [CartController::class, 'select'])->name('select');
    Route::post('/checkout', [CartController::class, 'checkoutSelected'])->name('checkout');
    Route::post('/coupon/apply', [CartController::class, 'applyCoupon'])->name('coupon.apply');
    Route::match(['post', 'delete'], '/coupon/remove', [CartController::class, 'removeCoupon'])->name('coupon.remove');
});

Route::post('/coupon/apply', [CartController::class, 'applyCoupon'])->name('coupon.apply.direct');
Route::match(['post', 'delete'], '/coupon/remove', [CartController::class, 'removeCoupon'])->name('coupon.remove.direct');

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::match(['post', 'put', 'patch'], '/', [CheckoutController::class, 'process'])->name('process');
    Route::match(['post', 'put', 'patch'], '/store', [CheckoutController::class, 'process'])->name('store');
    Route::get('/success/{order_number}', [CheckoutController::class, 'success'])->name('success');
});

Route::prefix('wishlist')->name('wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/toggle/{product}', [WishlistController::class, 'toggle'])->name('toggle');
    Route::match(['post', 'delete'], '/remove/{product}', [WishlistController::class, 'remove'])->name('remove');
});

// Chat với nhân viên (đăng nhập)
Route::prefix('api/chat')->name('chat.')->middleware('auth')->group(function () {
    Route::get('/session', [ChatController::class, 'session'])->name('session');
    Route::get('/{chatId}/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/{chatId}/message', [ChatController::class, 'send'])->name('send');
});

// Trợ lý AI tư vấn sản phẩm (mở cho cả khách chưa đăng nhập)
Route::prefix('api/ai-chat')->name('ai-chat.')->middleware('throttle:ai-chat')->group(function () {
    Route::get('/', [AiChatController::class, 'history'])->name('history');
    Route::post('/', [AiChatController::class, 'send'])->name('send');
    Route::post('/reset', [AiChatController::class, 'reset'])->name('reset');
});

// GHN
Route::get('/api/shipping/ghn/provinces', [ShippingController::class, 'getProvinces'])->name('shipping.ghn.provinces');
Route::get('/api/shipping/ghn/districts/{provinceId}', [ShippingController::class, 'getDistricts'])->name('shipping.ghn.districts');
Route::get('/api/shipping/ghn/wards/{districtId}', [ShippingController::class, 'getWards'])->name('shipping.ghn.wards');
Route::post('/api/shipping/ghn/calculate-fee', [ShippingController::class, 'calculateFee'])->name('shipping.ghn.calculateFee');
Route::post('/api/shipping/ghn/webhook', [ShippingController::class, 'webhook'])->name('shipping.ghn.webhook');

// Serve product images from storage/picture (master folder, not stored as DB blobs)
Route::get('/media/picture/{filename}', function (string $filename) {
    $decoded = rawurldecode($filename);
    $safe = basename(str_replace('\\', '/', $decoded));
    $path = storage_path('picture/'.$safe);

    abort_unless($safe !== '' && is_file($path), 404);

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'glb' => 'model/gltf-binary',
        'usdz' => 'model/vnd.usdz+zip',
        default => (function_exists('mime_content_type') ? (@mime_content_type($path) ?: null) : null) ?: 'application/octet-stream',
    };

    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('filename', '.*')->name('media.picture');

// Payment gateways
// NOTE: return/ipn routes MUST be declared before {orderCode}, otherwise
// /thanh-toan/vnpay/return is captured as orderCode="return".
Route::get('/thanh-toan/vnpay/return', [VnpayController::class, 'return'])->name('payments.vnpay.return');
Route::get('/api/payment/vnpay/ipn', [VnpayController::class, 'ipn'])->name('payments.vnpay.ipn');
Route::get('/thanh-toan/momo/return', [MomoController::class, 'return'])->name('payments.momo.return');
Route::post('/api/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payments.momo.ipn');
Route::match(['get', 'post'], '/thanh-toan/vnpay/{orderCode}', [VnpayController::class, 'create'])->name('payments.vnpay.create');
Route::match(['get', 'post'], '/thanh-toan/momo/{orderCode}', [MomoController::class, 'create'])->name('payments.momo.create');

// Dev mock payment routes (chỉ hoạt động khi MOMO_MOCK=true / VNPAY_MOCK=true)
Route::get('/thanh-toan/momo/mock/confirm', [MomoController::class, 'mockConfirm'])->name('payments.momo.mock');
Route::get('/thanh-toan/vnpay/mock/confirm', [VnpayController::class, 'mockConfirm'])->name('payments.vnpay.mock');

use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

Route::get('/orders/track', [OrderTrackingController::class, 'index'])->name('orders.track');
Route::get('/orders/{orderCode}/invoice', [InvoiceController::class, 'show'])->name('orders.invoice');

Route::get('/lien-he', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/cau-hoi-thuong-gap', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/chinh-sach-bao-hanh', [PageController::class, 'warranty'])->name('pages.warranty');
Route::get('/chinh-sach-doi-tra', [PageController::class, 'returnPolicy'])->name('pages.return');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderCode}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderCode}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{orderCode}/reorder', [CustomerOrderController::class, 'reorder'])->name('orders.reorder');
    Route::get('/don-hang', [CustomerOrderController::class, 'index'])->name('orders.index.alt');
    Route::get('/don-hang/{orderCode}', [CustomerOrderController::class, 'show'])->name('orders.show.alt');
    Route::get('/tai-khoan', [CustomerOrderController::class, 'index'])->name('account.index');
    Route::get('/ho-so', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/ho-so', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ho-so/xac-thuc-mat-khau', [\App\Http\Controllers\ProfileController::class, 'showPasswordVerify'])->name('profile.password.verify');
    Route::post('/ho-so/xac-thuc-mat-khau', [\App\Http\Controllers\ProfileController::class, 'verifyPasswordChange'])->name('profile.password.verify.submit');
    Route::post('/ho-so/xac-thuc-mat-khau/gui-lai', [\App\Http\Controllers\ProfileController::class, 'resendPasswordCode'])->name('profile.password.resend');
    Route::get('/tai-khoan/chinh-sua', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('account.edit');
    Route::match(['put', 'patch'], '/tai-khoan/chinh-sua', [\App\Http\Controllers\ProfileController::class, 'update'])->name('account.update');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/register/store', [AuthController::class, 'register'])->name('register.store');

    Route::get('/quen-mat-khau', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'sendCode'])->name('password.email')->middleware('throttle:password-reset');
    Route::get('/quen-mat-khau/xac-thuc', [PasswordResetController::class, 'showVerify'])->name('password.verify');
    Route::post('/quen-mat-khau/xac-thuc', [PasswordResetController::class, 'verifyCode'])->name('password.verify.submit')->middleware('throttle:password-reset');
    Route::post('/quen-mat-khau/gui-lai', [PasswordResetController::class, 'resend'])->name('password.resend')->middleware('throttle:password-reset');
    Route::get('/dat-lai-mat-khau', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'reset'])->name('password.update');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');
});

Route::get('/xac-thuc', [AuthController::class, 'showVerify'])->name('auth.verify');
Route::post('/xac-thuc', [AuthController::class, 'verify'])->name('auth.verify.submit')->middleware('throttle:10,1');
Route::post('/xac-thuc/gui-lai', [AuthController::class, 'resendVerify'])->name('auth.verify.resend')->middleware('throttle:5,1');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'));
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index')->middleware('adminonly');
    Route::get('/analytics/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('analytics.export')->middleware('adminonly');

    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::post('/products/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage'])->name('products.images.primary');
    Route::delete('/products/images/{image}', [AdminProductController::class, 'deleteImage'])->name('products.images.destroy');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/create-ghn', [ShippingController::class, 'adminCreateGhn'])->name('orders.createGhn');

    // Quản lý Tài chính & Giao dịch thanh toán (Lab 9)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finance/transactions', [FinanceController::class, 'transactions'])->name('finance.transactions');
    Route::patch('/finance/{order}/status', [FinanceController::class, 'updateStatus'])->name('finance.update-status');

    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->except(['show']);

    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}/edit', [\App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::patch('/customers/{customer}/toggle', [\App\Http\Controllers\Admin\CustomerController::class, 'toggle'])->name('customers.toggle');

    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/hide', [\App\Http\Controllers\Admin\ReviewController::class, 'hide'])->name('reviews.hide');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Trò chuyện khách hàng
    Route::get('/chats', [\App\Http\Controllers\Admin\ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/unread', [\App\Http\Controllers\Admin\ChatController::class, 'unread'])->name('chats.unread');
    Route::get('/chats/{chat}', [\App\Http\Controllers\Admin\ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/reply', [\App\Http\Controllers\Admin\ChatController::class, 'reply'])->name('chats.reply');
    Route::post('/chats/{chat}/close', [\App\Http\Controllers\Admin\ChatController::class, 'close'])->name('chats.close');
    Route::post('/chats/{chat}/reopen', [\App\Http\Controllers\Admin\ChatController::class, 'reopen'])->name('chats.reopen');
    Route::post('/chats/{chat}/assign', [\App\Http\Controllers\Admin\ChatController::class, 'assign'])->name('chats.assign');
    Route::post('/chats/{chat}/claim', [\App\Http\Controllers\Admin\ChatController::class, 'claim'])->name('chats.claim');

    // Quản lý nhân viên (chỉ Quản trị viên)
    Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class)->except(['show'])->middleware('adminonly');
    Route::patch('/staff/{staff}/toggle', [\App\Http\Controllers\Admin\StaffController::class, 'toggle'])->name('staff.toggle')->middleware('adminonly');
});
