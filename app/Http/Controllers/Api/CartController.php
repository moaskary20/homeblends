<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use App\Services\Bundle\BundleService;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function show(Request $request)
    {
        $cart = $this->resolveCart($request);
        $cart->load(['items.product.images', 'items.variant', 'items.bundle']);

        return response()->json([
            'cart' => new CartResource($cart),
            'totals' => $this->cartService->getTotals($cart),
        ]);
    }

    public function storeBundle(Request $request, BundleService $bundles)
    {
        $request->validate([
            'product_bundle_id' => ['required', 'exists:product_bundles,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $cart = $this->resolveCart($request);
        $bundle = ProductBundle::with(['items.product', 'items.variant'])
            ->findOrFail($request->integer('product_bundle_id'));

        $item = $bundles->addToCart($cart, $bundle, $request->integer('quantity', 1));
        $cart->load(['items.product', 'items.bundle']);

        return response()->json([
            'item' => $item,
            'totals' => $this->cartService->getTotals($cart),
        ]);
    }

    public function store(AddToCartRequest $request)
    {
        $cart = $this->resolveCart($request);
        $product = Product::findOrFail($request->product_id);
        $variant = $request->product_variant_id
            ? ProductVariant::where('product_id', $product->id)->findOrFail($request->product_variant_id)
            : null;

        $item = $this->cartService->addItem($cart, $product, $request->integer('quantity', 1), $variant);
        $cart->load(['items.product']);

        return response()->json([
            'item' => $item,
            'totals' => $this->cartService->getTotals($cart),
        ]);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);
        $item = $this->cartService->updateQuantity($cartItem, $request->integer('quantity'));

        return response()->json([
            'item' => $item,
            'totals' => $this->cartService->getTotals($cartItem->cart),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);
        $cart = $cartItem->cart;
        $cartItem->delete();

        return response()->json([
            'totals' => $this->cartService->getTotals($cart),
        ]);
    }

    public function saveForLater(Request $request)
    {
        abort_unless($request->user(), 401);

        $cart = $this->resolveCart($request);
        $this->cartService->saveForLater($cart);

        return response()->json(['message' => __('ecommerce.cart_saved')]);
    }

    public function restoreSaved(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $cart = $this->cartService->restoreFromSaved($user->id);
        $cart->load(['items.product.images', 'items.variant', 'items.bundle']);

        return response()->json([
            'cart' => new CartResource($cart),
            'totals' => $this->cartService->getTotals($cart),
        ]);
    }

    protected function resolveCart(Request $request)
    {
        $sessionId = $request->header('X-Session-Id');

        if (! $sessionId && $request->hasSession()) {
            $sessionId = $request->session()->getId();
        }

        return $this->cartService->resolveCart(
            $request->user()?->id,
            $sessionId
        );
    }

    protected function authorizeCartItem(Request $request, CartItem $item): void
    {
        $cart = $item->cart;
        $userId = $request->user()?->id;
        $sessionId = $request->header('X-Session-Id');

        if (! $sessionId && $request->hasSession()) {
            $sessionId = $request->session()->getId();
        }

        if ($cart->user_id && $cart->user_id !== $userId) {
            abort(403);
        }

        if (! $cart->user_id && $cart->session_id !== $sessionId) {
            abort(403);
        }
    }
}
