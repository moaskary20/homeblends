@extends('layouts.shop')

@section('body_class', 'home-page')
@section('main_class', 'flex-1 w-full p-0 max-w-none')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-home.css') }}?v={{ filemtime(public_path('css/shop-home.css')) }}">
    <script src="{{ asset('js/shop-home.js') }}?v={{ filemtime(public_path('js/shop-home.js')) }}" defer></script>
@endpush

@section('content')
    {{-- شريط آخر الأخبار --}}
    <div class="hb-news-ticker" aria-label="{{ __('ecommerce.latest_news') }}">
        <div class="hb-ticker-track">
            @foreach(array_merge($homepage['news_ticker'], $homepage['news_ticker']) as $item)
                <span class="hb-ticker-item">{{ $item }}</span>
            @endforeach
        </div>
    </div>

    {{-- سليدر رئيسي --}}
    <section class="hb-hero" id="hero-slider">
        @foreach($homepage['hero_slides'] as $index => $slide)
            <div class="hb-hero-slide {{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}">
                <img src="{{ \App\Services\Shop\HomepageService::slideImageUrl($slide['image'] ?? '', 1400) }}" alt="{{ $slide['title'] ?? '' }}" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" @if($index === 0) fetchpriority="high" @endif width="1400" height="788">
                <div class="hb-hero-overlay">
                    <div class="hb-hero-content">
                        <h2 class="text-3xl md:text-5xl font-bold mb-3">{{ $slide['title'] ?? '' }}</h2>
                        @if(!empty($slide['subtitle']))
                            <p class="text-lg md:text-xl opacity-90 mb-6">{{ $slide['subtitle'] }}</p>
                        @endif
                        @if(!empty($slide['url']))
                            <a href="{{ url($slide['url']) }}"
                               class="inline-block bg-white text-amber-800 px-8 py-3 rounded-full font-bold hover:bg-amber-50 transition">
                                {{ $slide['cta'] ?? __('Shop Now') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <div class="hb-hero-dots">
            @foreach($homepage['hero_slides'] as $index => $slide)
                <button type="button" class="hb-hero-dot {{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
    </section>

    {{-- شركاء النجاح --}}
    @include('shop.partials.partners-strip', ['partners' => $homepage['partners'] ?? []])

    {{-- الأقسام دائرية --}}
    <section class="hb-categories">
        <div class="hb-categories__head">
            <h2 class="hb-categories-title">{{ __('ecommerce.shop_by_category') }}</h2>
            <a href="{{ route('shop.categories.index') }}" class="hb-categories__all">
                {{ __('ecommerce.view_all_departments') }} ←
            </a>
        </div>
        <div class="hb-categories-scroll">
            @foreach($homeCategories as $category)
                <a href="{{ route('shop.categories.show', $category->slug) }}" class="hb-category-card group">
                    <div class="hb-category-circle group-hover:scale-105 transition-transform">
                        @if($category->imageUrl())
                            <img src="{{ $category->imageUrl(400) }}" alt="{{ $category->name }}" loading="lazy" decoding="async">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-amber-100 text-4xl">🏠</div>
                        @endif
                    </div>
                    <div class="hb-category-name-ticker">
                        <div class="hb-category-name-track" style="animation-delay: {{ $loop->index * 0.4 }}s">
                            <span>{{ $category->name }}</span>
                            <span>{{ __('ecommerce.explore_now') }} ←</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    @if($flashProducts->isNotEmpty())
        <section class="hb-home-section max-w-[1400px] mx-auto">
            <h2 class="hb-section-title flex items-center gap-2">
                <span>⚡</span> {{ __('ecommerce.flash_sales') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($flashProducts as $entry)
                    @include('shop.partials.product-card', ['entry' => $entry])
                @endforeach
            </div>
        </section>
    @endif

    @if($bundles->isNotEmpty())
        <section class="hb-home-section max-w-[1400px] mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="hb-section-title mb-0">{{ __('ecommerce.bundles_section') }}</h2>
                <a href="{{ route('shop.bundles.index') }}" class="text-amber-800 font-semibold hover:underline">{{ __('ecommerce.view_all_bundles') }} →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($bundles as $bundle)
                    @include('shop.partials.bundle-card', ['bundle' => $bundle])
                @endforeach
            </div>
        </section>
    @endif

    @include('shop.partials.featured-products', ['featured' => $featured])

    @include('shop.partials.popular-collections', ['popularCollectionCards' => $popularCollectionCards])

    @include('shop.partials.design-banner', ['designBanner' => $designBanner])

    @include('shop.partials.catalog-showcase', [
        'catalogShowcase' => $catalogShowcase ?? null,
        'sectionId' => 'catalog-showcase',
    ])

    @include('shop.partials.promo-banner', ['promoBanner' => $promoBanner ?? null])

    @include('shop.partials.catalog-showcase', [
        'catalogShowcase' => $catalogShowcaseFurniture ?? null,
        'sectionId' => 'catalog-showcase-furniture',
    ])

    @include('shop.partials.comfort-spotlight', ['comfortSpotlight' => $comfortSpotlight ?? null])

    @include('shop.partials.customer-reviews', [
        'customerReviewCards' => $customerReviewCards ?? collect(),
        'customerReviewsTitle' => $customerReviewsTitle ?? __('ecommerce.customer_reviews'),
    ])

    @include('shop.partials.contact-strip', ['contactStrip' => $contactStrip ?? null])

    @include('shop.partials.policy-links-strip')
@endsection
