<?php

namespace App\Http\Resources;

use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'banner_image' => ProductMedia::url($this->banner_image),
            'is_running' => $this->isRunning(),
            'products' => FlashSaleProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
