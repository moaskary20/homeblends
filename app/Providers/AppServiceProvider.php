<?php

namespace App\Providers;

use App\Models\BundleItem;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\ProductBundle;
use App\Observers\BundleItemObserver;
use App\Observers\OrderAffiliateObserver;
use App\Observers\PaymentGatewayObserver;
use App\Observers\ProductBundleObserver;
use App\Observers\FlashSaleObserver;
use App\Observers\FlashSaleProductObserver;
use App\Services\Cart\CartService;
use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use App\Services\Settings\SettingsService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale('ar');
        Carbon::setLocale('ar_EG');

        if (Schema::hasTable('settings')) {
            $this->app->make(SettingsService::class)->applyMailConfig();
        }

        FlashSale::observe(FlashSaleObserver::class);
        FlashSaleProduct::observe(FlashSaleProductObserver::class);
        ProductBundle::observe(ProductBundleObserver::class);
        BundleItem::observe(BundleItemObserver::class);

        if (Schema::hasTable('payment_gateways')) {
            PaymentGateway::observe(PaymentGatewayObserver::class);
        }

        Order::observe(OrderAffiliateObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if (! request()->hasSession()) {
                return;
            }

            $sessionId = request()->session()->getId();
            $userId = $event->user->id;

            app(CartService::class)->mergeGuestCart($sessionId, $userId);
            app(WishlistService::class)->mergeGuestToUser($sessionId, $userId);
            app(CompareListService::class)->mergeGuestToUser($sessionId, $userId);
        });
    }
}
