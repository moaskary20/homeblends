@extends('layouts.shop')

@section('body_class', 'design-team-page')
@section('main_class', 'flex-1 w-full p-0 max-w-none')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-design-team.css') }}?v={{ filemtime(public_path('css/shop-design-team.css')) }}">
    <script src="{{ asset('js/shop-design-team.js') }}?v={{ filemtime(public_path('js/shop-design-team.js')) }}" defer></script>
@endpush

@section('content')
    @php
        $page = $designTeam ?? [];
    @endphp

    <section class="hb-dt-hero" aria-labelledby="design-team-title">
        @if(!empty($page['hero']['image_url']))
            <div class="hb-dt-hero__bg" aria-hidden="true">
                <img src="{{ $page['hero']['image_url'] }}" alt="" loading="eager" decoding="async">
            </div>
        @endif
        <div class="hb-dt-hero__overlay"></div>
        <div class="hb-dt-hero__inner">
            @if(filled($page['hero']['eyebrow'] ?? null))
                <p class="hb-dt-eyebrow">{{ $page['hero']['eyebrow'] }}</p>
            @endif
            <h1 id="design-team-title" class="hb-dt-hero__title">{{ $page['hero']['title'] ?? $page['page_title'] }}</h1>
            @if(filled($page['hero']['subtitle'] ?? null))
                <p class="hb-dt-hero__subtitle">{{ $page['hero']['subtitle'] }}</p>
            @endif
            @if(filled($page['hero']['cta'] ?? null))
                <a href="{{ $page['hero']['cta_url'] }}" class="hb-dt-hero__cta">{{ $page['hero']['cta'] }}</a>
            @endif
        </div>
    </section>

    @if(($page['how_it_works']['is_active'] ?? false) && !empty($page['how_it_works']['steps']))
        <section class="hb-dt-section hb-dt-how" aria-labelledby="how-it-works-title">
            <div class="hb-dt-section__inner">
                <header class="hb-dt-section__header hb-dt-reveal">
                    <h2 id="how-it-works-title" class="hb-dt-section__title">{{ $page['how_it_works']['title'] }}</h2>
                    @if(filled($page['how_it_works']['subtitle'] ?? null))
                        <p class="hb-dt-section__subtitle">{{ $page['how_it_works']['subtitle'] }}</p>
                    @endif
                </header>
                <div class="hb-dt-steps">
                    @foreach($page['how_it_works']['steps'] as $index => $step)
                        <article class="hb-dt-step">
                            @if(!empty($step['image_url']))
                                <div class="hb-dt-step__media">
                                    <img src="{{ $step['image_url'] }}" alt="{{ $step['title'] }}" loading="lazy" decoding="async">
                                </div>
                            @endif
                            <div class="hb-dt-reveal" data-delay="{{ $index * 80 }}">
                                <h3 class="hb-dt-step__title">{{ $step['title'] }}</h3>
                                <p class="hb-dt-step__text">{{ $step['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(($page['meeting_ways']['is_active'] ?? false) && !empty($page['meeting_ways']['items']))
        <section class="hb-dt-section hb-dt-meeting" aria-labelledby="meeting-ways-title">
            <div class="hb-dt-section__inner">
                <header class="hb-dt-section__header hb-dt-reveal">
                    <h2 id="meeting-ways-title" class="hb-dt-section__title">{{ $page['meeting_ways']['title'] }}</h2>
                    @if(filled($page['meeting_ways']['subtitle'] ?? null))
                        <p class="hb-dt-section__subtitle">{{ $page['meeting_ways']['subtitle'] }}</p>
                    @endif
                </header>
                <div class="hb-dt-meeting__grid">
                    @foreach($page['meeting_ways']['items'] as $index => $item)
                        <article class="hb-dt-meeting__card hb-dt-reveal" data-delay="{{ $index * 100 }}">
                            <h3 class="hb-dt-meeting__badge">{{ $item['badge'] }}</h3>
                            @if(filled($item['type'] ?? null))
                                <p class="hb-dt-meeting__type">({{ $item['type'] }})</p>
                            @endif
                            <p class="hb-dt-meeting__text">{{ $item['description'] }}</p>
                            @if(filled($item['cta'] ?? null))
                                <a href="{{ $item['url'] }}" class="hb-dt-link">{{ $item['cta'] }}</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(($page['services']['is_active'] ?? false) && !empty($page['services']['items']))
        <section class="hb-dt-section hb-dt-services" aria-labelledby="services-title">
            <div class="hb-dt-section__inner">
                <header class="hb-dt-section__header hb-dt-reveal">
                    <h2 id="services-title" class="hb-dt-section__title">{{ $page['services']['title'] }}</h2>
                    @if(filled($page['services']['subtitle'] ?? null))
                        <p class="hb-dt-section__subtitle">{{ $page['services']['subtitle'] }}</p>
                    @endif
                </header>
                <div class="hb-dt-services__grid">
                    @foreach($page['services']['items'] as $index => $item)
                        <article class="hb-dt-service hb-dt-reveal" data-delay="{{ ($index % 3) * 60 }}">
                            <span class="hb-dt-service__icon" aria-hidden="true">
                                @include('shop.partials.design-team-icon', ['icon' => $item['icon'] ?? 'clock'])
                            </span>
                            <h3 class="hb-dt-service__title">{{ $item['title'] }}</h3>
                            <p class="hb-dt-service__text">{{ $item['description'] }}</p>
                            @if(filled($item['note'] ?? null))
                                <p class="hb-dt-service__note">— {{ $item['note'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(($page['faq']['is_active'] ?? false) && !empty($page['faq']['items']))
        <section class="hb-dt-section hb-dt-faq" aria-labelledby="faq-title">
            <div class="hb-dt-section__inner hb-dt-faq__inner">
                <h2 id="faq-title" class="hb-dt-section__title hb-dt-faq__title hb-dt-reveal">{{ $page['faq']['title'] }}</h2>
                <div class="hb-dt-faq__list hb-dt-reveal" data-delay="80">
                    @foreach($page['faq']['items'] as $item)
                        <details class="hb-dt-faq__item">
                            <summary class="hb-dt-faq__question">
                                <span>{{ $item['question'] }}</span>
                                <span class="hb-dt-faq__toggle" aria-hidden="true"></span>
                            </summary>
                            <div class="hb-dt-faq__answer">
                                <p>{{ $item['answer'] }}</p>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
