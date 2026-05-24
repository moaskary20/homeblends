<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalGateway
{
    public function process(Payment $payment): Payment
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $payment->currency,
                    'value' => number_format($payment->amount, 2, '.', ''),
                ],
                'reference_id' => $payment->order->order_number,
            ]],
            'application_context' => [
                'return_url' => route('payment.paypal.success', $payment),
                'cancel_url' => route('payment.paypal.cancel', $payment),
            ],
        ]);

        $payment->update([
            'payload' => $response,
            'transaction_id' => $response['id'] ?? null,
        ]);

        return $payment;
    }
}
