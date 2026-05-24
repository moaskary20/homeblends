<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
            'is_bundle' => $this->isBundleLine(),
            'bundle' => $this->when($this->isBundleLine(), fn () => [
                'id' => $this->product_bundle_id,
                'name' => $this->bundle_snapshot['name'] ?? $this->bundle?->name,
                'items' => $this->bundle_snapshot['items'] ?? [],
            ]),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
