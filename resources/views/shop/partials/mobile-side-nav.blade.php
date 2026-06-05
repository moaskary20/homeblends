@php
    $navCategories = $navCategories ?? collect();
@endphp
<div class="hb-side-nav-backdrop" data-mobile-menu-close hidden></div>

<aside id="hb-side-nav"
       class="hb-side-nav"
       role="dialog"
       aria-modal="true"
       aria-label="{{ __('ecommerce.main_menu') }}"
       hidden>
    <div class="hb-side-nav-header">
        <span class="hb-side-nav-title">{{ __('ecommerce.main_menu') }}</span>
        <button type="button" class="hb-side-nav-close" data-mobile-menu-close aria-label="{{ __('ecommerce.close_menu') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="hb-side-nav-links">
        <a href="{{ route('shop.home') }}" class="hb-side-nav-link {{ request()->routeIs('shop.home') ? 'is-active' : '' }}">
            {{ __('ecommerce.home') }}
        </a>
        <a href="{{ route('shop.about') }}" class="hb-side-nav-link {{ request()->routeIs('shop.about') ? 'is-active' : '' }}">
            {{ __('ecommerce.about_company') }}
        </a>
        <a href="{{ route('shop.contact') }}" class="hb-side-nav-link {{ request()->routeIs('shop.contact') ? 'is-active' : '' }}">
            {{ __('ecommerce.contact_us') }}
        </a>
        <a href="{{ route('shop.products.index') }}" class="hb-side-nav-link {{ request()->routeIs('shop.products.*') ? 'is-active' : '' }}">
            {{ __('ecommerce.new_featured') }}
        </a>
        <a href="{{ route('shop.categories.index') }}" class="hb-side-nav-link {{ request()->routeIs('shop.categories.*') ? 'is-active' : '' }}">
            {{ __('ecommerce.departments') }}
        </a>
        <a href="{{ route('shop.products.index') }}" class="hb-side-nav-link">
            {{ __('Products') }}
        </a>
        @foreach($navCategories as $category)
            @if($category->children->isNotEmpty())
                <details class="hb-side-nav-group">
                    <summary class="hb-side-nav-link hb-side-nav-link--parent">
                        <span>{{ $category->name }}</span>
                        <svg class="hb-side-nav-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                        </svg>
                    </summary>
                    <div class="hb-side-nav-sub">
                        <a href="{{ route('shop.categories.show', $category->slug) }}" class="hb-side-nav-sublink">
                            {{ __('ecommerce.view_subcategories', ['name' => $category->name]) }}
                        </a>
                        @foreach($category->children as $child)
                            @if($child->children->isNotEmpty())
                                <a href="{{ route('shop.categories.show', $child->slug) }}"
                                   class="hb-side-nav-sublink {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $child->slug ? 'is-active' : '' }}">
                                    {{ $child->name }}
                                </a>
                                @foreach($child->children as $grandchild)
                                    <a href="{{ route('shop.categories.show', $grandchild->slug) }}"
                                       class="hb-side-nav-sublink {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $grandchild->slug ? 'is-active' : '' }}">
                                        — {{ $grandchild->name }}
                                    </a>
                                @endforeach
                            @else
                                <a href="{{ route('shop.categories.show', $child->slug) }}"
                                   class="hb-side-nav-sublink {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $child->slug ? 'is-active' : '' }}">
                                    {{ $child->name }}
                                </a>
                            @endif
                        @endforeach
                        <a href="{{ route('shop.categories.show', ['slug' => $category->slug, 'all' => 1]) }}" class="hb-side-nav-sublink hb-side-nav-sublink--all">
                            {{ __('ecommerce.browse_all_in_department', ['name' => $category->name]) }}
                        </a>
                    </div>
                </details>
            @else
                <a href="{{ route('shop.categories.show', $category->slug) }}"
                   class="hb-side-nav-link {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $category->slug ? 'is-active' : '' }}">
                    {{ $category->name }}
                </a>
            @endif
        @endforeach
        <a href="{{ route('shop.design-team') }}" class="hb-side-nav-link {{ request()->routeIs('shop.design-team') ? 'is-active' : '' }}">
            {{ __('ecommerce.design_team') }}
        </a>
    </nav>

    <div class="hb-side-nav-footer">
        @auth
            <a href="{{ route('shop.account.profile') }}" class="hb-side-nav-account">
                {{ __('ecommerce.my_account') }}
            </a>
        @else
            <a href="{{ route('login') }}" class="hb-side-nav-account">
                {{ __('Log in') }}
            </a>
        @endauth
    </div>
</aside>
