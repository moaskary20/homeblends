<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;

class CashOnDeliveryGateway
{
    public function process(Payment $payment): Payment
    {
        $payload = is_array($payment->payload) ? $payment->payload : [];

        $payment->update([
            'status' => 'pending',
            'payload' => array_merge($payload, [
                'method' => 'cash_on_delivery',
                'collect_on_delivery' => true,
            ]),
        ]);

        return $payment;
    }
}
