<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Shop\CompareListService;
use App\Support\GuestShopContext;
use App\Support\ProductCompareBuilder;
use App\Services\Shop\WishlistService;
use App\Support\ProductMedia;
use Illuminate\Http\Request;

class WishlistCompareController extends Controller
{
    public function toggleWishlist(Request $request, Product $product, WishlistService $wishlist)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $added = $wishlist->toggle($user, $sessionId, $product);

        return response()->json([
            'added' => $added,
            'count' => $wishlist->count($user, $sessionId),
            'session_id' => $sessionId,
        ]);
    }

    public function removeWishlist(Request $request, Product $product, WishlistService $wishlist)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $wishlist->remove($user, $sessionId, $product);

        return response()->json([
            'count' => $wishlist->count($user, $sessionId),
            'session_id' => $sessionId,
        ]);
    }

    public function wishlistPreview(Request $request, WishlistService $wishlist)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            GuestShopContext::requireGuestSessionId($sessionId);
        }
        $preview = $wishlist->previewProducts($user, $sessionId, 5);
        $count = $wishlist->count($user, $sessionId);

        return response()->json([
            'count' => $count,
            'has_more' => $count > $preview->count(),
            'items' => $preview->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumb' => ProductMedia::productThumbnail($product),
                'price' => (float) $product->effective_price,
                'url' => route('shop.products.show', $product->slug),
                'remove_url' => url('/api/v1/wishlist/'.$product->id),
            ])->values(),
        ]);
    }

    public function toggleCompare(Request $request, Product $product, CompareListService $compare)
    {
        try {
            [$user, $sessionId] = GuestShopContext::resolve($request);

            if (! $user) {
                $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
            }

            $added = $compare->toggle($user, $sessionId, $product);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'added' => $added,
            'count' => $compare->count($user, $sessionId),
        ]);
    }

    public function removeCompare(Request $request, Product $product, CompareListService $compare)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $compare->remove($user, $sessionId, $product);

        return response()->json([
            'count' => $compare->count($user, $sessionId),
            'session_id' => $sessionId,
        ]);
    }

    public function comparePage(Request $request, CompareListService $compare, ProductCompareBuilder $builder)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);
        $built = $builder->build($compare->products($user, $sessionId));

        return view('shop.compare', [
            'products' => $built['products'],
            'rows' => $built['rows'],
            'maxItems' => $compare->maxItems(),
            'seo' => app(\App\Services\Seo\SeoService::class)->forPrivatePage(__('ecommerce.my_compare')),
        ]);
    }

    public function clearCompare(Request $request, CompareListService $compare)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $compare->clear($user, $sessionId);

        return redirect()->route('shop.compare')->with('success', __('ecommerce.compare_cleared'));
    }
}
