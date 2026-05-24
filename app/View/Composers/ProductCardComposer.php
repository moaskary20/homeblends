<?php

namespace App\View\Composers;

use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use Illuminate\View\View;

class ProductCardComposer
{
    /** @var array<int, int>|null */
    protected static ?array $wishlistProductIds = null;

    /** @var array<int, int>|null */
    protected static ?array $compareProductIds = null;

    public function compose(View $view): void
    {
        if (! array_key_exists('wishlistProductIds', $view->getData())) {
            if (static::$wishlistProductIds === null) {
                static::$wishlistProductIds = auth()->check()
                    ? app(WishlistService::class)->productIds(auth()->user())
                    : [];
            }

            $view->with('wishlistProductIds', static::$wishlistProductIds);
        }

        if (! array_key_exists('compareProductIds', $view->getData())) {
            if (static::$compareProductIds === null) {
                static::$compareProductIds = auth()->check()
                    ? app(CompareListService::class)->productIds(auth()->user())
                    : [];
            }

            $view->with('compareProductIds', static::$compareProductIds);
        }
    }
}
