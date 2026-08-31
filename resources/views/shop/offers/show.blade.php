@php
    use App\Support\ProductMedia;
    $banner = ProductMedia::url($offer->banner_image);
    $gallery = collect($offer->galleryPaths())->map(fn ($path) => ProductMedia::url($path))->filter();
    $offersJsPath = public_path('js/shop-offers.js');
    $offersJsVersion = is_file($offersJsPath) ? filemtime($offersJsPath) : time();
@endphp
@extends('layouts.shop')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-offers.css') }}?v={{ filemtime(public_path('css/shop-offers.css')) }}">
@endpush

@section('content')
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('shop.home') }}" class="hover:text-amber-700">{{ __('ecommerce.home') }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('shop.offers.index') }}" class="hover:text-amber-700">{{ __('ecommerce.installment_offers') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ $offer->name }}</span>
    </nav>

    <header class="hb-offer-hero">
        <div class="hb-offer-hero__top">
            <div class="hb-offer-hero__copy">
                <span class="hb-offer-hero__badge">
                    {{ $offer->plansLabel() }}
                </span>
                <h1>{{ $offer->name }}</h1>
                @if($offer->description)
                    <p class="hb-offer-hero__desc">{{ $offer->description }}</p>
                @endif
                <p class="hb-offer-hero__hint">{{ __('ecommerce.offer_set_hint') }}</p>
                <div class="hb-offer-hero__meta">
                    <span>{{ trans_choice('ecommerce.offer_products_count', $offer->products->count(), ['count' => $offer->products->count()]) }}</span>
                    @if($offer->ends_at)
                        <p class="flash-countdown text-amber-700 font-medium"
                           data-ends="{{ $offer->ends_at->toIso8601String() }}"
                           data-label="{{ __('ecommerce.offer_ends_in') }} "></p>
                    @endif
                </div>
            </div>
            @if($banner)
                <div class="hb-offer-hero__banner">
                    <img src="{{ $banner }}" alt="{{ $offer->name }}">
                </div>
            @endif
        </div>

        <div class="hb-offer-buy"
             data-offer-buy
             data-offer-cart-url="{{ route('shop.offers.cart', $offer->slug) }}"
             data-added-label="{{ __('ecommerce.added_to_cart') }}"
             data-error-label="{{ __('ecommerce.add_to_cart_error') }}">
            <div class="hb-offer-buy__prices">
                <p class="hb-offer-buy__label">{{ __('ecommerce.offer_set_total') }}</p>
                <p class="hb-offer-buy__total">{{ number_format($offerTotal, 2) }} {{ __('EGP') }}</p>
                @if($compareTotal > $offerTotal)
                    <p class="hb-offer-buy__compare">{{ number_format($compareTotal, 2) }} {{ __('EGP') }}</p>
                @endif
                <div class="hb-offer-buy__plan">
                    <p class="hb-offer-buy__label">{{ __('ecommerce.installment_choose_plan') }}</p>
                    <div class="hb-offer-plans" role="radiogroup" aria-label="{{ __('ecommerce.installment_choose_plan') }}">
                        @foreach($installmentPlans as $index => $plan)
                            <label class="hb-offer-plan">
                                <input type="radio"
                                       name="installment_months"
                                       value="{{ $plan['months'] }}"
                                       @checked($index === 0)>
                                <span class="hb-offer-plan__copy">
                                    <span class="hb-offer-plan__months">{{ __('ecommerce.installment_plan_option', ['count' => $plan['months']]) }}</span>
                                    <span class="hb-offer-plan__amount">{{ __('ecommerce.installment_monthly_each', ['amount' => number_format($plan['monthly_amount'], 2)]) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="hb-offer-buy__plan-hint">{{ __('ecommerce.installment_on_offer_total') }}</p>
                </div>
            </div>
            <button type="button"
                    class="hb-product-card-cart hb-offer-buy__cta"
                    data-offer-add
                    @disabled(! $canBuySet)>
                {{ $canBuySet ? __('ecommerce.offer_buy_set') : __('ecommerce.offer_set_unavailable') }}
            </button>
        </div>
    </header>

    @if($gallery->isNotEmpty())
        <div class="hb-offer-gallery">
            @foreach($gallery as $url)
                <img src="{{ $url }}" alt="">
            @endforeach
        </div>
    @endif

    <section class="hb-offer-products-section">
        <div class="hb-offer-products-section__head">
            <div>
                <h2>{{ __('ecommerce.offer_includes') }}</h2>
                <p class="hb-offer-products-section__hint">{{ __('ecommerce.offer_set_hint') }}</p>
            </div>
        </div>
        <div class="hb-offer-products">
            @foreach($offer->products as $entry)
                @include('shop.partials.offer-product-card', ['entry' => $entry])
            @endforeach
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/shop-offers.js') }}?v={{ $offersJsVersion }}" defer></script>
@endpush
