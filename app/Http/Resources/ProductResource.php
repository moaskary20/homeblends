<?php

namespace App\Http\Resources;

use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $flash = null;
        if ($this->relationLoaded('activeFlashSaleEntry') && $this->activeFlashSaleEntry?->hasStock()) {
            $entry = $this->activeFlashSaleEntry;
            $flash = [
                'flash_price' => $entry->sale_price,
                'compare_price' => $this->regular_price,
                'discount_percent' => $entry->discountPercent(),
                'ends_at' => $entry->flashSale?->ends_at?->toIso8601String(),
                'remaining' => $entry->remainingQuantity(),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'main_image' => ProductMedia::productThumbnail($this->resource),
            'regular_price' => $this->regular_price,
            'discount_price' => $this->discount_price,
            'discount_starts_at' => $this->discount_starts_at?->toIso8601String(),
            'discount_ends_at' => $this->discount_ends_at?->toIso8601String(),
            'has_active_discount' => $this->hasActiveTimedDiscount(),
            'effective_price' => $this->effective_price,
            'is_flash_sale' => $flash !== null,
            'flash_sale' => $flash,
            'is_installment_offer' => $this->relationLoaded('activeOfferEntry') && $this->activeOfferEntry?->hasStock(),
            'installment_offer' => $this->when(
                $this->relationLoaded('activeOfferEntry') && $this->activeOfferEntry?->hasStock(),
                fn () => [
                    'offer_product_id' => $this->activeOfferEntry->id,
                    'offer_price' => $this->activeOfferEntry->offer_price,
                    'installment_months' => $this->activeOfferEntry->offer?->defaultPlanMonths(),
                    'installment_plans' => $this->activeOfferEntry->offer?->planMonths(),
                    'monthly_amount' => $this->activeOfferEntry->monthlyAmount(),
                    'offer_slug' => $this->activeOfferEntry->offer?->slug,
                    'offer_name' => $this->activeOfferEntry->offer?->name,
                ]
            ),
            'stock_quantity' => $this->stock_quantity,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'is_featured' => $this->is_featured,
            'avg_rating' => $this->avg_rating,
            'reviews_count' => $this->reviews_count,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'gallery' => ProductMedia::productGallery($this->resource)->map(fn (array $item) => [
                'url' => $item['url'],
                'alt' => $item['alt'],
            ])->values(),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
