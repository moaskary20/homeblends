<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Gateways\CashOnDeliveryGateway;
use App\Services\Payment\Gateways\LocalProviderGateway;
use App\Services\Payment\Gateways\PayPalGateway;

class PaymentService
{
    public function initiate(Order $order, PaymentGatewayDriver $gateway, array $meta = []): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $gateway->value,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'payload' => $meta ?: null,
        ]);

        $handler = match ($gateway) {
            PaymentGatewayDriver::Paypal => app(PayPalGateway::class),
            PaymentGatewayDriver::CashOnDelivery => app(CashOnDeliveryGateway::class),
            PaymentGatewayDriver::LocalProvider => app(LocalProviderGateway::class),
        };

        return $handler->process($payment);
    }
}
