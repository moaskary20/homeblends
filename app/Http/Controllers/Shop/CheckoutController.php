<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\FreeShippingRule;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Offer\OfferService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Seo\SeoService;
use App\Services\Shipping\ShippingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $user = $request->user();
        $guestSessionId = $request->hasSession()
            ? ($request->session()->get(CartService::GUEST_SESSION_ID_KEY) ?? $request->session()->getId())
            : null;

        if ($user === null) {
            $user = $this->registerGuestCustomer($request);
            Auth::login($user);
            $request->setUserResolver(fn () => $user);

            // Session id changes on login; merge using the pre-login guest cart key.
            if (is_string($guestSessionId) && $guestSessionId !== '') {
                $this->cartService->mergeGuestCart($guestSessionId, $user->id);
            }
        }

        $cart = $this->cartService->resolveForRequest($request);

        try {
            $order = $this->checkoutService->placeOrder(
                cart: $cart,
                user: $user,
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

    protected function registerGuestCustomer(PlaceOrderRequest $request): User
    {
        $shipping = $request->input('shipping_address', []);
        $firstName = trim((string) ($shipping['first_name'] ?? ''));
        $lastName = trim((string) ($shipping['last_name'] ?? ''));
        $name = trim($firstName.' '.$lastName) ?: __('ecommerce.guest_customer');

        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower(trim((string) ($shipping['email'] ?? ''))),
            'password' => Hash::make((string) $request->input('password')),
            'phone' => trim((string) ($shipping['phone'] ?? '')) ?: null,
            'alternate_phone' => filled($shipping['alternate_phone'] ?? null)
                ? trim((string) $shipping['alternate_phone'])
                : null,
            'locale' => 'ar',
            'currency' => 'EGP',
        ]);

        event(new Registered($user));

        return $user;
    }
}
