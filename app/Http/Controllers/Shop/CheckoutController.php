<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Seo\SeoService;
use App\Services\Shipping\ShippingService;

class CheckoutController extends Controller
{
    public function __invoke(ShippingService $shippingService, PaymentGatewayService $paymentGateways)
    {
        $shippingRates = $shippingService->getAvailableRates('EG');
        $freeShippingMin = \App\Models\FreeShippingRule::query()
            ->where('is_active', true)
            ->min('min_order_amount');
        $paymentGateways = $paymentGateways->getActive();

        $seo = app(SeoService::class)->forPrivatePage(__('ecommerce.checkout'));

        return view('shop.checkout', compact('shippingRates', 'freeShippingMin', 'paymentGateways', 'seo'));
    }
}
