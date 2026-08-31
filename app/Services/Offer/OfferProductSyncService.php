<?php

namespace App\Services\Offer;

use App\Models\Offer;
use App\Models\OfferProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferProductSyncService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function sync(Offer $offer, array $items): void
    {
        $items = collect($items)
            ->filter(fn (array $row) => ! empty($row['product_id']))
            ->values();

        $productIds = $items->pluck('product_id');
        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'offer_products' => [__('ecommerce.offer_product_duplicate')],
            ]);
        }

        DB::transaction(function () use ($offer, $items) {
            $keptIds = [];

            foreach ($items as $index => $row) {
                $attributes = [
                    'product_id' => (int) $row['product_id'],
                    'product_variant_id' => filled($row['product_variant_id'] ?? null)
                        ? (int) $row['product_variant_id']
                        : null,
                    'offer_price' => (float) $row['offer_price'],
                    'stock_limit' => filled($row['stock_limit'] ?? null) ? (int) $row['stock_limit'] : null,
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                ];

                if (! empty($row['id'])) {
                    $entry = OfferProduct::query()
                        ->where('offer_id', $offer->id)
                        ->whereKey($row['id'])
                        ->first();

                    if ($entry) {
                        $entry->update($attributes);
                        $keptIds[] = $entry->id;

                        continue;
                    }
                }

                $entry = $offer->products()->updateOrCreate(
                    ['product_id' => $attributes['product_id']],
                    $attributes
                );
                $keptIds[] = $entry->id;
            }

            $offer->products()->whereNotIn('id', $keptIds)->delete();
        });

        app(OfferService::class)->clearCaches();
    }
}
