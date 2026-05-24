@extends('layouts.shop')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-cart.css') }}">
@endpush

@section('content')
    <div class="hb-cart-page max-w-[1200px] mx-auto">
        <nav class="text-sm text-gray-600 mb-4">
            <a href="{{ route('shop.home') }}" class="hover:text-amber-700">{{ __('ecommerce.home') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">{{ __('ecommerce.cart') }}</span>
        </nav>

        <h1 class="text-3xl font-bold text-[#3d3830] mb-8">{{ __('ecommerce.cart') }}</h1>

        @php $cartHasItems = $cart->items->isNotEmpty(); @endphp
        <div id="cart-app"
             data-session-id="{{ session()->getId() }}"
             data-api="{{ url('/api/v1') }}"
             data-has-items="{{ $cartHasItems ? '1' : '0' }}"
             data-initial-count="{{ $totals['items_count'] ?? 0 }}"
             data-product-url-template="{{ route('shop.products.show', ['slug' => '__SLUG__']) }}"
             data-bundles-url="{{ route('shop.bundles.index') }}"
             class="hb-cart-layout">

            <div class="hb-cart-main">
                <p id="cart-loading" class="text-gray-500 {{ $cartHasItems ? 'hb-cart-hidden' : '' }}">
                    {{ __('ecommerce.cart_loading') }}
                </p>

                <div id="cart-empty" class="{{ $cartHasItems ? 'hb-cart-hidden' : '' }} hb-cart-empty">
                    <div class="text-5xl mb-4">🛒</div>
                    <p class="text-lg text-gray-600 mb-6">{{ __('ecommerce.cart_empty') }}</p>
                    <a href="{{ route('shop.products.index') }}" class="hb-cart-btn-primary inline-block">
                        {{ __('ecommerce.continue_shopping') }}
                    </a>
                </div>

                <div id="cart-items" class="{{ $cartHasItems ? '' : 'hb-cart-hidden' }} bg-white rounded-xl shadow-sm overflow-hidden">
                    @foreach($cart->items as $item)
                        @include('shop.partials.cart-line', ['item' => $item])
                    @endforeach
                </div>
            </div>

            <aside id="cart-sidebar" class="hb-cart-sidebar {{ $cartHasItems ? '' : 'hb-cart-hidden' }}">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24 space-y-4">
                    <h2 class="font-bold text-lg text-[#3d3830]">{{ __('ecommerce.order_summary') }}</h2>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ __('ecommerce.items_count_label') }}</span>
                            <span id="summary-items-count">{{ $totals['items_count'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>{{ __('ecommerce.subtotal') }}</span>
                            <span id="cart-subtotal" class="text-amber-800">
                                {{ number_format($totals['subtotal'] ?? 0, 2) }} {{ __('ecommerce.currency') }}
                            </span>
                        </div>
                    </div>

                    <div id="coupon-message" class="hidden text-sm rounded-lg px-3 py-2"></div>

                    <div class="flex flex-col gap-2">
                        <input type="text" id="coupon-code" placeholder="{{ __('ecommerce.coupon_code') }}"
                               value="{{ $cart->coupon_code }}"
                               class="border border-gray-200 rounded-lg px-3 py-2 w-full text-sm">
                        <button type="button" id="apply-coupon" class="hb-cart-btn-secondary w-full">
                            {{ __('ecommerce.apply_coupon') }}
                        </button>
                    </div>

                    <a href="{{ route('shop.checkout') }}" id="checkout-btn" class="hb-cart-btn-primary block text-center w-full">
                        {{ __('ecommerce.proceed_checkout') }}
                    </a>

                    <a href="{{ route('shop.products.index') }}" class="block text-center text-sm text-gray-600 hover:text-amber-700">
                        {{ __('ecommerce.continue_shopping') }}
                    </a>

                    @auth
                        <button type="button" id="save-later" class="w-full text-sm text-gray-600 hover:text-amber-700 border-t pt-4">
                            {{ __('ecommerce.save_for_later') }}
                        </button>
                    @endauth
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    @if (file_exists(public_path('build/manifest.json')) && isset(json_decode(file_get_contents(public_path('build/manifest.json')), true)['resources/js/shop-cart.js']))
        @vite(['resources/js/shop-cart.js'])
    @else
        <script src="{{ asset('js/shop-cart.js') }}" defer></script>
    @endif
@endpush
