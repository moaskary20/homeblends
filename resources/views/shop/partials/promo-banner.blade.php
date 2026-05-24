@php
    $banner = $promoBanner ?? [];
@endphp

@if(!empty($banner['is_active']) && !empty($banner['image_url']))
    <section class="hb-promo-banner" id="home-promo-banner" aria-label="{{ $banner['cta'] ?? __('Shop Now') }}">
        <div class="hb-promo-banner__frame">
            <img
                class="hb-promo-banner__img"
                src="{{ $banner['image_url'] }}"
                alt=""
                loading="eager"
                fetchpriority="high"
                decoding="async"
            >
            @if(filled($banner['cta'] ?? null))
                <div class="hb-promo-banner__overlay">
                    <a href="{{ url($banner['url'] ?? route('shop.products.index')) }}" class="hb-promo-banner__btn">
                        {{ $banner['cta'] }}
                    </a>
                </div>
            @endif
        </div>
    </section>
@endif
