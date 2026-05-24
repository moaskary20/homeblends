<?php

namespace App\Services\FlashSale;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class FlashSaleService
{
    public function findActiveEntry(Product $product, ?ProductVariant $variant = null): ?FlashSaleProduct
    {
        $entries = FlashSaleProduct::query()
            ->with(['flashSale', 'product', 'variant'])
            ->inActiveSale()
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
            ->sortBy('sale_price')
            ->first();
    }

    /**
     * @return array{
     *     price: float,
     *     compare_price: float,
     *     is_flash_sale: bool,
     *     flash_sale_product: ?FlashSaleProduct,
     *     discount_percent: float
     * }
     */
    public function resolveUnitPrice(Product $product, ?ProductVariant $variant = null): array
    {
        $entry = $this->findActiveEntry($product, $variant);

        if ($entry && $entry->hasStock()) {
            $compare = (float) ($variant?->price ?? $product->regular_price);

            return [
                'price' => (float) $entry->sale_price,
                'compare_price' => $compare,
                'is_flash_sale' => true,
                'flash_sale_product' => $entry,
                'discount_percent' => $entry->discountPercent(),
            ];
        }

        $price = (float) ($variant?->price ?? $product->baseSellingPrice());

        return [
            'price' => $price,
            'compare_price' => (float) $product->regular_price,
            'is_flash_sale' => false,
            'flash_sale_product' => null,
            'discount_percent' => 0,
        ];
    }

    public function assertCanPurchase(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $entry = $this->findActiveEntry($product, $variant);

        if (! $entry) {
            return;
        }

        if (! $entry->hasStock($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => [__('ecommerce.flash_sale_sold_out')],
            ]);
        }
    }

    public function recordSale(FlashSaleProduct $entry, int $quantity): void
    {
        $entry->increment('quantity_sold', $quantity);
        $this->clearCaches();
    }

    public function getActiveSales(): Collection
    {
        return Cache::remember('flash_sales.active', 300, function () {
            return FlashSale::query()
                ->active()
                ->orderBy('sort_order')
                ->orderByDesc('starts_at')
                ->with(['products.product.category', 'products.product.images', 'products.variant'])
                ->get();
        });
    }

    public function getHighlightedProducts(int $limit = 12): Collection
    {
        return Cache::remember("flash_sales.products.{$limit}", 300, function () use ($limit) {
            return FlashSaleProduct::query()
                ->with(['product.category', 'product.images', 'variant', 'flashSale'])
                ->inActiveSale()
                ->orderBy('sort_order')
                ->limit($limit)
                ->get()
                ->filter(fn (FlashSaleProduct $entry) => $entry->hasStock())
                ->values();
        });
    }

    public function clearCaches(): void
    {
        Cache::forget('flash_sales.active');
        Cache::forget('flash_sales.products.8');
        Cache::forget('flash_sales.products.12');
        Cache::forget('shop.featured');
    }
}
