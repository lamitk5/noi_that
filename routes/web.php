<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\Payments\MomoController;
use App\Http\Controllers\Payments\VnpayController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductFeatureController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Storefront Home & Catalog
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/san-pham', [ProductController::class, 'index'])->name('products.index');
Route::get('/san-pham/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Product Features & Smart Search
Route::get('/so-sanh', [ProductFeatureController::class, 'compare'])->name('products.compare');
Route::get('/api/products/compare', [ProductFeatureController::class, 'compareData'])->name('products.compare.data');
Route::get('/api/products/{product}/quick-view', [ProductFeatureController::class, 'quickView'])->name('products.quick-view');
Route::get('/api/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

// Shopping Cart
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::post('/gio-hang', [CartController::class, 'store'])->name('cart.store');
Route::post('/api/cart/quick-add', [CartController::class, 'quickAdd'])->name('cart.quick-add');
Route::patch('/gio-hang/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/gio-hang/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::delete('/gio-hang', [CartController::class, 'clear'])->name('cart.clear');

// Public Payment Callbacks & IPNs
Route::get('/thanh-toan/vnpay/return', [VnpayController::class, 'return'])->name('payments.vnpay.return');
Route::get('/api/payment/vnpay/ipn', [VnpayController::class, 'ipn'])->name('payments.vnpay.ipn');
Route::get('/thanh-toan/momo/return', [MomoController::class, 'return'])->name('payments.momo.return');
Route::post('/api/payment/momo/ipn', [MomoController::class, 'ipn'])->name('payments.momo.ipn');

// Storefront FAQ
Route::get('/cau-hoi-thuong-gap', [FaqController::class, 'index'])->name('faq.index');

// Storefront Blog / Posts
Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/{slug}', [PostController::class, 'show'])->name('posts.show');

// Storefront Contact & Inquiries
Route::get('/lien-he', [CmsPageController::class, 'contact'])->name('pages.contact');
Route::post('/lien-he', [CmsPageController::class, 'submitContact'])->name('pages.contact.store');

// Storefront CMS Static Pages (Specific shortcuts & generic fallback)
Route::get('/gioi-thieu', [CmsPageController::class, 'show'])->defaults('slug', 'gioi-thieu')->name('pages.about');
Route::get('/chinh-sach-mua-hang', [CmsPageController::class, 'show'])->defaults('slug', 'chinh-sach-mua-hang')->name('pages.purchase-policy');
Route::get('/chinh-sach-bao-hanh', [CmsPageController::class, 'show'])->defaults('slug', 'chinh-sach-bao-hanh')->name('pages.warranty-policy');
Route::get('/chinh-sach-doi-tra', [CmsPageController::class, 'show'])->defaults('slug', 'chinh-sach-doi-tra')->name('pages.return-policy');
Route::get('/trang/{slug}', [CmsPageController::class, 'show'])->name('pages.show');

// Guest Authentication & Social Login
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store'])->name('register.store');

    // Social Authentication (Google, Facebook)
    Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('auth.social.callback');
});

// Authenticated Customer Area
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

    // Customer Personal Appearance Settings
    Route::get('/tai-khoan/giao-dien', [AccountController::class, 'appearance'])->name('account.appearance');
    Route::post('/tai-khoan/giao-dien', [AccountController::class, 'updateAppearance'])->name('account.appearance.update');
    Route::post('/tai-khoan/giao-dien/khoi-phuc', [AccountController::class, 'resetAppearance'])->name('account.appearance.reset');

    // Customer Address Book
    Route::get('/tai-khoan/dia-chi', [UserAddressController::class, 'index'])->name('account.addresses.index');
    Route::post('/tai-khoan/dia-chi', [UserAddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/tai-khoan/dia-chi/{address}', [UserAddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/tai-khoan/dia-chi/{address}', [UserAddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::post('/tai-khoan/dia-chi/{address}/mac-dinh', [UserAddressController::class, 'setDefault'])->name('account.addresses.set-default');

    // Order History & Actions
    Route::get('/tai-khoan/don-hang', [OrderHistoryController::class, 'index'])->name('orders.index');
    Route::get('/tai-khoan/don-hang/{order:order_code}', [OrderHistoryController::class, 'show'])->name('orders.show');
    Route::post('/tai-khoan/don-hang/{order:order_code}/mua-lai', [OrderHistoryController::class, 'buyAgain'])->name('orders.buy-again');
    Route::post('/tai-khoan/don-hang/{order:order_code}/huy', [OrderHistoryController::class, 'cancel'])->name('orders.cancel');
    Route::get('/tai-khoan/don-hang/{order:order_code}/in', [OrderHistoryController::class, 'print'])->name('orders.print');
    Route::post('/tai-khoan/don-hang/{order:order_code}/thanh-toan-lai', [OrderHistoryController::class, 'retryPayment'])->name('orders.retry-payment');

    // Customer Support Tickets
    Route::get('/tai-khoan/ho-tro', [TicketController::class, 'index'])->name('account.tickets.index');
    Route::get('/tai-khoan/ho-tro/tao-moi', [TicketController::class, 'create'])->name('account.tickets.create');
    Route::post('/tai-khoan/ho-tro', [TicketController::class, 'store'])->name('account.tickets.store');
    Route::get('/tai-khoan/ho-tro/{ticket:ticket_code}', [TicketController::class, 'show'])->name('account.tickets.show');
    Route::post('/tai-khoan/ho-tro/{ticket:ticket_code}/phan-hoi', [TicketController::class, 'reply'])->name('account.tickets.reply');

    // Wishlist
    Route::get('/tai-khoan/yeu-thich', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/san-pham/{product:slug}/yeu-thich', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/tai-khoan/yeu-thich/{product:slug}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Loyalty Points & Rewards
    Route::get('/tai-khoan/diem-thuong', [AccountController::class, 'loyalty'])->name('account.loyalty');

    // Notifications
    Route::get('/tai-khoan/thong-bao', [NotificationController::class, 'index'])->name('account.notifications');
    Route::patch('/tai-khoan/thong-bao-tat-ca', [NotificationController::class, 'markAllAsRead'])->name('account.notifications.read-all');
    Route::patch('/tai-khoan/thong-bao/{id}', [NotificationController::class, 'markAsRead'])->name('account.notifications.read');

    // Checkout Voucher & Loyalty Points
    Route::post('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'applyVoucher'])->name('checkout.apply-voucher');
    Route::delete('/thanh-toan/ma-giam-gia', [CheckoutController::class, 'removeVoucher'])->name('checkout.remove-voucher');
    Route::post('/thanh-toan/diem-thuong', [CheckoutController::class, 'applyPoints'])->name('checkout.apply-points');
    Route::delete('/thanh-toan/diem-thuong', [CheckoutController::class, 'removePoints'])->name('checkout.remove-points');

    // Product Reviews
    Route::post('/san-pham/{product:slug}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/danh-gia/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

// Admin Dashboard & Backoffice
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

    // Support Tickets (Customer Inquiries)
    Route::get('ho-tro', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('ho-tro/{ticket:ticket_code}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::patch('ho-tro/{ticket:ticket_code}/trang-thai', [\App\Http\Controllers\Admin\TicketController::class, 'updateStatus'])->name('tickets.update-status');
    Route::post('ho-tro/{ticket:ticket_code}/phan-hoi', [\App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('tickets.reply');

    // FAQs
    Route::resource('cau-hoi-thuong-gap', \App\Http\Controllers\Admin\FaqController::class)
        ->parameters(['cau-hoi-thuong-gap' => 'faq'])
        ->names('faqs');

    // CMS Pages
    Route::resource('trang-tinh', \App\Http\Controllers\Admin\CmsPageController::class)
        ->parameters(['trang-tinh' => 'page'])
        ->names('pages');

    // Blog / Posts
    Route::resource('bai-viet', \App\Http\Controllers\Admin\PostController::class)
        ->parameters(['bai-viet' => 'post'])
        ->names('posts');

    // Website Appearance Customizer
    Route::get('cai-dat/giao-dien', [\App\Http\Controllers\Admin\AppearanceController::class, 'index'])->name('appearance.index');
    Route::post('cai-dat/giao-dien', [\App\Http\Controllers\Admin\AppearanceController::class, 'update'])->name('appearance.update');
    Route::post('cai-dat/giao-dien/khoi-phuc', [\App\Http\Controllers\Admin\AppearanceController::class, 'resetDefaults'])->name('appearance.reset');
});
