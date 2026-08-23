<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Seo\SeoService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PaymentGatewayService $paymentGateways,
    ) {}

    public function show(ShippingService $shippingService)
    {
        $shippingRates = $shippingService->getAvailableRates('EG');
        $freeShippingMin = \App\Models\FreeShippingRule::query()
            ->where('is_active', true)
            ->min('min_order_amount');
        $paymentGateways = $this->paymentGateways->getActive();

        $seo = app(SeoService::class)->forPrivatePage(__('ecommerce.checkout'));

        return view('shop.checkout', compact('shippingRates', 'freeShippingMin', 'paymentGateways', 'seo'));
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $cart = $this->cartService->resolveForRequest($request);

        try {
            $order = $this->checkoutService->placeOrder(
                cart: $cart,
                user: $request->user(),
                shippingAddress: $request->shipping_address,
                billingAddress: $request->billing_address ?? $request->shipping_address,
                shippingRateId: $request->integer('shipping_rate_id'),
                couponCode: $request->coupon_code,
                gateway: $this->paymentGateways->resolveDriver($request->payment_gateway),
                loyaltyPointsToRedeem: $request->integer('loyalty_points', 0),
                notes: $request->notes,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new OrderResource($order))->response();
    }
}
