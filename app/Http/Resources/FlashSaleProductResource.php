<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');

        return [
            'id' => $this->id,
            'flash_price' => $this->sale_price,
            'compare_price' => $this->variant?->price ?? $this->product?->regular_price,
            'discount_percent' => $this->discountPercent(),
            'quantity_limit' => $this->stock_limit,
            'quantity_sold' => $this->quantity_sold,
            'remaining' => $this->remainingQuantity(),
            'ends_at' => $this->flashSale?->ends_at?->toIso8601String(),
            'product' => $product ? new ProductResource($this->product) : null,
        ];
    }
}
