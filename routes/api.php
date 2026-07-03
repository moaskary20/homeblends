<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\BundleController;
use App\Http\Controllers\Api\HomeController as ApiHomeController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('auth/social/{provider}/callback', [SocialAuthController::class, 'callback']);

    Route::get('home', ApiHomeController::class);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/{slug}', [ProductController::class, 'show']);
    Route::get('products/{slug}/reviews', [ReviewController::class, 'index']);

    Route::get('bundles', [BundleController::class, 'index']);
    Route::get('bundles/{slug}', [BundleController::class, 'show']);

    Route::get('flash-sales', [FlashSaleController::class, 'index']);
    Route::get('flash-sales/products', [FlashSaleController::class, 'products']);
    Route::get('flash-sales/{slug}', [FlashSaleController::class, 'show']);

    Route::get('payment-gateways', [PaymentGatewayController::class, 'index']);

    Route::get('shipping-rates', [ShippingController::class, 'index']);
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    Route::middleware('shopApiSession')->group(function () {
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/bundles', [CartController::class, 'storeBundle']);
        Route::post('cart/items', [CartController::class, 'store']);
        Route::patch('cart/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('cart/items/{cartItem}', [CartController::class, 'destroy']);
        Route::post('cart/coupon', [CouponController::class, 'apply']);

        Route::get('wishlist', [WishlistController::class, 'show']);
        Route::post('wishlist/{product}/toggle', [WishlistController::class, 'toggle']);
        Route::delete('wishlist/{product}', [WishlistController::class, 'destroy']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::apiResource('addresses', AddressController::class)->except(['show']);
        Route::post('products/{slug}/reviews', [ReviewController::class, 'store']);

        Route::post('cart/save-for-later', [CartController::class, 'saveForLater']);
        Route::post('cart/restore', [CartController::class, 'restoreSaved']);

        Route::post('checkout', [CheckoutController::class, 'store']);
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::get('orders/{id}/tracking', [OrderController::class, 'tracking']);
        Route::get('orders/{id}/invoice', [OrderController::class, 'invoice']);

        Route::get('refunds', [RefundController::class, 'index']);
        Route::post('refunds', [RefundController::class, 'store']);

        Route::get('returns', [ReturnController::class, 'index']);
        Route::post('returns', [ReturnController::class, 'store']);

        Route::get('loyalty/balance', [LoyaltyController::class, 'balance']);
        Route::get('loyalty/history', [LoyaltyController::class, 'history']);
        Route::post('loyalty/preview', [LoyaltyController::class, 'preview']);
        Route::post('loyalty/redeem-wallet', [LoyaltyController::class, 'redeemToWallet']);

        Route::post('affiliate/apply', [AffiliateController::class, 'apply']);
        Route::get('affiliate/dashboard', [AffiliateController::class, 'dashboard'])->middleware('affiliate');
        Route::post('affiliate/payouts', [AffiliateController::class, 'requestPayout'])->middleware('affiliate');
    });
});
