<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentGatewayListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->displayName(),
            'description' => $this->description,
            'instructions' => $this->instructions,
            'cod_fee' => $this->codFee(),
            'min_order_amount' => $this->minOrderAmount(),
            'max_order_amount' => $this->maxOrderAmount(),
        ];
    }
}
