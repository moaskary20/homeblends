@php
    $strip = $contactStrip ?? [];
    $items = $strip['items'] ?? [];
@endphp

@if(!empty($strip['is_active']) && count($items) > 0)
    <section class="hb-contact-strip" id="contact" aria-label="{{ __('ecommerce.contact_strip') }}">
        <div class="hb-contact-strip__inner">
            <div class="hb-contact-strip__grid">
                @foreach($items as $item)
                    <div class="hb-contact-strip__cell">
                        <div class="hb-contact-strip__item">
                            @include('shop.partials.contact-strip-icon', ['icon' => $item['icon'] ?? 'chat'])
                            <div class="hb-contact-strip__content">
                                <h3 class="hb-contact-strip__title">{{ $item['title'] }}</h3>
                                @if(filled($item['url'] ?? null))
                                    <a href="{{ $item['url'] }}" class="hb-contact-strip__text hb-contact-strip__link">
                                        {{ $item['text'] }}
                                    </a>
                                @else
                                    <p class="hb-contact-strip__text">{{ $item['text'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
