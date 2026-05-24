<?php

use App\Http\Controllers\Media\ImageThumbController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController as ShopOrderController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\AboutController;
use App\Http\Controllers\Shop\DesignTeamController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\AffiliateController;
use App\Http\Controllers\Shop\BundleController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\ProductController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/media/{width}/{path}', ImageThumbController::class)
    ->where('path', '.*')
    ->whereNumber('width')
    ->name('media.thumb');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', HomeController::class)->name('shop.home');
    Route::get('/bundles', [BundleController::class, 'index'])->name('shop.bundles.index');
    Route::get('/bundles/{slug}', [BundleController::class, 'show'])->name('shop.bundles.show');
    Route::get('/categories', [CategoryController::class, 'index'])->name('shop.categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('shop.categories.show');
    Route::get('/products', [ProductController::class, 'index'])->name('shop.products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('shop.products.show');
    Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
    Route::get('/checkout', CheckoutController::class)->name('shop.checkout');
    Route::get('/orders', [ShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/orders/{orderNumber}', [ShopOrderController::class, 'show'])->name('shop.orders.show');

    Route::middleware('auth')->prefix('account')->name('shop.account.')->group(function () {
        Route::get('/', [AccountController::class, 'profile'])->name('profile');
        Route::put('/', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
        Route::post('/avatar', [AccountController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [AccountController::class, 'removeAvatar'])->name('avatar.remove');
        Route::get('/purchases', [AccountController::class, 'purchases'])->name('purchases');
        Route::get('/points', [AccountController::class, 'points'])->name('points');
        Route::post('/points/redeem', [AccountController::class, 'redeemPoints'])->name('points.redeem');
        Route::get('/tracking', [AccountController::class, 'tracking'])->name('tracking');
        Route::get('/favorites', [AccountController::class, 'favorites'])->name('favorites');
        Route::get('/favorites/preview', [AccountController::class, 'favoritesPreview'])->name('favorites.preview');
        Route::post('/favorites/{product}', [AccountController::class, 'toggleFavorite'])->name('favorites.toggle');
        Route::delete('/favorites/{product}', [AccountController::class, 'removeFavorite'])->name('favorites.remove');
        Route::get('/compare', [AccountController::class, 'compare'])->name('compare');
        Route::post('/compare/{product}', [AccountController::class, 'toggleCompare'])->name('compare.toggle');
        Route::delete('/compare/{product}', [AccountController::class, 'removeCompare'])->name('compare.remove');
        Route::delete('/compare', [AccountController::class, 'clearCompare'])->name('compare.clear');
    });
    Route::get('/about', AboutController::class)->name('shop.about');
    Route::get('/design-team', DesignTeamController::class)->name('shop.design-team');
    Route::get('/contact', [ContactController::class, 'index'])->name('shop.contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('shop.contact.store')->middleware('throttle:6,1');
    Route::get('/affiliate-program', [AffiliateController::class, 'index'])->name('shop.affiliate.index');
    Route::post('/affiliate-program/apply', [AffiliateController::class, 'apply'])->name('shop.affiliate.apply')->middleware('auth');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
