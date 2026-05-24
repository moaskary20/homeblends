<?php

namespace App\Services\FlashSale;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FlashSaleProductSyncService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function sync(FlashSale $flashSale, array $items): void
    {
        $items = collect($items)
            ->filter(fn (array $row) => ! empty($row['product_id']))
            ->values();

        $productIds = $items->pluck('product_id');
        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'flash_products' => [__('ecommerce.flash_product_duplicate')],
            ]);
        }

        DB::transaction(function () use ($flashSale, $items) {
            $keptIds = [];

            foreach ($items as $index => $row) {
                $attributes = [
                    'product_id' => (int) $row['product_id'],
                    'product_variant_id' => filled($row['product_variant_id'] ?? null)
                        ? (int) $row['product_variant_id']
                        : null,
                    'sale_price' => (float) $row['sale_price'],
                    'stock_limit' => filled($row['stock_limit'] ?? null) ? (int) $row['stock_limit'] : null,
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                ];

                if (! empty($row['id'])) {
                    $entry = FlashSaleProduct::query()
                        ->where('flash_sale_id', $flashSale->id)
                        ->whereKey($row['id'])
                        ->first();

                    if ($entry) {
                        $entry->update($attributes);
                        $keptIds[] = $entry->id;

                        continue;
                    }
                }

                $entry = $flashSale->products()->updateOrCreate(
                    ['product_id' => $attributes['product_id']],
                    $attributes
                );
                $keptIds[] = $entry->id;
            }

            $flashSale->products()->whereNotIn('id', $keptIds)->delete();
        });

        app(FlashSaleService::class)->clearCaches();
    }
}
