<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order?->order_number,
            'offer_name' => $this->offer?->name ?? ($this->offer_snapshot['name'] ?? null),
            'months' => $this->months,
            'total_amount' => $this->total_amount,
            'monthly_amount' => $this->monthly_amount,
            'remaining_total' => $this->remainingTotal(),
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'installments' => $this->whenLoaded('installments', fn () => $this->installments->map(fn ($row) => [
                'id' => $row->id,
                'sequence' => $row->sequence,
                'due_date' => $row->due_date?->toDateString(),
                'amount' => $row->amount,
                'status' => $row->status->value,
                'status_label' => $row->status->label(),
                'paid_at' => $row->paid_at?->toIso8601String(),
                'is_payable' => $row->isPayable(),
            ])),
        ];
    }
}
