<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\FreeShippingRule;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Offer\OfferService;
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
        $freeShippingMin = FreeShippingRule::query()
            ->where('is_active', true)
            ->min('min_order_amount');
        $paymentGateways = $this->paymentGateways->getActive();

        $user = auth()->user();
        $defaultAddress = $user?->addresses()->where('is_default', true)->first()
            ?? $user?->addresses()->latest('id')->first();
        $nameParts = preg_split('/\s+/u', trim($user?->name ?? ''), 2) ?: [];

        $checkoutDefaults = [
            'first_name' => old('first_name', $defaultAddress?->first_name ?? ($nameParts[0] ?? '')),
            'last_name' => old('last_name', $defaultAddress?->last_name ?? ($nameParts[1] ?? '')),
            'email' => old('email', $user?->email ?? ''),
            'phone' => old('phone', $defaultAddress?->phone ?? $user?->phone ?? ''),
            'alternate_phone' => old('alternate_phone', $defaultAddress?->alternate_phone ?? $user?->alternate_phone ?? ''),
            'address_line_1' => old('address_line_1', $defaultAddress?->address_line_1 ?? ''),
            'address_line_2' => old('address_line_2', $defaultAddress?->address_line_2 ?? ''),
            'city' => old('city', $defaultAddress?->city ?? ''),
            'state' => old('state', $defaultAddress?->state ?? ''),
            'postal_code' => old('postal_code', $defaultAddress?->postal_code ?? ''),
            'country' => old('country', $defaultAddress?->country ?? 'EG'),
        ];

        $seo = app(SeoService::class)->forPrivatePage(__('ecommerce.checkout'));
        $cart = $this->cartService->resolveForRequest(request());
        $installmentPreview = app(OfferService::class)->cartInstallmentPreview($cart);

        return view('shop.checkout', compact(
            'shippingRates',
            'freeShippingMin',
            'paymentGateways',
            'checkoutDefaults',
            'seo',
            'installmentPreview',
        ));
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
                payInInstallments: $request->boolean('pay_in_installments'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new OrderResource($order))->response();
    }
}
