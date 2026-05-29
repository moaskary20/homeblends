<?php

namespace App\Http\Resources;

use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'image' => ProductMedia::url($this->image),
            'is_default' => $this->is_default,
        ];
    }
}
