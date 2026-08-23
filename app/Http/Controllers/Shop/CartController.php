<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;

class CartController extends Controller
{
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
}
