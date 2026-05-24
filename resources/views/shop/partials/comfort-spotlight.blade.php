@php
    $spotlight = $comfortSpotlight ?? null;
@endphp

@if(!empty($spotlight))
    <section class="hb-comfort-spotlight" id="comfort-spotlight" aria-labelledby="comfort-spotlight-title">
        <div class="hb-comfort-spotlight__inner">
            <div class="hb-comfort-spotlight__content">
                @if(filled($spotlight['eyebrow'] ?? null))
                    <p class="hb-comfort-spotlight__eyebrow">{{ $spotlight['eyebrow'] }}</p>
                @endif

                @if(filled($spotlight['title'] ?? null))
                    <h2 id="comfort-spotlight-title" class="hb-comfort-spotlight__title">{{ $spotlight['title'] }}</h2>
                @endif

                @if(!empty($spotlight['links']))
                    <nav class="hb-comfort-spotlight__links" aria-label="{{ $spotlight['title'] }}">
                        @foreach($spotlight['links'] as $link)
                            <a href="{{ url($link['url']) }}">{{ $link['name'] }}</a>
                        @endforeach
                    </nav>
                @endif

                @if(!empty($spotlight['thumbs']))
                    <div class="hb-comfort-spotlight__thumbs">
                        @foreach($spotlight['thumbs'] as $thumb)
                            <a href="{{ $thumb['url'] }}" class="hb-comfort-spotlight__thumb" title="{{ $thumb['name'] }}">
                                <img src="{{ $thumb['image'] }}" alt="{{ $thumb['name'] }}" loading="lazy" decoding="async">
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(filled($spotlight['description'] ?? null))
                    <p class="hb-comfort-spotlight__desc">{{ $spotlight['description'] }}</p>
                @endif

                @if(filled($spotlight['cta'] ?? null))
                    <a href="{{ url($spotlight['url'] ?? route('shop.products.index')) }}" class="hb-comfort-spotlight__btn">
                        <span>{{ $spotlight['cta'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75" /></svg>
                    </a>
                @endif
            </div>

            @if(!empty($spotlight['image_url']))
                <div class="hb-comfort-spotlight__media">
                    <img
                        src="{{ $spotlight['image_url'] }}"
                        alt="{{ $spotlight['title'] ?? '' }}"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            @endif
        </div>
    </section>
@endif
