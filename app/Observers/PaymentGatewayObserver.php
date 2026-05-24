<?php

namespace App\Observers;

use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayService;

class PaymentGatewayObserver
{
    public function saved(PaymentGateway $gateway): void
    {
        app(PaymentGatewayService::class)->clearCache();
    }

    public function deleted(PaymentGateway $gateway): void
    {
        app(PaymentGatewayService::class)->clearCache();
    }
}
