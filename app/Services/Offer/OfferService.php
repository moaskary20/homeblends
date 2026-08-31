<?php

namespace App\Services\Offer;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\OfferProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Installment\InstallmentScheduler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OfferService
{
    public function findActiveEntry(Product $product, ?ProductVariant $variant = null): ?OfferProduct
    {
        $entries = OfferProduct::query()
            ->with(['offer', 'product', 'variant'])
            ->inActiveOffer()
            ->where('product_id', $product->id)
            ->where(function ($query) use ($variant) {
                $query->whereNull('product_variant_id');
                if ($variant) {
                    $query->orWhere('product_variant_id', $variant->id);
                }
            })
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        if ($variant) {
            $variantEntry = $entries->firstWhere('product_variant_id', $variant->id);
            if ($variantEntry) {
                return $variantEntry;
            }
        }

        return $entries
            ->whereNull('product_variant_id')
            ->sortBy('offer_price')
            ->first();
    }

    public function findActiveById(int $offerProductId): ?OfferProduct
    {
        return OfferProduct::query()
            ->with(['offer', 'product', 'variant'])
            ->inActiveOffer()
            ->whereKey($offerProductId)
            ->first();
    }

    public function assertCanPurchase(OfferProduct $entry, int $quantity): void
    {
        if (! $entry->offer || ! $entry->offer->isRunning()) {
            throw ValidationException::withMessages([
                'offer_product_id' => [__('ecommerce.offer_not_available')],
            ]);
        }

        if (! $entry->hasStock($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => [__('ecommerce.offer_sold_out')],
            ]);
        }
    }

    public function recordSale(OfferProduct $entry, int $quantity): void
    {
        $entry->increment('quantity_sold', $quantity);
        $this->clearCaches();
    }

    public function reverseSale(OfferProduct $entry, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $toReverse = min($quantity, max(0, (int) $entry->quantity_sold));

        if ($toReverse > 0) {
            $entry->decrement('quantity_sold', $toReverse);
            $this->clearCaches();
        }
    }

    /**
     * @return array{
     *     eligible: bool,
     *     offer: ?Offer,
     *     months: int,
     *     products_total: float,
     *     monthly_amount: float,
     *     schedule: list<array{sequence: int, amount: float, due_date: string}>,
     *     reason: ?string
     * }
     */
    public function cartInstallmentPreview(Cart $cart): array
    {
        $cart->loadMissing(['items.offerProduct.offer', 'items.product']);

        $empty = [
            'eligible' => false,
            'offer' => null,
            'months' => 0,
            'products_total' => 0.0,
            'monthly_amount' => 0.0,
            'schedule' => [],
            'reason' => null,
        ];

        if ($cart->items->isEmpty()) {
            return $empty;
        }

        try {
            $offer = $this->assertCartEligibleForInstallment($cart);
        } catch (ValidationException $e) {
            return array_merge($empty, [
                'reason' => collect($e->errors())->flatten()->first(),
            ]);
        }

        $productsTotal = round($cart->items->sum(fn (CartItem $item) => $item->subtotal), 2);
        $months = $this->resolveCartPlanMonths($cart, $offer);
        $amounts = app(InstallmentScheduler::class)->splitAmounts($productsTotal, $months);
        $start = now()->startOfDay();

        $schedule = [];
        foreach ($amounts as $index => $amount) {
            $schedule[] = [
                'sequence' => $index + 1,
                'amount' => $amount,
                'due_date' => $start->copy()->addMonths($index)->translatedFormat('j F Y'),
            ];
        }

        return [
            'eligible' => true,
            'offer' => $offer,
            'months' => $months,
            'products_total' => $productsTotal,
            'monthly_amount' => $amounts[0] ?? 0.0,
            'schedule' => $schedule,
            'reason' => null,
        ];
    }

    public function resolveCartPlanMonths(Cart $cart, Offer $offer): int
    {
        $selected = (int) ($cart->installment_months ?? 0);

        if ($offer->hasPlan($selected)) {
            return $selected;
        }

        return $offer->defaultPlanMonths();
    }

    public function assertCartEligibleForInstallment(Cart $cart): Offer
    {
        $cart->loadMissing(['items.offerProduct.offer', 'items.product']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'pay_in_installments' => [__('ecommerce.installment_cart_empty')],
            ]);
        }

        $offerIds = [];

        foreach ($cart->items as $item) {
            if ($item->product_bundle_id) {
                throw ValidationException::withMessages([
                    'pay_in_installments' => [__('ecommerce.installment_offer_only')],
                ]);
            }

            $entry = $item->offerProduct;

            if (! $entry || ! $entry->offer) {
                throw ValidationException::withMessages([
                    'pay_in_installments' => [__('ecommerce.installment_offer_only')],
                ]);
            }

            $this->assertCanPurchase($entry, (int) $item->quantity);
            $offerIds[] = $entry->offer_id;
        }

        $unique = array_values(array_unique($offerIds));

        if (count($unique) !== 1) {
            throw ValidationException::withMessages([
                'pay_in_installments' => [__('ecommerce.installment_single_offer')],
            ]);
        }

        $offer = $cart->items->first()->offerProduct->offer;

        if ($this->resolveCartPlanMonths($cart, $offer) < 2) {
            throw ValidationException::withMessages([
                'pay_in_installments' => [__('ecommerce.installment_months_invalid')],
            ]);
        }

        return $offer;
    }

    public function getActiveOffers(): Collection
    {
        return Cache::remember('offers.active', 300, function () {
            return Offer::query()
                ->active()
                ->orderBy('sort_order')
                ->orderByDesc('starts_at')
                ->with(['products.product.category', 'products.product.images', 'products.variant'])
                ->get();
        });
    }

    public function getHighlightedProducts(int $limit = 8): Collection
    {
        return Cache::remember("offers.products.{$limit}", 300, function () use ($limit) {
            return OfferProduct::query()
                ->with(['product.category', 'product.images', 'variant', 'offer'])
                ->inActiveOffer()
                ->orderBy('sort_order')
                ->limit($limit)
                ->get()
                ->filter(fn (OfferProduct $entry) => $entry->hasStock())
                ->values();
        });
    }

    public function clearCaches(): void
    {
        Cache::forget('offers.active');
        Cache::forget('offers.products.8');
        Cache::forget('offers.products.12');
        Cache::forget('shop.featured');
    }
}
