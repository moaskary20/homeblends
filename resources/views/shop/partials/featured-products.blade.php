@php
    $featuredProducts = $featured ?? collect();
    $perView = max(1, (int) config('homepage.featured_products_per_view', 4));
@endphp

<section class="hb-featured-products hb-home-section max-w-[1400px] mx-auto" id="featured-products" aria-labelledby="featured-products-title">
    <div class="hb-featured-header">
        <h2 id="featured-products-title" class="hb-section-title mb-0">{{ __('Featured Products') }}</h2>
        <div class="hb-featured-header__actions">
            @if($featuredProducts->count() > $perView)
                <div class="hb-featured-nav" aria-hidden="false">
                    <button type="button" class="hb-featured-nav-btn hb-featured-prev" aria-label="{{ __('ecommerce.carousel_prev') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <button type="button" class="hb-featured-nav-btn hb-featured-next" aria-label="{{ __('ecommerce.carousel_next') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                </div>
            @endif
            <a href="{{ route('shop.products.index') }}" class="hb-featured-all">{{ __('Shop Now') }} →</a>
        </div>
    </div>

    @if($featuredProducts->isEmpty())
        <p class="text-gray-700">{{ __('No products yet.') }}</p>
    @else
        <div class="hb-featured-viewport" data-per-view="{{ $perView }}">
            <div class="hb-featured-track">
                @foreach($featuredProducts as $product)
                    <div class="hb-featured-slide">
                        @include('shop.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
