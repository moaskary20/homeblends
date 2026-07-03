<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'shipping_method' => $this->shipping_method,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'currency' => $this->currency,
            'tracking_number' => $this->tracking_number,
            'payment_status' => $this->payment_status,
            'loyalty_points_earned' => $this->loyalty_points_earned,
            'loyalty_points_redeemed' => $this->loyalty_points_redeemed,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'payment_action' => $this->resolvePaymentAction(),
        ];
    }

    protected function resolvePaymentAction(): ?array
    {
        if (! $this->relationLoaded('payments')) {
            return null;
        }

        $payment = $this->payments->firstWhere('status', 'pending');

        if (! $payment || ! is_array($payment->payload)) {
            return null;
        }

        $approvalUrl = collect($payment->payload['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approvalUrl) {
            return null;
        }

        return [
            'type' => 'redirect',
            'approval_url' => $approvalUrl,
        ];
    }
}
