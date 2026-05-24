@php
    $accountUrl = auth()->check()
        ? route('shop.account.profile')
        : route('login');
    $isHome = request()->routeIs('shop.home');
    $isCart = request()->routeIs('shop.cart');
    $isCategories = request()->routeIs('shop.categories.*');
    $isAccount = request()->routeIs('shop.account.*')
        || (auth()->guest() && request()->routeIs('login'));
    $cartCount = (int) ($cartItemsCount ?? 0);
@endphp

<nav class="hb-mobile-nav" aria-label="{{ __('ecommerce.mobile_navigation') }}">
    <a href="{{ route('shop.home') }}"
       class="hb-mobile-nav-item {{ $isHome ? 'is-active' : '' }}"
       @if($isHome) aria-current="page" @endif>
        <span class="hb-mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        </span>
        <span class="hb-mobile-nav-label">{{ __('ecommerce.home') }}</span>
    </a>

    <a href="{{ route('shop.cart') }}"
       class="hb-mobile-nav-item {{ $isCart ? 'is-active' : '' }}"
       @if($isCart) aria-current="page" @endif>
        <span class="hb-mobile-nav-icon hb-mobile-nav-icon--cart" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <span data-cart-count class="hb-mobile-nav-badge {{ $cartCount < 1 ? 'hb-cart-hidden' : '' }}">
                {{ $cartCount > 99 ? '99+' : $cartCount }}
            </span>
        </span>
        <span class="hb-mobile-nav-label">{{ __('ecommerce.mobile_nav_cart') }}</span>
    </a>

    <a href="{{ route('shop.categories.index') }}"
       class="hb-mobile-nav-item {{ $isCategories ? 'is-active' : '' }}"
       @if($isCategories) aria-current="page" @endif>
        <span class="hb-mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </span>
        <span class="hb-mobile-nav-label">{{ __('ecommerce.departments') }}</span>
    </a>

    <a href="{{ $accountUrl }}"
       class="hb-mobile-nav-item {{ $isAccount ? 'is-active' : '' }}"
       @if($isAccount) aria-current="page" @endif>
        <span class="hb-mobile-nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </span>
        <span class="hb-mobile-nav-label">{{ __('ecommerce.my_account') }}</span>
    </a>
</nav>
