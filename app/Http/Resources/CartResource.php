<?php

namespace App\Http\Resources;

use App\Services\Cart\CartDisplayLine;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => app(CartService::class)
                ->displayLines($this->resource)
                ->map(fn (CartDisplayLine $line) => $line->toApiArray())
                ->values(),
            'coupon_code' => $this->coupon_code,
            'installment_months' => $this->installment_months,
        ];
    }
}
