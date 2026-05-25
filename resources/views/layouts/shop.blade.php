<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(session()->isStarted())
        <meta name="shop-session-id" content="{{ session()->getId() }}">
    @endif
    <title>{{ ($seo ?? null)?->title ?? trim($__env->yieldContent('title', config('app.name'))) }}</title>
    @include('shop.partials.seo-meta')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    <link rel="stylesheet" href="{{ asset('css/shop-header.css') }}?v={{ filemtime(public_path('css/shop-header.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-cart.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop-product-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop-mobile-nav.css') }}">
    @stack('head')
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="@yield('body_class', 'bg-[#f5f0e6]') text-gray-900 min-h-screen flex flex-col hb-shop-has-mobile-nav"
      data-user-authenticated="{{ auth()->check() ? '1' : '0' }}">
    @php
        $navCategories = $navCategories ?? \Illuminate\Support\Facades\Cache::remember(
            'shop.nav.categories',
            7200,
            fn () => app(\App\Services\Shop\CategoryBrowseService::class)->categoriesForNav()
        );
        $homepageHeader = $homepage ?? null;
    @endphp

    @include('shop.partials.header-tiered', [
        'homepage' => $homepageHeader ?? app(\App\Services\Shop\HomepageService::class)->getContent(),
        'navCategories' => $navCategories,
    ])

    <main class="@yield('main_class', 'flex-1 max-w-7xl mx-auto w-full px-4 py-8')">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    @include('shop.partials.mobile-bottom-nav')

    <footer class="bg-[#3d3830] text-gray-200 mt-auto">
        <div class="max-w-[1400px] mx-auto px-4 py-8 text-center text-sm space-y-1.5">
            <p>{{ __('ecommerce.footer_rights') }}</p>
            <p>{{ __('ecommerce.footer_credit') }}</p>
        </div>
    </footer>

    @include('shop.partials.flash-countdown-script')
    @stack('scripts')
    <script>
        window.shopAuthToken = () => (
            document.body?.dataset.userAuthenticated === '1'
                ? localStorage.getItem('api_token')
                : null
        );
    </script>
    <script>
        document.querySelectorAll('[data-user-menu-toggle]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = btn.closest('[data-user-menu]');
                document.querySelectorAll('[data-user-menu].is-open').forEach((m) => {
                    if (m !== menu) m.classList.remove('is-open');
                });
                menu?.classList.toggle('is-open');
            });
        });
        document.addEventListener('click', () => {
            document.querySelectorAll('[data-user-menu].is-open').forEach((m) => m.classList.remove('is-open'));
        });

        window.addEventListener('cart:updated', (e) => {
            const count = e.detail?.count ?? 0;
            document.querySelectorAll('[data-cart-count]').forEach((el) => {
                el.textContent = count > 99 ? '99+' : String(count);
                el.classList.toggle('hidden', count < 1);
                el.classList.toggle('hb-cart-hidden', count < 1);
            });
            if (typeof window.scheduleMiniCartRefresh === 'function') {
                window.scheduleMiniCartRefresh(count);
            } else if (typeof window.refreshMiniCart === 'function') {
                window.refreshMiniCart(count);
            }
        });
    </script>
    <script src="{{ asset('js/shop-guest-session.js') }}" defer></script>
    <script src="{{ asset('js/shop-header-mobile.js') }}" defer></script>
    <script src="{{ asset('js/shop-mini-cart.js') }}" defer></script>
    <script src="{{ asset('js/shop-mini-wishlist.js') }}" defer></script>
    <script src="{{ asset('js/shop-product-card.js') }}?v={{ filemtime(public_path('js/shop-product-card.js')) }}" defer></script>
</body>
</html>
