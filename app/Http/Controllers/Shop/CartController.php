<?php

namespace App\Http\Controllers\Shop;

use App\Http\Concerns\ResolvesCartSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ResolvesCartSession;

    public function index(Request $request, CartService $cartService)
    {
        $cart = $cartService->resolveForRequest($request);

        $cart->load(['items.product.images', 'items.variant', 'items.bundle']);
        $totals = $cartService->getTotals($cart);

        return view('shop.cart', [
            'cart' => $cart,
            'totals' => $totals,
            'seo' => app(SeoService::class)->forCart(),
        ]);
    }

    public function preview(Request $request, CartService $cartService)
    {
        $cart = $cartService->resolveForRequest($request);
        $cart->load(['items.product.images', 'items.variant', 'items.bundle']);

        return response()->json([
            'cart' => new CartResource($cart),
            'totals' => $cartService->getTotals($cart),
        ]);
    }

    public function store(AddToCartRequest $request, CartService $cartService)
    {
        $cart = $cartService->resolveForRequest($request);
        $product = Product::findOrFail($request->product_id);
        $variant = $request->product_variant_id
            ? ProductVariant::where('product_id', $product->id)->findOrFail($request->product_variant_id)
            : null;

        $item = $cartService->addItem($cart, $product, $request->integer('quantity', 1), $variant);
        $cart->load(['items.product']);

        return response()->json([
            'item' => $item,
            'totals' => $cartService->getTotals($cart),
        ]);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem, CartService $cartService)
    {
        $this->authorizeCartItem($request, $cartItem);
        $item = $cartService->updateQuantity($cartItem, $request->integer('quantity'));

        return response()->json([
            'item' => $item,
            'totals' => $cartService->getTotals($cartItem->cart),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem, CartService $cartService)
    {
        $this->authorizeCartItem($request, $cartItem);
        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json([
            'totals' => $cartService->getTotals($cart),
        ]);
    }

    protected function authorizeCartItem(Request $request, CartItem $item): void
    {
        $cart = $item->cart;
        $userId = $request->user()?->id;
        $sessionId = $this->resolveCartSessionId($request);

        if ($cart->user_id && $cart->user_id !== $userId) {
            abort(403);
        }

        if (! $cart->user_id && $cart->session_id !== $sessionId) {
            abort(403);
        }
    }
}
