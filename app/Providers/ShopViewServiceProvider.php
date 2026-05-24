<?php

namespace App\Providers;

use App\View\Composers\ProductCardComposer;
use App\View\Composers\ShopHeaderComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ShopViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer([
            'shop.partials.header-tiered',
            'shop.partials.mobile-bottom-nav',
        ], ShopHeaderComposer::class);
        View::composer('shop.partials.product-card', ProductCardComposer::class);
    }
}
