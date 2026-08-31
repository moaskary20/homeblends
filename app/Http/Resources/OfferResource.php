<?php

namespace App\Http\Resources;

use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = $this->offerTotal();
        $plans = $this->plansForTotal($total);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'banner_image' => ProductMedia::url($this->banner_image),
            'gallery' => collect($this->galleryPaths())
                ->map(fn (string $path) => ProductMedia::url($path))
                ->filter()
                ->values(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'installment_months' => $this->defaultPlanMonths(),
            'installment_plans' => $plans,
            'offer_total' => $total,
            'compare_total' => $this->compareTotal(),
            'monthly_amount' => $plans[0]['monthly_amount'] ?? $this->monthlyAmountFor($total),
            'is_running' => $this->isRunning(),
            'products' => OfferProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
