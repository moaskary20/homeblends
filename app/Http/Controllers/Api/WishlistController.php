<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Shop\WishlistService;
use App\Support\GuestShopContext;
use App\Support\ProductMedia;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlist) {}

    public function show(Request $request)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            GuestShopContext::requireGuestSessionId($sessionId);
        }

        $preview = $this->wishlist->previewProducts($user, $sessionId, 5);
        $count = $this->wishlist->count($user, $sessionId);

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

    public function toggle(Request $request, Product $product)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $added = $this->wishlist->toggle($user, $sessionId, $product);

        return response()->json([
            'added' => $added,
            'count' => $this->wishlist->count($user, $sessionId),
            'session_id' => $sessionId,
        ]);
    }

    public function destroy(Request $request, Product $product)
    {
        [$user, $sessionId] = GuestShopContext::resolve($request);

        if (! $user) {
            $sessionId = GuestShopContext::requireGuestSessionId($sessionId);
        }

        $this->wishlist->remove($user, $sessionId, $product);

        return response()->json([
            'count' => $this->wishlist->count($user, $sessionId),
            'session_id' => $sessionId,
        ]);
    }
}
