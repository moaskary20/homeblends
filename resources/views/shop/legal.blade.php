@extends('layouts.shop')

@section('body_class', 'legal-page legal-page--' . ($page['key'] ?? 'document'))
@section('main_class', 'flex-1 w-full p-0 max-w-none')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-legal.css') }}?v={{ filemtime(public_path('css/shop-legal.css')) }}">
    <script src="{{ asset('js/shop-legal.js') }}?v={{ filemtime(public_path('js/shop-legal.js')) }}" defer></script>
@endpush

@section('content')
    <section class="hb-legal-hero">
        <div class="hb-legal-hero__backdrop" aria-hidden="true">
            <span class="hb-legal-hero__orb hb-legal-hero__orb--1"></span>
            <span class="hb-legal-hero__orb hb-legal-hero__orb--2"></span>
        </div>
        <div class="hb-legal-hero__inner">
            <nav class="hb-legal-breadcrumb hb-legal-reveal" aria-label="{{ __('ecommerce.breadcrumb') }}">
                <a href="{{ route('shop.home') }}">{{ __('ecommerce.home') }}</a>
                <span aria-hidden="true">/</span>
                <span>{{ $page['page_title'] }}</span>
            </nav>
            <p class="hb-legal-hero__eyebrow hb-legal-reveal" data-delay="30">{{ __('ecommerce.legal_terms_apply_all') }}</p>
            <div class="hb-legal-hero__badge hb-legal-reveal" data-delay="50">
                <span class="hb-legal-hero__icon" aria-hidden="true">
                    @include('shop.partials.policy-icon', ['icon' => $page['icon'] ?? 'document'])
                </span>
            </div>
            <h1 class="hb-legal-hero__title hb-legal-reveal" data-delay="90">{{ $page['page_title'] }}</h1>
            @if(filled($page['seo_description'] ?? null))
                <p class="hb-legal-hero__lead hb-legal-reveal" data-delay="130">{{ $page['seo_description'] }}</p>
            @endif
        </div>
    </section>

    @if(!empty($legalLinks))
        <nav class="hb-legal-tabs" aria-label="{{ __('ecommerce.policy_strip') }}">
            <div class="hb-legal-tabs__inner">
                @foreach($legalLinks as $link)
                    <a
                        href="{{ $link['url'] }}"
                        class="hb-legal-tabs__link {{ ($page['key'] ?? '') === $link['key'] ? 'is-active' : '' }}"
                    >
                        @include('shop.partials.policy-icon', ['icon' => $link['icon']])
                        <span>{{ $link['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    <div class="hb-legal-layout">
        @if(count($page['sections'] ?? []) > 1)
            <aside class="hb-legal-toc hb-legal-reveal" aria-label="{{ __('ecommerce.legal_toc') }}">
                <div class="hb-legal-toc__sticky">
                    <p class="hb-legal-toc__label">{{ __('ecommerce.legal_on_this_page') }}</p>
                    <ol class="hb-legal-toc__list">
                        @foreach($page['sections'] as $index => $section)
                            @if(filled($section['title'] ?? null))
                                @php $sectionId = $section['id'] ?? 'section-'.($index + 1); @endphp
                                <li>
                                    <a href="#{{ $sectionId }}" class="hb-legal-toc__link" data-toc-link>
                                        <span class="hb-legal-toc__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span>{{ $section['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            </aside>
        @endif

        <article class="hb-legal-article">
            <div class="hb-legal-article__inner">
                @foreach($page['sections'] ?? [] as $index => $section)
                    @php $sectionId = $section['id'] ?? 'section-'.($index + 1); @endphp
                    <section
                        id="{{ $sectionId }}"
                        class="hb-legal-block hb-legal-reveal"
                        data-delay="{{ min($index * 50, 400) }}"
                    >
                        <header class="hb-legal-block__head">
                            <span class="hb-legal-block__num" aria-hidden="true">{{ $index + 1 }}</span>
                            @if(filled($section['title'] ?? null))
                                <h2 class="hb-legal-block__title">{{ $section['title'] }}</h2>
                            @endif
                        </header>

                        <div class="hb-legal-block__body">
                            @foreach($section['paragraphs'] ?? [] as $paragraph)
                                <p class="hb-legal-block__text">{{ $paragraph }}</p>
                            @endforeach

                            @if(!empty($section['bullets']))
                                <ul class="hb-legal-block__list">
                                    @foreach($section['bullets'] as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </section>
                @endforeach

                <aside class="hb-legal-cta hb-legal-reveal">
                    <div class="hb-legal-cta__content">
                        <h3 class="hb-legal-cta__title">{{ __('ecommerce.legal_questions_title') }}</h3>
                        <p class="hb-legal-cta__text">{{ __('ecommerce.legal_questions_text') }}</p>
                    </div>
                    <a href="{{ route('shop.contact') }}" class="hb-legal-cta__btn">
                        {{ __('ecommerce.contact_us') }}
                    </a>
                </aside>
            </div>
        </article>
    </div>
@endsection
