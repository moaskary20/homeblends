@extends('layouts.shop')

@php
    $salePrice = (float) ($flashPricing['price'] ?? $product->effective_price);
    $isFlash = (bool) ($flashPricing['is_flash_sale'] ?? false);
    $hasDiscount = $product->hasActiveTimedDiscount();
    $comparePrice = $isFlash
        ? (float) ($flashPricing['compare_price'] ?? 0)
        : ($hasDiscount ? (float) $product->regular_price : 0);
    $inStock = $product->stock_quantity > 0;
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-categories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop-product-page.css') }}">
@endpush

@section('body_class')
    bg-[#f5f0e6] hb-pdp-has-mobile-bar
@endsection

@section('main_class', 'flex-1 w-full min-w-0 max-w-7xl mx-auto px-4 py-8 overflow-x-clip')

@section('content')
    <article class="hb-pdp" data-product-page
             data-product-id="{{ $product->id }}"
             data-api="{{ url('/api/v1') }}"
             data-session-id="{{ session()->getId() }}"
             data-cart-url="{{ route('shop.cart') }}"
             data-added-label="{{ __('ecommerce.added_to_cart') }}"
             data-error-label="{{ __('ecommerce.add_to_cart_error') }}">

        <nav class="hb-pdp-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('shop.home') }}">{{ __('ecommerce.home') }}</a>
            <span aria-hidden="true">/</span>
            @if($product->category)
                <a href="{{ route('shop.categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
                <span aria-hidden="true">/</span>
            @endif
            <span>{{ $product->name }}</span>
        </nav>

        <div class="hb-pdp-grid">
            <div class="hb-pdp-gallery-col">
                <div class="hb-pdp-gallery">
                    @include('shop.partials.product-gallery', ['product' => $product])
                </div>
            </div>

            <div class="hb-pdp-info-col">
                <div class="hb-pdp-badges">
                    @if($isFlash)
                        <span class="hb-pdp-badge is-flash">
                            {{ __('ecommerce.flash_sale_badge') }}
                            @if(($flashPricing['discount_percent'] ?? 0) > 0)
                                −{{ $flashPricing['discount_percent'] }}%
                            @endif
                        </span>
                    @elseif($hasDiscount)
                        <span class="hb-pdp-badge is-discount">{{ __('ecommerce.discount_badge') }}</span>
                    @endif
                    <span class="hb-pdp-badge {{ $inStock ? 'is-stock-ok' : 'is-stock-no' }}">
                        {{ $inStock ? __('ecommerce.product_in_stock') : __('ecommerce.out_of_stock') }}
                    </span>
                </div>

                <h1 class="hb-pdp-title">{{ $product->name }}</h1>

                @if($product->reviews_count > 0)
                    <div class="hb-pdp-rating">
                        <span class="hb-pdp-rating-stars">★ {{ number_format((float) $product->avg_rating, 1) }}</span>
                        <span>({{ $product->reviews_count }} {{ __('ecommerce.reviews') }})</span>
                    </div>
                @endif

                <div class="hb-pdp-price-box">
                    <p class="hb-pdp-price-current">{{ number_format($salePrice, 2) }} {{ __('ecommerce.currency') }}</p>
                    @if($comparePrice > $salePrice)
                        <p class="hb-pdp-price-compare">{{ number_format($comparePrice, 2) }} {{ __('ecommerce.currency') }}</p>
                    @endif
                    @if($hasDiscount && $product->discount_ends_at)
                        <p class="flash-countdown hb-pdp-countdown text-amber-700"
                           data-ends="{{ $product->discount_ends_at->toIso8601String() }}"
                           data-label="{{ __('ecommerce.discount_ends_in') }} "></p>
                    @endif
                    @if($flashPricing['flash_sale_product']?->flashSale?->ends_at)
                        <p class="flash-countdown hb-pdp-countdown text-red-600"
                           data-ends="{{ $flashPricing['flash_sale_product']->flashSale->ends_at->toIso8601String() }}"
                           data-label="{{ __('ecommerce.flash_ends_in') }} "></p>
                    @endif
                    @if(($flashPricing['flash_sale_product']?->remainingQuantity()) !== null)
                        <p class="text-sm text-orange-600 mt-1">
                            {{ __('ecommerce.flash_remaining', ['count' => $flashPricing['flash_sale_product']->remainingQuantity()]) }}
                        </p>
                    @endif
                </div>

                @if($product->short_description)
                    <p class="hb-pdp-short">{{ $product->short_description }}</p>
                @endif

                <div class="hb-pdp-meta">
                    @if($product->sku)
                        <span class="hb-pdp-meta-item"><strong>{{ __('ecommerce.sku') }}:</strong> {{ $product->sku }}</span>
                    @endif
                    @if($product->category)
                        <span class="hb-pdp-meta-item"><strong>{{ __('ecommerce.category') }}:</strong> {{ $product->category->name }}</span>
                    @endif
                    @if($product->weight)
                        <span class="hb-pdp-meta-item"><strong>{{ __('ecommerce.weight') }}:</strong> {{ number_format((float) $product->weight, 2) }} {{ __('ecommerce.kg') }}</span>
                    @endif
                    @if($product->dimensions)
                        <span class="hb-pdp-meta-item"><strong>{{ __('ecommerce.dimensions') }}:</strong> {{ $product->dimensions }}</span>
                    @endif
                </div>

                <div class="hb-pdp-buy-box">
                    @if($product->variants->isNotEmpty())
                        <label class="hb-pdp-variant-label" for="variant-select">{{ __('ecommerce.choose_variant') }}</label>
                        <select id="variant-select" class="hb-pdp-variant-select">
                            @foreach($product->variants as $variant)
                                <option value="{{ $variant->id }}" data-price="{{ $variant->price }}">
                                    {{ $variant->sku }} — {{ number_format($variant->price, 2) }} {{ __('ecommerce.currency') }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <div class="hb-pdp-qty-row">
                        <span class="hb-pdp-qty-label">{{ __('ecommerce.quantity') }}</span>
                        <div class="hb-pdp-qty-control">
                            <button type="button" class="hb-pdp-qty-btn" data-qty-minus aria-label="-">−</button>
                            <input type="number" class="hb-pdp-qty-input" id="product-qty" value="1" min="1" max="{{ max(1, $product->stock_quantity) }}" aria-label="{{ __('ecommerce.quantity') }}">
                            <button type="button" class="hb-pdp-qty-btn" data-qty-plus aria-label="+">+</button>
                        </div>
                    </div>

                    <div class="hb-pdp-actions-primary">
                        <button type="button"
                                id="pdp-add-to-cart"
                                class="hb-pdp-btn-cart"
                                data-pdp-add-cart
                                {{ $inStock ? '' : 'disabled' }}>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            {{ __('ecommerce.add_to_cart') }}
                        </button>
                    </div>

                    <div class="hb-pdp-actions-secondary">
                        <button type="button"
                                class="hb-pdp-btn-icon is-wishlist {{ $inWishlist ? 'is-active' : '' }}"
                                data-product-wishlist
                                data-product-id="{{ $product->id }}"
                                data-favorite-url="{{ url('/api/v1/wishlist/'.$product->id.'/toggle') }}"
                                data-login-url="{{ route('login') }}"
                                data-add-label="{{ __('ecommerce.add_to_favorites') }}"
                                data-remove-label="{{ __('ecommerce.remove_from_favorites') }}">
                            <svg fill="{{ $inWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span>{{ $inWishlist ? __('ecommerce.remove_from_favorites') : __('ecommerce.add_to_favorites') }}</span>
                        </button>
                        <button type="button"
                                class="hb-pdp-btn-icon is-compare {{ $inCompare ? 'is-active' : '' }}"
                                data-product-compare
                                data-product-id="{{ $product->id }}"
                                data-compare-url="{{ route('shop.compare.toggle', $product) }}"
                                data-login-url="{{ route('login') }}"
                                data-add-label="{{ __('ecommerce.add_to_compare') }}"
                                data-remove-label="{{ __('ecommerce.remove_from_compare') }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>{{ $inCompare ? __('ecommerce.remove_from_compare') : __('ecommerce.add_to_compare') }}</span>
                        </button>
                    </div>

                    <div class="hb-pdp-trust">
                        <span>🚚 {{ __('ecommerce.trust_shipping') }}</span>
                        <span>🔒 {{ __('ecommerce.trust_secure') }}</span>
                        <span>↩️ {{ __('ecommerce.trust_returns') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hb-pdp-sections">
            <div class="hb-pdp-tabs" role="tablist">
                <button type="button" class="hb-pdp-tab is-active" data-pdp-tab="description" role="tab" aria-selected="true">
                    {{ __('ecommerce.product_description') }}
                </button>
                <button type="button" class="hb-pdp-tab" data-pdp-tab="reviews" role="tab" aria-selected="false">
                    {{ __('ecommerce.reviews') }}
                    @if($product->reviews_count > 0)
                        ({{ $product->reviews_count }})
                    @endif
                </button>
            </div>

            <div class="hb-pdp-panel is-active" data-pdp-panel="description" role="tabpanel">
                @if($product->full_description)
                    <div class="prose">{!! $product->full_description !!}</div>
                @else
                    <p class="text-gray-500">{{ __('ecommerce.no_description') }}</p>
                @endif
            </div>

            <div class="hb-pdp-panel" data-pdp-panel="reviews" role="tabpanel">
                @if($product->reviews->isNotEmpty())
                    <div class="hb-pdp-reviews">
                        @foreach($product->reviews as $review)
                            <div class="hb-pdp-review">
                                <p class="text-amber-600 font-semibold mb-1">★ {{ $review->rating }}/5</p>
                                @if($review->comment)
                                    <p class="text-gray-600 text-sm leading-relaxed">{{ $review->comment }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-6">{{ __('ecommerce.no_reviews_yet') }}</p>
                @endif
            </div>
        </div>

        <div class="hb-pdp-toast" data-pdp-toast role="status" aria-live="polite"></div>

        <div class="hb-pdp-mobile-bar" aria-hidden="false">
            <button type="button"
                    class="hb-pdp-btn-icon is-wishlist {{ $inWishlist ? 'is-active' : '' }}"
                    data-product-wishlist
                    data-product-id="{{ $product->id }}"
                    data-favorite-url="{{ url('/api/v1/wishlist/'.$product->id.'/toggle') }}"
                    data-login-url="{{ route('login') }}"
                    aria-label="{{ __('ecommerce.my_favorites') }}">
                <svg fill="{{ $inWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
            <button type="button"
                    class="hb-pdp-btn-icon is-compare {{ $inCompare ? 'is-active' : '' }}"
                    data-product-compare
                    data-product-id="{{ $product->id }}"
                    data-compare-url="{{ route('shop.compare.toggle', $product) }}"
                    data-login-url="{{ route('login') }}"
                    aria-label="{{ __('ecommerce.my_compare') }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </button>
            <button type="button"
                    class="hb-pdp-btn-cart"
                    data-pdp-add-cart
                    {{ $inStock ? '' : 'disabled' }}>
                {{ __('ecommerce.add_to_cart') }}
            </button>
        </div>
    </article>
@endsection

@push('scripts')
    <script src="{{ asset('js/shop-product-card.js') }}" defer></script>
    <script src="{{ asset('js/shop-product-page.js') }}" defer></script>
@endpush
