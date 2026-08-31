<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $months = $this->offer?->defaultPlanMonths() ?? 6;

        return [
            'id' => $this->id,
            'offer_price' => $this->offer_price,
            'compare_price' => $this->variant?->price ?? $this->product?->regular_price,
            'discount_percent' => $this->discountPercent(),
            'installment_months' => $months,
            'installment_plans' => $this->offer?->planMonths() ?? [$months],
            'monthly_amount' => $this->monthlyAmount(),
            'quantity_limit' => $this->stock_limit,
            'quantity_sold' => $this->quantity_sold,
            'remaining' => $this->remainingQuantity(),
            'ends_at' => $this->offer?->ends_at?->toIso8601String(),
            'product' => $product ? new ProductResource($this->product) : null,
        ];
    }
}
