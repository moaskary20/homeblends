@extends('layouts.shop')

@section('content')
    <h1 class="text-3xl font-bold mb-8">{{ __('ecommerce.checkout') }}</h1>

    <form id="checkout-form" class="grid md:grid-cols-2 gap-8 max-w-4xl"
          data-api="{{ url('/api/v1') }}"
          data-checkout-url="{{ route('shop.checkout.store') }}"
          data-login-url="{{ route('login') }}"
          data-orders-url="{{ url('/orders') }}"
          data-order-url-template="{{ route('shop.orders.show', ['orderNumber' => '__ORDER__']) }}"
          data-login-required="{{ __('ecommerce.login_required_checkout') }}"
          data-session-id="{{ session()->getId() }}">
        @csrf
        <div class="space-y-4 bg-white p-6 rounded-xl shadow-sm">
            <h2 class="font-bold text-lg">{{ __('ecommerce.shipping_address') }}</h2>
            <p class="text-sm text-gray-600">{{ __('ecommerce.checkout_address_hint') }}</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.first_name') }}</label>
                    <input name="first_name" value="{{ $checkoutDefaults['first_name'] }}" required
                           class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.last_name') }}</label>
                    <input name="last_name" value="{{ $checkoutDefaults['last_name'] }}" required
                           class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.email') }}</label>
                <input name="email" type="email" value="{{ $checkoutDefaults['email'] }}" required
                       autocomplete="email" class="w-full border rounded-lg px-3 py-2">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.phone') }}</label>
                    <input name="phone" type="tel" value="{{ $checkoutDefaults['phone'] }}" required
                           placeholder="01xxxxxxxxx" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.alternate_phone') }}</label>
                    <input name="alternate_phone" type="tel" value="{{ $checkoutDefaults['alternate_phone'] }}"
                           placeholder="01xxxxxxxxx" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.address_line_1') }}</label>
                <input name="address_line_1" value="{{ $checkoutDefaults['address_line_1'] }}" required
                       placeholder="{{ __('ecommerce.address_line_1_hint') }}"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.address_line_2') }}</label>
                <input name="address_line_2" value="{{ $checkoutDefaults['address_line_2'] }}"
                       placeholder="{{ __('ecommerce.address_line_2_hint') }}"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.city') }}</label>
                    <input name="city" value="{{ $checkoutDefaults['city'] }}" required
                           class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.state') }}</label>
                    <input name="state" value="{{ $checkoutDefaults['state'] }}"
                           placeholder="{{ __('ecommerce.state_hint') }}"
                           class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ecommerce.postal_code') }}</label>
                <input name="postal_code" value="{{ $checkoutDefaults['postal_code'] }}"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <input type="hidden" name="country" value="{{ $checkoutDefaults['country'] }}">
        </div>
        <div class="space-y-4 bg-white p-6 rounded-xl shadow-sm">
            <h2 class="font-bold text-lg">الدفع والشحن</h2>
            @if($freeShippingMin)
                <p class="text-sm text-green-700 bg-green-50 rounded-lg px-3 py-2">
                    {{ __('ecommerce.free_shipping_notice', ['amount' => number_format($freeShippingMin, 0)]) }}
                </p>
            @endif
            <label class="block text-sm font-medium text-gray-700">{{ __('ecommerce.shipping_method') }}</label>
            @if($shippingRates->isEmpty())
                <p class="text-sm text-red-600">{{ __('ecommerce.shipping_unavailable') }}</p>
            @else
            <select name="shipping_rate_id" id="shipping_rate_id" required class="w-full border rounded-lg px-3 py-2">
                @foreach($shippingRates as $rate)
                    <option value="{{ $rate->id }}" data-rate="{{ $rate->rate }}" data-days="{{ $rate->estimated_days }}">
                        {{ $rate->name }} — {{ number_format($rate->rate, 2) }} ج.م
                        @if($rate->estimated_days)
                            ({{ $rate->estimated_days }} {{ __('ecommerce.days') }})
                        @endif
                    </option>
                @endforeach
            </select>
            @endif
            <p id="shipping-cost-preview" class="text-sm text-gray-600"></p>
            <label class="block text-sm font-medium text-gray-700">{{ __('ecommerce.payment_method') }}</label>
            @forelse($paymentGateways as $gateway)
                <label class="flex items-start gap-3 border rounded-lg p-3 cursor-pointer hover:border-amber-400 has-[:checked]:border-amber-600 has-[:checked]:bg-amber-50">
                    <input type="radio" name="payment_gateway" value="{{ $gateway->code }}" required
                           class="mt-1" @checked($loop->first)>
                    <span class="flex-1">
                        <span class="font-semibold block">{{ $gateway->displayName() }}</span>
                        @if($gateway->description)
                            <span class="text-sm text-gray-600 block mt-0.5">{{ $gateway->description }}</span>
                        @endif
                        @if($gateway->codFee() > 0)
                            <span class="text-sm text-amber-700 block mt-1">
                                {{ __('ecommerce.cod_fee') }}: {{ number_format($gateway->codFee(), 2) }} {{ __('EGP') }}
                            </span>
                        @endif
                        @if($gateway->instructions)
                            <span class="text-xs text-gray-500 block mt-1">{{ $gateway->instructions }}</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="text-sm text-red-600">{{ __('ecommerce.payment_gateway_unavailable') }}</p>
            @endforelse
            <div id="loyalty-section" class="hidden border rounded-lg p-4 bg-amber-50 space-y-2">
                <p class="font-semibold text-sm">{{ __('ecommerce.loyalty_redeem') }}</p>
                <p id="loyalty-balance" class="text-sm text-gray-600"></p>
                <p id="loyalty-vip" class="text-sm text-amber-700 hidden"></p>
                <div class="flex gap-2 items-center">
                    <input type="number" id="loyalty_points" name="loyalty_points" min="0" value="0"
                           class="border rounded-lg px-3 py-2 w-full">
                    <span class="text-sm whitespace-nowrap" id="loyalty-discount-preview">0.00 ج.م</span>
                </div>
                <p id="loyalty-hint" class="text-xs text-gray-500">{{ __('ecommerce.loyalty_redeem_hint') }}</p>
            </div>
            <textarea name="notes" placeholder="ملاحظات الطلب" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
            <button type="submit" @disabled($paymentGateways->isEmpty() || $shippingRates->isEmpty())
                    class="w-full bg-amber-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                تأكيد الطلب
            </button>
            <p id="checkout-error" class="text-red-600 text-sm hidden"></p>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/shop-checkout.js') }}?v={{ is_file(public_path('js/shop-checkout.js')) ? filemtime(public_path('js/shop-checkout.js')) : time() }}" defer></script>
@endpush
