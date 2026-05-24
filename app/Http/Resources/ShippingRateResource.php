<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'rate' => $this->rate,
            'min_value' => $this->min_value,
            'max_value' => $this->max_value,
            'estimated_days' => $this->estimated_days,
            'zone' => $this->whenLoaded('zone', fn () => $this->zone->name),
            'zone_id' => $this->shipping_zone_id,
        ];
    }
}
