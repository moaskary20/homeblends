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
        $user = auth('web')->user();
        $sessionId = request()->hasSession() ? request()->session()->getId() : null;

        if (! $user && (! is_string($sessionId) || $sessionId === '')) {
            $view->with('wishlistProductIds', []);
            $view->with('compareProductIds', []);

            return;
        }

        $cacheKey = ($user ? 'u:'.$user->id : 'g:'.$sessionId);

        if (! array_key_exists('wishlistProductIds', $view->getData())) {
            if (! isset(static::$wishlistProductIdsByKey[$cacheKey])) {
                static::$wishlistProductIdsByKey[$cacheKey] = app(WishlistService::class)
                    ->productIds($user, $sessionId);
            }

            $view->with('wishlistProductIds', static::$wishlistProductIdsByKey[$cacheKey]);
        }

        if (! array_key_exists('compareProductIds', $view->getData())) {
            if (! isset(static::$compareProductIdsByKey[$cacheKey])) {
                static::$compareProductIdsByKey[$cacheKey] = app(CompareListService::class)
                    ->productIds($user, $sessionId);
            }

            $view->with('compareProductIds', static::$compareProductIdsByKey[$cacheKey]);
        }
    }
}
