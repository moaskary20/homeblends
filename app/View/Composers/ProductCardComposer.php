<?php

namespace App\View\Composers;

use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use App\Support\GuestShopContext;
use Illuminate\View\View;

class ProductCardComposer
{
    public function compose(View $view): void
    {
        [$user, $sessionId] = GuestShopContext::resolve(request());

        if (! array_key_exists('wishlistProductIds', $view->getData())) {
            $view->with(
                'wishlistProductIds',
                ($user || $sessionId)
                    ? app(WishlistService::class)->productIds($user, $sessionId)
                    : []
            );
        }

        if (! array_key_exists('compareProductIds', $view->getData())) {
            $view->with(
                'compareProductIds',
                ($user || $sessionId)
                    ? app(CompareListService::class)->productIds($user, $sessionId)
                    : []
            );
        }
    }
}
