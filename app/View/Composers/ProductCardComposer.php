<?php

namespace App\View\Composers;

use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use Illuminate\View\View;

class ProductCardComposer
{
    /** @var array<string, array<int, int>> */
    protected static array $wishlistProductIdsByKey = [];

    /** @var array<string, array<int, int>> */
    protected static array $compareProductIdsByKey = [];

    public function compose(View $view): void
    {
        $sessionId = request()->hasSession() ? request()->session()->getId() : null;
        $userId = auth()->id();
        $cacheKey = ($userId ? 'u:'.$userId : 'g:').($sessionId ?? '');

        if (! array_key_exists('wishlistProductIds', $view->getData())) {
            if (! isset(static::$wishlistProductIdsByKey[$cacheKey])) {
                static::$wishlistProductIdsByKey[$cacheKey] = app(WishlistService::class)
                    ->productIds(auth()->user(), $sessionId);
            }

            $view->with('wishlistProductIds', static::$wishlistProductIdsByKey[$cacheKey]);
        }

        if (! array_key_exists('compareProductIds', $view->getData())) {
            if (! isset(static::$compareProductIdsByKey[$cacheKey])) {
                static::$compareProductIdsByKey[$cacheKey] = app(CompareListService::class)
                    ->productIds(auth()->user(), $sessionId);
            }

            $view->with('compareProductIds', static::$compareProductIdsByKey[$cacheKey]);
        }
    }
}
