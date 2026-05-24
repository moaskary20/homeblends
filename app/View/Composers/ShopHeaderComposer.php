<?php

namespace App\View\Composers;

use App\Services\Cart\CartService;
use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use Illuminate\View\View;

class ShopHeaderComposer
{
    public function compose(View $view): void
    {
        $count = 0;
        $cartPreviewItems = collect();
        $cartSubtotal = 0.0;
        $cartHasMore = false;

        if (request()->hasSession()) {
            try {
                $cartService = app(CartService::class);
                $cart = $cartService->resolveForRequest(request());
                $cart->load(['items.product.images', 'items.variant', 'items.bundle']);
                $totals = $cartService->getTotals($cart);
                $count = (int) $totals['items_count'];
                $cartSubtotal = (float) $totals['subtotal'];
                $cartPreviewItems = $cart->items->take(5);
                $cartHasMore = $cart->items->count() > 5;
            } catch (\Throwable) {
                $count = 0;
            }
        }

        $wishlistCount = 0;
        $wishlistPreviewItems = collect();
        $wishlistHasMore = false;
        $compareCount = 0;
        $comparePreviewItems = collect();

        if (request()->hasSession()) {
            try {
                $user = auth('web')->user();
                $sessionId = request()->session()->getId();
                $wishlist = app(WishlistService::class);
                $wishlistCount = $wishlist->count($user, $sessionId);
                $wishlistPreviewItems = $wishlist->previewProducts($user, $sessionId, 5);
                $wishlistHasMore = $wishlistCount > $wishlistPreviewItems->count();
                $compare = app(CompareListService::class);
                $compareCount = $compare->count($user, $sessionId);
                $comparePreviewItems = $compare->products($user, $sessionId);
            } catch (\Throwable) {
                //
            }
        }

        $view->with([
            'cartItemsCount' => $count,
            'cartPreviewItems' => $cartPreviewItems,
            'cartSubtotal' => $cartSubtotal,
            'cartHasMore' => $cartHasMore,
            'wishlistCount' => $wishlistCount,
            'wishlistPreviewItems' => $wishlistPreviewItems,
            'wishlistHasMore' => $wishlistHasMore,
            'compareCount' => $compareCount,
            'comparePreviewItems' => $comparePreviewItems,
        ]);
    }
}
