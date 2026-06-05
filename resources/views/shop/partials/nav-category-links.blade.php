@foreach($navCategories as $category)
    @if($category->children->isNotEmpty())
        <div class="hb-nav-dropdown">
            <a href="{{ route('shop.categories.show', $category->slug) }}"
               class="hb-nav-dropdown__trigger {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $category->slug ? 'is-active' : '' }}">
                <span>{{ $category->name }}</span>
                <svg class="hb-nav-dropdown__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                </svg>
            </a>
            <div class="hb-nav-dropdown__menu" role="menu">
                @foreach($category->children as $child)
                    @if($child->children->isNotEmpty())
                        <a href="{{ route('shop.categories.show', $child->slug) }}"
                           class="hb-nav-dropdown__item {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $child->slug ? 'is-active' : '' }}"
                           role="menuitem">
                            {{ $child->name }}
                        </a>
                        @foreach($child->children as $grandchild)
                            <a href="{{ route('shop.categories.show', $grandchild->slug) }}"
                               class="hb-nav-dropdown__item {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $grandchild->slug ? 'is-active' : '' }}"
                               role="menuitem">
                                — {{ $grandchild->name }}
                            </a>
                        @endforeach
                    @else
                        <a href="{{ route('shop.categories.show', $child->slug) }}"
                           class="hb-nav-dropdown__item {{ request()->routeIs('shop.categories.show') && request()->route('slug') === $child->slug ? 'is-active' : '' }}"
                           role="menuitem">
                            {{ $child->name }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ route('shop.categories.show', ['slug' => $category->slug, 'all' => 1]) }}"
                   class="hb-nav-dropdown__item hb-nav-dropdown__item--all"
                   role="menuitem">
                    {{ __('ecommerce.browse_all_in_department', ['name' => $category->name]) }}
                </a>
            </div>
        </div>
    @else
        <a href="{{ route('shop.categories.show', $category->slug) }}"
           class="{{ request()->routeIs('shop.categories.show') && request()->route('slug') === $category->slug ? 'is-active' : '' }}">
            {{ $category->name }}
        </a>
    @endif
@endforeach
