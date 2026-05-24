<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order->order_number),
            'items' => $this->items,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
