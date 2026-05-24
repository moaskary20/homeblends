<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FlashSale\FlashSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function resolveForRequest(Request $request): Cart
    {
        $userId = $request->hasSession()
            ? auth('web')->id()
            : $request->user()?->id;

        $sessionId = $request->hasSession()
            ? $request->session()->getId()
            : $request->header('X-Session-Id');

        if (! is_string($sessionId) || $sessionId === '') {
            $sessionId = null;
        }

        return $this->resolveCart($userId, $sessionId);
    }

    public function resolveCart(?int $userId, ?string $sessionId): Cart
    {
        if ($userId && $sessionId) {
            $guestHasItems = Cart::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->where('saved_for_later', false)
                ->whereHas('items')
                ->exists();

            if ($guestHasItems) {
                $this->mergeGuestCart($sessionId, $userId);
            }
        }

        if ($userId) {
            $cart = Cart::query()
                ->where('user_id', $userId)
                ->where('saved_for_later', false)
                ->first();
        } else {
            $cart = Cart::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->where('saved_for_later', false)
                ->first();
        }

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
        }

        return $cart;
    }

    public function addItem(Cart $cart, Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $pricing = app(FlashSaleService::class);
        $pricing->assertCanPurchase($product, $variant, $quantity);
        $unitPrice = $pricing->resolveUnitPrice($product, $variant)['price'];

        return DB::transaction(function () use ($cart, $product, $quantity, $variant, $unitPrice) {
            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
            ]);

            $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
            $item->unit_price = $unitPrice;
            $item->save();

            return $item->load(['product', 'variant']);
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh(['product', 'variant']);
    }

    public function getTotals(Cart $cart): array
    {
        $cart->load(['items.product', 'items.variant']);

        $subtotal = $cart->items->sum(fn (CartItem $item) => $item->subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'items_count' => $cart->items->sum('quantity'),
        ];
    }

    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->where('saved_for_later', false)
            ->with('items.product', 'items.variant')
            ->first();

        if (! $guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        $userCart = Cart::query()
            ->where('user_id', $userId)
            ->where('saved_for_later', false)
            ->first();

        if (! $userCart) {
            $userCart = Cart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
        }

        foreach ($guestCart->items as $item) {
            $this->addItem(
                $userCart,
                $item->product,
                $item->quantity,
                $item->variant
            );
        }

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    public function saveForLater(Cart $cart): Cart
    {
        $cart->update(['saved_for_later' => true]);

        return $cart;
    }

    public function restoreFromSaved(int $userId): Cart
    {
        $saved = Cart::query()
            ->where('user_id', $userId)
            ->where('saved_for_later', true)
            ->first();

        if ($saved) {
            $saved->update(['saved_for_later' => false]);
        }

        return $this->resolveCart($userId, null);
    }

    public function applyCoupon(Cart $cart, string $code): Cart
    {
        $cart->update(['coupon_code' => strtoupper($code)]);

        return $cart->fresh();
    }

    public function forgetCache(Cart $cart): void
    {
        // Cart resolution no longer uses cache; kept for callers.
    }
}
