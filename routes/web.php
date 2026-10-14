<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminHeroSlideController;
use App\Http\Controllers\AdminSaleBannerController;
use App\Http\Controllers\AdminStoreLocationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class, 'home'])->name('index');
Route::get('/order-success', [PageController::class, 'orderSuccess'])->name('order.success');
Route::get('/product_details/{id}', [UserController::class, 'productDetails'])->name('product_details');
Route::get('/shop', [UserController::class, 'shop'])->name('shop');
Route::get('/contact_us', [UserController::class, 'contactUs'])->name('contact_us');
Route::get('/find-our-stores', [UserController::class, 'storeLocations'])->name('store.locations');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/terms-and-conditions', [PageController::class, 'termsAndConditions'])->name('terms.conditions');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/store-policies', [PageController::class, 'storePolicies'])->name('store.policies');
Route::post('/contact_us', [UserController::class, 'submitResellerInquiry'])
    ->middleware('throttle:10,1')
    ->name('contact_us.submit');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');

Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

Route::get('/dashboard', [UserController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::post('/cart/{id}', [UserController::class, 'addToCart'])
        ->middleware('throttle:60,1')
        ->name('cart.add');
    Route::get('/viewcart', [UserController::class, 'viewCart'])->name('viewcart');
    Route::delete('/cart/{id}', [UserController::class, 'removeCart'])->name('cart.remove');
    Route::patch('/cart/update/{id}', [UserController::class, 'updateCart'])->name('cart.update');
    Route::patch('/cart/{id}/variant', [UserController::class, 'changeVariant'])->name('cart.changeVariant');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])
        ->middleware('throttle:10,1')
        ->name('checkout.placeOrder');
    Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
    Route::delete('/checkout/coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.couponRemove');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

    // GCash payment flow (manual QR + admin verification).
    Route::get('/orders/{order}/pay', [OrderPaymentController::class, 'show'])->name('orders.pay');
    Route::post('/orders/{order}/pay', [OrderPaymentController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('orders.pay.submit');
    Route::get('/orders/{order}/invoice', [OrderPaymentController::class, 'invoice'])->name('orders.invoice');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:5,1')
        ->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/add_category', [AdminController::class, 'addCategory'])->name('admin.addcategory');
    Route::post('/add_category', [AdminController::class, 'postAddCategory'])->name('admin.postaddcategory');
    Route::get('/view_category', [AdminController::class, 'viewCategory'])->name('admin.viewcategory');
    Route::delete('/delete_category/{id}', [AdminController::class, 'deleteCategory'])->name('admin.categorydelete');
    Route::get('/update_category/{id}', [AdminController::class, 'updateCategory'])->name('admin.categoryupdate');
    Route::post('/update_category/{id}', [AdminController::class, 'postUpdatecategory'])->name('admin.postupdatecategory');

    Route::get('/add_product', [AdminController::class, 'addProduct'])->name('admin.addproduct');
    Route::post('/add_product', [AdminController::class, 'postAddProduct'])->name('admin.postaddproduct');
    Route::get('/view_product', [AdminController::class, 'viewProduct'])->name('admin.viewproduct');
    Route::delete('/deleteproduct/{id}', [AdminController::class, 'deleteProduct'])->name('admin.deleteproduct');
    Route::get('/updateproduct/{id}', [AdminController::class, 'updateProduct'])->name('admin.updateproduct');
    Route::post('/update_product/{id}', [AdminController::class, 'postUpdateProduct'])->name('admin.postupdateproduct');
    Route::match(['get', 'post'], '/search', [AdminController::class, 'searchProduct'])->name('admin.searchproduct');

    Route::get('/view_orders', [AdminController::class, 'viewOrders'])->name('admin.vieworders');
    Route::patch('/view_orders/{id}/status', [AdminController::class, 'updateStatus'])->name('admin.order.updateStatus');
    Route::delete('/view_orders/{id}', [AdminController::class, 'deleteOrder'])->name('admin.order.delete');

    // GCash payment verification queue.
    Route::get('/admin/payments', [PaymentController::class, 'index'])->name('admin.payments');
    Route::post('/admin/payments/{order}/verify', [PaymentController::class, 'verify'])->name('admin.payments.verify');
    Route::post('/admin/payments/{order}/reject', [PaymentController::class, 'reject'])->name('admin.payments.reject');

    // Transaction ledger.
    Route::get('/admin/transactions', [TransactionController::class, 'index'])->name('admin.transactions');
    Route::get('/admin/transactions/export', [TransactionController::class, 'export'])->name('admin.transactions.export');

    // User management.
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');

    // GCash payment settings.
    Route::get('/admin/settings', [SettingController::class, 'edit'])->name('admin.settings');
    Route::post('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    Route::resource('admin/hero-slides', AdminHeroSlideController::class)
        ->names('admin.hero-slides');

    Route::resource('admin/sale-banners', AdminSaleBannerController::class)
        ->names('admin.sale-banners');

    Route::resource('admin/store-locations', AdminStoreLocationController::class)
        ->names('admin.store-locations');

    Route::resource('admin/coupons', CouponController::class)
        ->names('admin.coupons')
        ->middleware('throttle:30,1');

    Route::prefix('admin/inventory')->name('admin.inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/{id}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::get('/history/{id}', [InventoryController::class, 'history'])->name('history');
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('lowStock');
    });

    Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'salesByDate'])->name('sales');
        Route::get('/export/csv', [ReportController::class, 'exportSalesCsv'])->name('exportCsv');
    });
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/toggle/{id}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reviews.store');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('reviews.destroy');
});

require __DIR__.'/auth.php';
