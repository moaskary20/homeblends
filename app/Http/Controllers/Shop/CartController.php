<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\Cart\CartService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        $cart = $cartService->resolveCart(
            $request->user()?->id,
            $request->session()->getId()
        );

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
        $cart = $cartService->resolveCart(
            $request->user()?->id,
            $request->session()->getId()
        );
        $cart->load(['items.product.images', 'items.variant', 'items.bundle']);

        return response()->json([
            'cart' => new CartResource($cart),
            'totals' => $cartService->getTotals($cart),
        ]);
    }
}
