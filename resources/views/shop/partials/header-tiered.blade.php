@php
    $homepage = $homepage ?? app(\App\Services\Shop\HomepageService::class)->getContent();
    $navCategories = $navCategories ?? collect();
@endphp
<header class="hb-header">
    {{-- الجزء العلوي --}}
    <div class="hb-header-top">
        <div class="hb-header-inner py-2 hb-header-top-row">
            <div class="hb-header-top-social flex items-center gap-3 shrink-0">
                @foreach($homepage['social'] ?? [] as $social)
                    <a href="{{ $social['url'] ?? '#' }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] ?? '' }}" class="opacity-90 hover:opacity-100">
                        @if(($social['icon'] ?? '') === 'facebook')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        @elseif(($social['icon'] ?? '') === 'instagram')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.919-.02.08 1.58.06 3.16-.06 4.74-.94 5.06-5.04 8.98-9.86 9.04-4.82.06-9.14-3.98-9.2-9.78-.06-5.8 4.36-9.86 9.14-9.98z"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
            <p class="hb-header-announcement text-xs md:text-sm font-medium">
                {{ $homepage['announcement'] ?? '' }}
            </p>
            <div class="hb-header-top-links flex items-center gap-2 shrink-0 text-xs">
                @foreach($homepage['top_links'] ?? [] as $link)
                    @if(!$loop->first)<span class="opacity-50">|</span>@endif
                    <a href="{{ url($link['url'] ?? '#') }}">{{ $link['label'] ?? '' }}</a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- الشعار في المنتصف | بحث مضغوط | حساب وسلة --}}
    <div class="hb-header-main">
        <div class="hb-header-inner py-3">
            <div class="hb-header-main-row">
                <div class="hb-header-actions hb-header-desktop-actions">
                    @include('shop.partials.mini-wishlist')
                    <div class="hb-header-icon-group">
                        @include('shop.partials.mini-compare')
                        @include('shop.partials.mini-cart')
                    </div>
                    @include('shop.partials.user-menu')
                </div>

                <div class="hb-header-center">
                    <a href="{{ route('shop.home') }}" class="hb-logo" title="{{ config('app.name', 'هوم بلند') }}">
                        <img src="{{ asset('images/logo/logohome.png') }}"
                             alt="{{ config('app.name', 'هوم بلند') }}"
                             class="hb-logo-img"
                             width="220"
                             height="68"
                             loading="eager">
                    </a>
                    <nav class="hb-subnav" aria-label="{{ __('ecommerce.site_pages') }}">
                        <a href="{{ route('shop.home') }}" class="{{ request()->routeIs('shop.home') ? 'is-active' : '' }}">{{ __('ecommerce.home') }}</a>
                        <a href="{{ route('shop.about') }}" class="{{ request()->routeIs('shop.about') ? 'is-active' : '' }}">{{ __('ecommerce.about_company') }}</a>
                        <a href="{{ route('shop.contact') }}" class="{{ request()->routeIs('shop.contact') ? 'is-active' : '' }}">{{ __('ecommerce.contact_us') }}</a>
                    </nav>
                </div>

                <form action="{{ route('shop.products.index') }}" method="get" class="hb-search hb-header-search">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" placeholder="{{ __('ecommerce.search_placeholder') }}" value="{{ request('q') }}" aria-label="{{ __('ecommerce.search') }}">
                </form>

                <div class="hb-header-mobile-tools-end">
                    @include('shop.partials.user-menu')
                    @include('shop.partials.mini-wishlist')
                </div>

                <div class="hb-header-mobile-tools">
                    <button type="button"
                            class="hb-icon-btn hb-mobile-menu-btn"
                            data-mobile-menu-open
                            aria-controls="hb-side-nav"
                            aria-expanded="false"
                            aria-label="{{ __('ecommerce.open_menu') }}">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </button>
                    @include('shop.partials.mini-cart')
                </div>
            </div>
        </div>
    </div>

    {{-- القائمة الرئيسية في المنتصف --}}
    <nav class="hb-nav">
        <div class="hb-header-inner hb-header-nav-inner">
            <a href="{{ route('shop.products.index') }}">{{ __('ecommerce.new_featured') }}</a>
            <a href="{{ route('shop.categories.index') }}">{{ __('ecommerce.departments') }}</a>
            <a href="{{ route('shop.products.index') }}">{{ __('Products') }}</a>
            @include('shop.partials.nav-category-links', ['navCategories' => $navCategories])
            <a href="{{ route('shop.design-team') }}" class="{{ request()->routeIs('shop.design-team') ? 'is-active' : '' }}">{{ __('ecommerce.design_team') }}</a>
        </div>
    </nav>

    @include('shop.partials.mobile-side-nav', ['navCategories' => $navCategories])
</header>
