<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class LocalProviderGateway
{
    public function process(Payment $payment): Payment
    {
        $config = config('ecommerce.payments.local');

        if (! empty($config['api_url'])) {
            $response = Http::withToken($config['api_key'] ?? '')
                ->post($config['api_url'], [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'order_ref' => $payment->order->order_number,
                ]);

            $payment->update([
                'payload' => $response->json(),
                'transaction_id' => $response->json('transaction_id'),
            ]);
        } else {
            $payment->update([
                'status' => 'pending',
                'payload' => ['method' => 'local_provider', 'manual' => true],
            ]);
        }

        return $payment;
    }
}
