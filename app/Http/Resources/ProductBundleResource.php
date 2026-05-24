<?php

namespace App\Http\Resources;

use App\Services\Bundle\BundleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(BundleService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'main_image' => $this->main_image ? asset('storage/'.$this->main_image) : null,
            'bundle_price' => $this->bundle_price,
            'regular_total' => $service->calculateRegularTotal($this->resource),
            'savings' => $service->calculateSavings($this->resource),
            'savings_percent' => $service->savingsPercent($this->resource),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_available' => $this->isAvailable(),
            'items' => BundleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
