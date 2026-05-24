<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Affiliate\AffiliateCommissionService;

class OrderAffiliateObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged(['status', 'payment_status'])) {
            app(AffiliateCommissionService::class)->handleOrderStatusChange($order);
        }
    }
}
