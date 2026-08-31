<?php

namespace App\Services\Cart;

use App\Http\Concerns\ResolvesCartSession;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\OfferProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FlashSale\FlashSaleService;
use App\Services\Inventory\InventoryService;
use App\Services\Offer\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    use ResolvesCartSession;

    public function resolveForRequest(Request $request): Cart
    {
        $userId = $request->user()?->id;

        if ($userId === null && $request->hasSession()) {
            $userId = auth('web')->id();
        }

        return $this->resolveCart($userId, $this->resolveCartSessionId($request));
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
            if (! is_string($sessionId) || $sessionId === '') {
                throw new \InvalidArgumentException('Guest cart requires a browser session.');
            }

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

    public function addItem(
        Cart $cart,
        Product $product,
        int $quantity = 1,
        ?ProductVariant $variant = null,
        ?OfferProduct $offerProduct = null,
    ): CartItem {
        if ($offerProduct) {
            app(OfferService::class)->assertCanPurchase($offerProduct, $quantity);
        } else {
            app(FlashSaleService::class)->assertCanPurchase($product, $variant, $quantity);
        }

        return DB::transaction(function () use ($cart, $product, $quantity, $variant, $offerProduct) {
            $query = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->whereNull('product_bundle_id');

            if ($offerProduct) {
                $query->where('offer_product_id', $offerProduct->id);
            } else {
                $query->whereNull('offer_product_id');
            }

            $item = $query->first() ?? new CartItem([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'offer_product_id' => $offerProduct?->id,
            ]);

            $nextQuantity = ($item->exists ? $item->quantity : 0) + $quantity;
            app(InventoryService::class)->assertAvailable($product, $nextQuantity, $variant);

            if ($offerProduct) {
                app(OfferService::class)->assertCanPurchase($offerProduct, $nextQuantity);
                $item->unit_price = (float) $offerProduct->offer_price;
                $item->offer_product_id = $offerProduct->id;
            } else {
                $item->unit_price = app(FlashSaleService::class)->resolveUnitPrice($product, $variant)['price'];
            }

            $item->quantity = $nextQuantity;
            $item->save();

            return $item->load(['product', 'variant', 'offerProduct.offer']);
        });
    }

    public function addOffer(Cart $cart, Offer $offer, ?int $months = null): Cart
    {
        $offer->loadMissing(['products.product', 'products.variant', 'products.offer']);

        if ($offer->products->isEmpty() || ! $offer->isRunning()) {
            throw ValidationException::withMessages([
                'offer' => [__('ecommerce.offer_not_available')],
            ]);
        }

        if (! $offer->canPurchaseAsSet()) {
            throw ValidationException::withMessages([
                'offer' => [__('ecommerce.offer_set_unavailable')],
            ]);
        }

        $months ??= $offer->defaultPlanMonths();

        if (! $offer->hasPlan($months)) {
            throw ValidationException::withMessages([
                'installment_months' => [__('ecommerce.installment_plan_invalid')],
            ]);
        }

        return DB::transaction(function () use ($cart, $offer, $months) {
            $offers = app(OfferService::class);
            $inventory = app(InventoryService::class);

            foreach ($offer->products as $entry) {
                $offers->assertCanPurchase($entry, 1);
                $inventory->assertAvailable($entry->product, 1, $entry->variant);
            }

            foreach ($offer->products as $entry) {
                $item = CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->where('offer_product_id', $entry->id)
                    ->first() ?? new CartItem([
                        'cart_id' => $cart->id,
                        'product_id' => $entry->product_id,
                        'product_variant_id' => $entry->product_variant_id,
                        'offer_product_id' => $entry->id,
                    ]);

                $item->product_id = $entry->product_id;
                $item->product_variant_id = $entry->product_variant_id;
                $item->offer_product_id = $entry->id;
                $item->unit_price = (float) $entry->offer_price;
                $item->quantity = 1;
                $item->save();
            }

            $cart->forceFill(['installment_months' => $months])->save();

            return $cart->fresh(['items.product', 'items.variant', 'items.offerProduct.offer']);
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);

            return null;
        }

        $item->loadMissing(['product', 'variant', 'offerProduct']);

        if ($item->product && ! $item->product_bundle_id) {
            app(InventoryService::class)->assertAvailable($item->product, $quantity, $item->variant);
        }

        if ($item->offer_product_id && $quantity !== 1) {
            throw ValidationException::withMessages([
                'quantity' => [__('ecommerce.offer_qty_locked')],
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh(['product', 'variant']);
    }

    public function removeItem(CartItem $item): void
    {
        $item->loadMissing('offerProduct');
        $cart = $item->cart;
        $offerId = $item->offerProduct?->offer_id;

        if ($item->offer_product_id && $offerId) {
            $cart->items()
                ->whereHas('offerProduct', fn ($query) => $query->where('offer_id', $offerId))
                ->delete();

            if (! $cart->items()->whereNotNull('offer_product_id')->exists()) {
                $cart->update(['installment_months' => null]);
            }

            return;
        }

        $item->delete();
    }

    /**
     * @return Collection<int, CartDisplayLine>
     */
    public function displayLines(Cart $cart): Collection
    {
        $cart->loadMissing([
            'items.product.images',
            'items.variant',
            'items.bundle',
            'items.offerProduct.offer',
        ]);

        $lines = collect();
        $emittedOffers = [];

        foreach ($cart->items as $item) {
            $offerId = $item->offerProduct?->offer_id;

            if ($offerId) {
                if (isset($emittedOffers[$offerId])) {
                    continue;
                }

                $emittedOffers[$offerId] = true;
                $group = $cart->items->filter(
                    fn (CartItem $row) => $row->offerProduct?->offer_id === $offerId
                );
                $offer = $group->first()?->offerProduct?->offer;

                if (! $offer) {
                    continue;
                }

                $lines->push(CartDisplayLine::fromOfferSet($offer, $group->values(), $cart));

                continue;
            }

            $lines->push(CartDisplayLine::fromCartItem($item));
        }

        return $lines->values();
    }

    public function getTotals(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'items.variant', 'items.offerProduct']);

        $subtotal = $cart->items->sum(fn (CartItem $item) => $item->subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'items_count' => $this->displayLines($cart)->sum(fn (CartDisplayLine $line) => $line->quantity),
        ];
    }

    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->where('saved_for_later', false)
            ->with('items.product', 'items.variant', 'items.offerProduct')
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
                $item->variant,
                $item->offerProduct,
            );
        }

        if ($guestCart->installment_months) {
            $userCart->update(['installment_months' => $guestCart->installment_months]);
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
