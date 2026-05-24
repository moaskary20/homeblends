<div class="hb-user-menu" data-user-menu>
    <button type="button"
            class="hb-user-menu-btn @auth hb-user-menu-trigger @else hb-icon-btn @endauth"
            aria-expanded="false" aria-haspopup="true"
            data-user-menu-toggle title="{{ __('ecommerce.my_account') }}">
        @auth
            <img src="{{ auth()->user()->avatar_url }}" alt="" class="hb-user-menu-avatar" width="28" height="28">
            <span class="hb-user-menu-name">{{ auth()->user()->name }}</span>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        @endauth
    </button>

    <div class="hb-user-dropdown" role="menu">
        @auth
            <div class="hb-user-dropdown-header hb-user-dropdown-header--with-avatar">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="hb-user-dropdown-avatar" width="40" height="40">
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <span class="text-gray-500">{{ auth()->user()->email }}</span>
                </div>
            </div>
            <a href="{{ route('shop.account.profile') }}" role="menuitem">👤 {{ __('ecommerce.my_account') }}</a>
            <a href="{{ route('shop.account.purchases') }}" role="menuitem">🛍️ {{ __('ecommerce.my_purchases') }}</a>
            <a href="{{ route('shop.account.points') }}" role="menuitem">⭐ {{ __('ecommerce.my_points') }}</a>
            <a href="{{ route('shop.account.tracking') }}" role="menuitem">📦 {{ __('ecommerce.track_orders') }}</a>
            <a href="{{ route('shop.account.favorites') }}" role="menuitem">
                ❤️ {{ __('ecommerce.my_favorites') }}
                @if(($wishlistCount ?? 0) > 0)
                    <span class="mr-auto text-xs bg-amber-100 text-amber-800 px-1.5 rounded-full">{{ $wishlistCount }}</span>
                @endif
            </a>
            <a href="{{ route('shop.account.compare') }}" role="menuitem">
                ⚖️ {{ __('ecommerce.my_compare') }}
                @if(($compareCount ?? 0) > 0)
                    <span class="mr-auto text-xs bg-amber-100 text-amber-800 px-1.5 rounded-full">{{ $compareCount }}</span>
                @endif
            </a>
            <div class="hb-user-dropdown-divider"></div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem">🚪 {{ __('ecommerce.logout') }}</button>
            </form>
        @else
            <div class="hb-user-dropdown-header">
                <span class="text-gray-600">{{ __('ecommerce.login_welcome') }}</span>
            </div>
            <a href="{{ route('login') }}" role="menuitem">👤 {{ __('ecommerce.login') }}</a>
            @if(Route::has('register'))
                <a href="{{ route('register') }}" role="menuitem">📝 {{ __('ecommerce.register') }}</a>
            @endif
        @endauth
    </div>
</div>
