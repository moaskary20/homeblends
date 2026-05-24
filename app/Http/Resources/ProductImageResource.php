<?php

namespace App\Http\Resources;

use App\Support\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => ProductMedia::url($this->path),
            'url' => ProductMedia::url($this->path),
            'alt' => $this->alt,
        ];
    }
}
