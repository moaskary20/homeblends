<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ResolvesCartSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentGatewayService;

class CheckoutController extends Controller
{
    use ResolvesCartSession;

    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PaymentGatewayService $paymentGateways,
    ) {}

    public function store(PlaceOrderRequest $request)
    {
        $cart = $this->cartService->resolveCart(
            $request->user()->id,
            $this->resolveCartSessionId($request)
        );

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

        return new OrderResource($order);
    }
}
