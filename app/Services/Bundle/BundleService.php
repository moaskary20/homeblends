<?php

namespace App\Services\Bundle;

use App\Models\BundleItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductBundle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BundleService
{
    public function calculateRegularTotal(ProductBundle $bundle): float
    {
        $bundle->loadMissing(['items.product', 'items.variant']);

        return round($bundle->items->sum(fn (BundleItem $item) => $item->lineRegularTotal()), 2);
    }

    public function calculateSavings(ProductBundle $bundle): float
    {
        return max(0, round($this->calculateRegularTotal($bundle) - (float) $bundle->bundle_price, 2));
    }

    public function savingsPercent(ProductBundle $bundle): float
    {
        $regular = $this->calculateRegularTotal($bundle);

        if ($regular <= 0) {
            return 0;
        }

        return round(($this->calculateSavings($bundle) / $regular) * 100, 1);
    }

    public function assertAvailable(ProductBundle $bundle): void
    {
        if (! $bundle->isAvailable()) {
            throw ValidationException::withMessages([
                'bundle' => [__('ecommerce.bundle_not_available')],
            ]);
        }
    }

    public function assertStock(ProductBundle $bundle, int $bundleQuantity = 1): void
    {
        $bundle->loadMissing(['items.product', 'items.variant']);

        foreach ($bundle->items as $item) {
            $required = $item->quantity * $bundleQuantity;
            $available = $item->variant
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;

            if ($required > $available) {
                throw ValidationException::withMessages([
                    'bundle' => [__('ecommerce.bundle_insufficient_stock', ['product' => $item->product->name])],
                ]);
            }
        }
    }

    public function addToCart(Cart $cart, ProductBundle $bundle, int $quantity = 1): CartItem
    {
        $this->assertAvailable($bundle);
        $this->assertStock($bundle, $quantity);

        $bundle->load(['items.product', 'items.variant']);
        $first = $bundle->items->first();

        if (! $first) {
            throw ValidationException::withMessages([
                'bundle' => [__('ecommerce.bundle_items_required')],
            ]);
        }

        return DB::transaction(function () use ($cart, $bundle, $quantity, $first) {
            CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_bundle_id', $bundle->id)
                ->delete();

            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $first->product_id,
                'product_variant_id' => $first->product_variant_id,
                'product_bundle_id' => $bundle->id,
                'quantity' => $quantity,
                'unit_price' => $bundle->bundle_price,
                'bundle_snapshot' => $bundle->toSnapshot(),
            ]);

            app(\App\Services\Cart\CartService::class)->forgetCache($cart);

            return $item->load(['product', 'bundle']);
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function expandToOrderLines(ProductBundle $bundle, int $bundleQuantity, float $bundleLineTotal): Collection
    {
        $bundle->loadMissing(['items.product', 'items.variant']);
        $regularTotal = $this->calculateRegularTotal($bundle);

        if ($regularTotal <= 0) {
            $share = $bundleLineTotal / max(1, $bundle->items->sum('quantity'));

            return $bundle->items->map(fn (BundleItem $item) => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->name,
                'sku' => $item->variant?->sku ?? $item->product->sku,
                'quantity' => $item->quantity * $bundleQuantity,
                'unit_price' => round($share, 2),
                'total' => round($share * $item->quantity * $bundleQuantity, 2),
                'variant_snapshot' => $item->variant?->toArray(),
                'product' => $item->product,
                'variant' => $item->variant,
            ]);
        }

        $ratio = $bundleLineTotal / $regularTotal;

        return $bundle->items->map(function (BundleItem $item) use ($bundleQuantity, $ratio) {
            $unitRegular = (float) ($item->variant?->price ?? $item->product->baseSellingPrice());
            $unitPrice = round($unitRegular * $ratio, 2);
            $qty = $item->quantity * $bundleQuantity;

            return [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->name,
                'sku' => $item->variant?->sku ?? $item->product->sku,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => round($unitPrice * $qty, 2),
                'variant_snapshot' => $item->variant?->toArray(),
                'product' => $item->product,
                'variant' => $item->variant,
            ];
        });
    }

    public function decrementStock(ProductBundle $bundle, int $bundleQuantity): void
    {
        $bundle->loadMissing(['items.product', 'items.variant']);

        foreach ($bundle->items as $item) {
            $qty = $item->quantity * $bundleQuantity;
            if ($item->variant) {
                $item->variant->decrement('stock_quantity', $qty);
            } else {
                $item->product->decrement('stock_quantity', $qty);
            }
        }
    }

    public function createOrderItemsFromCartLine(Order $order, CartItem $cartItem): void
    {
        if (! $cartItem->product_bundle_id || ! $cartItem->bundle_snapshot) {
            return;
        }

        $bundle = ProductBundle::with(['items.product', 'items.variant'])->find($cartItem->product_bundle_id);

        if (! $bundle) {
            return;
        }

        $lines = $this->expandToOrderLines(
            $bundle,
            $cartItem->quantity,
            (float) $cartItem->unit_price * $cartItem->quantity
        );

        foreach ($lines as $line) {
            $order->items()->create([
                'product_id' => $line['product_id'],
                'product_variant_id' => $line['product_variant_id'],
                'product_name' => $line['product_name'],
                'sku' => $line['sku'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'total' => $line['total'],
                'variant_snapshot' => $line['variant_snapshot'],
            ]);
        }

        $this->decrementStock($bundle, $cartItem->quantity);
    }

    public function getActiveBundles(): Collection
    {
        return Cache::remember('bundles.active', 300, function () {
            return ProductBundle::query()
                ->active()
                ->with(['items.product.images', 'items.variant'])
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn (ProductBundle $bundle) => $bundle->items->isNotEmpty());
        });
    }

    public function clearCaches(): void
    {
        Cache::forget('bundles.active');
    }
}
