@php
    $banner = $designBanner ?? [];
@endphp

@if(!empty($banner['is_active']) && !empty($banner['image_url']))
    <section class="hb-design-banner is-visible" id="design-team-banner" aria-labelledby="design-banner-title">
        <img
            class="hb-design-banner__bg"
            src="{{ $banner['image_url'] }}"
            alt="{{ $banner['title'] }}"
            loading="lazy"
            decoding="async"
        >
        <div class="hb-design-banner__overlay">
            <div class="hb-design-banner__content">
                @if(filled($banner['eyebrow'] ?? null))
                    <p class="hb-design-banner__eyebrow">{{ $banner['eyebrow'] }}</p>
                @endif
                @if(filled($banner['title'] ?? null))
                    <h2 id="design-banner-title" class="hb-design-banner__title">{{ $banner['title'] }}</h2>
                @endif
                @if(filled($banner['subtitle'] ?? null))
                    <p class="hb-design-banner__subtitle">{{ $banner['subtitle'] }}</p>
                @endif
                @if(filled($banner['cta'] ?? null))
                    <a href="{{ url($banner['url'] ?? '#contact') }}" class="hb-design-banner__btn">
                        {{ $banner['cta'] }}
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif
