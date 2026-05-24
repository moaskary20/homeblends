@extends('layouts.shop')

@section('body_class', 'about-page')
@section('main_class', 'flex-1 w-full p-0 max-w-none')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-about.css') }}?v={{ filemtime(public_path('css/shop-about.css')) }}">
    <script src="{{ asset('js/shop-about.js') }}?v={{ filemtime(public_path('js/shop-about.js')) }}" defer></script>
@endpush

@section('content')
    @if(!empty($about['intro']['is_active']))
        <section class="hb-about-hero" aria-labelledby="about-intro-title">
            <div class="hb-about-hero__bg" aria-hidden="true">
                @if(!empty($about['intro']['image_url']))
                    <img src="{{ $about['intro']['image_url'] }}" alt="" loading="eager" decoding="async">
                @endif
            </div>
            <div class="hb-about-hero__overlay"></div>
            <div class="hb-about-hero__inner">
                <div class="hb-about-hero__content hb-about-reveal">
                    @if(filled($about['intro']['eyebrow'] ?? null))
                        <p class="hb-about-eyebrow">{{ $about['intro']['eyebrow'] }}</p>
                    @endif
                    <h1 id="about-intro-title" class="hb-about-hero__title">{{ $about['intro']['title'] ?? $about['page_title'] }}</h1>
                </div>
            </div>
        </section>

        <section class="hb-about-intro">
            <div class="hb-about-intro__inner">
                <div class="hb-about-intro__text hb-about-reveal">
                    @foreach($about['intro']['paragraphs'] ?? [] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
                @if(!empty($about['intro']['image_url']))
                    <div class="hb-about-intro__media hb-about-reveal" data-delay="120">
                        <div class="hb-about-intro__frame">
                            <img
                                src="{{ $about['intro']['image_url'] }}"
                                alt="{{ $about['intro']['title'] ?? '' }}"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <span class="hb-about-intro__accent" aria-hidden="true"></span>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(!empty($about['ceo']['is_active']) && filled($about['ceo']['quote'] ?? null))
        <section class="hb-about-ceo" aria-labelledby="about-ceo-title">
            <div class="hb-about-ceo__pattern" aria-hidden="true"></div>
            <div class="hb-about-ceo__inner">
                <div class="hb-about-ceo__header hb-about-reveal">
                    <h2 id="about-ceo-title" class="hb-about-section-title">{{ $about['ceo']['section_title'] }}</h2>
                </div>
                <div class="hb-about-ceo__card hb-about-reveal" data-delay="80">
                    <div class="hb-about-ceo__quote-wrap">
                        <svg class="hb-about-ceo__quote-icon" viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="currentColor" d="M14 28c0-5.5 4.5-10 10-10V12C14.8 12 8 18.8 8 27v11h16V28H14zm20 0c0-5.5 4.5-10 10-10V12C34.8 12 28 18.8 28 27v11h16V28H34z"/>
                        </svg>
                        <blockquote class="hb-about-ceo__quote">
                            @foreach(preg_split('/\R+/u', trim($about['ceo']['quote'])) as $line)
                                @if(filled($line))
                                    <p>{{ $line }}</p>
                                @endif
                            @endforeach
                        </blockquote>
                    </div>
                    <div class="hb-about-ceo__profile">
                        @if(!empty($about['ceo']['image_url']))
                            <div class="hb-about-ceo__photo">
                                <img
                                    src="{{ $about['ceo']['image_url'] }}"
                                    alt="{{ $about['ceo']['name'] ?? '' }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        @endif
                        <div class="hb-about-ceo__meta">
                            @if(filled($about['ceo']['name'] ?? null))
                                <p class="hb-about-ceo__name">{{ $about['ceo']['name'] }}</p>
                            @endif
                            @if(filled($about['ceo']['title'] ?? null))
                                <p class="hb-about-ceo__role">{{ $about['ceo']['title'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(!empty($about['services']['is_active']) && !empty($about['services']['items']))
        <section class="hb-about-services" aria-labelledby="about-services-title">
            <div class="hb-about-services__inner">
                <div class="hb-about-services__header hb-about-reveal">
                    <h2 id="about-services-title" class="hb-about-section-title">{{ $about['services']['section_title'] }}</h2>
                    @if(filled($about['services']['section_subtitle'] ?? null))
                        <p class="hb-about-section-subtitle">{{ $about['services']['section_subtitle'] }}</p>
                    @endif
                </div>

                <div class="hb-about-services__grid">
                    @foreach($about['services']['items'] as $index => $service)
                        <article
                            class="hb-about-service-card hb-about-reveal"
                            data-delay="{{ $index * 100 }}"
                            style="--hb-service-index: {{ $index }}"
                        >
                            @if(!empty($service['image_url']))
                                <div class="hb-about-service-card__media">
                                    <img
                                        src="{{ $service['image_url'] }}"
                                        alt="{{ $service['title'] ?? '' }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <span class="hb-about-service-card__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            @endif
                            <div class="hb-about-service-card__body">
                                <div class="hb-about-service-card__head">
                                    <h3 class="hb-about-service-card__title">{{ $service['title'] ?? '' }}</h3>
                                    @if(filled($service['title_en'] ?? null))
                                        <p class="hb-about-service-card__title-en">{{ $service['title_en'] }}</p>
                                    @endif
                                </div>
                                @if(!empty($service['bullets']))
                                    <ul class="hb-about-service-card__list">
                                        @foreach($service['bullets'] as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('shop.partials.partners-strip', ['partners' => $partners ?? []])
@endsection
