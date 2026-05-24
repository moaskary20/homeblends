@php
    use App\Support\ProductMedia;
    $wishlistPreviewItems = $wishlistPreviewItems ?? collect();
    $wishlistCount = (int) ($wishlistCount ?? 0);
    $wishlistHasMore = (bool) ($wishlistHasMore ?? false);
    $favoritesPageUrl = auth()->check() ? route('shop.account.favorites') : route('shop.products.index');
@endphp
<div class="hb-mini-wishlist"
     data-mini-wishlist
     data-preview-url="{{ route('shop.wishlist.preview') }}"
     data-favorites-url="{{ $favoritesPageUrl }}"
     data-remove-label="{{ __('ecommerce.remove_from_favorites') }}"
     data-empty-text="{{ __('ecommerce.favorites_empty') }}"
     data-login-text="{{ __('ecommerce.login_for_favorites') }}"
     data-more-template="{{ __('ecommerce.favorites_more_items', ['count' => ':count']) }}"
     data-view-label="{{ __('ecommerce.view_favorites') }}"
     data-continue-url="{{ route('shop.products.index') }}"
     data-continue-label="{{ __('ecommerce.continue_shopping') }}"
     data-currency="{{ __('ecommerce.currency') }}">
    <a href="{{ $favoritesPageUrl }}"
       class="hb-icon-btn hb-wishlist-icon-wrap"
       title="{{ __('ecommerce.my_favorites') }}"
       aria-label="{{ __('ecommerce.my_favorites') }}">
        <svg class="w-5 h-5" fill="{{ $wishlistCount > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
        <span data-wishlist-count class="hb-wishlist-badge {{ $wishlistCount < 1 ? 'hb-cart-hidden' : '' }}">
            {{ $wishlistCount > 99 ? '99+' : $wishlistCount }}
        </span>
    </a>

    <div class="hb-mini-wishlist-dropdown" data-mini-wishlist-panel role="region" aria-label="{{ __('ecommerce.my_favorites') }}">
        <div class="hb-mini-wishlist-dropdown-header">
            <strong>{{ __('ecommerce.my_favorites') }}</strong>
            <span data-mini-wishlist-count class="text-gray-500 text-xs {{ $wishlistCount < 1 ? 'hb-cart-hidden' : '' }}">
                @if($wishlistCount > 0)
                    {{ $wishlistCount }} {{ __('ecommerce.items_count_label') }}
                @endif
            </span>
        </div>

        <div data-mini-wishlist-body>
            @if($wishlistPreviewItems->isEmpty())
                <div class="hb-mini-wishlist-empty">
                    <p>{{ __('ecommerce.favorites_empty') }}</p>
                    <a href="{{ route('shop.products.index') }}" class="hb-mini-wishlist-link">{{ __('ecommerce.continue_shopping') }}</a>
                </div>
            @else
                <ul class="hb-mini-wishlist-items">
                    @foreach($wishlistPreviewItems as $product)
                        @php
                            $thumb = ProductMedia::productThumbnail($product);
                            $productUrl = route('shop.products.show', $product->slug);
                        @endphp
                        <li class="hb-mini-wishlist-item" data-product-id="{{ $product->id }}">
                            <a href="{{ $productUrl }}" class="hb-mini-wishlist-item-thumb">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="{{ $product->name }}" loading="lazy" width="48" height="48">
                                @else
                                    <span class="hb-mini-wishlist-item-placeholder">❤️</span>
                                @endif
                            </a>
                            <div class="hb-mini-wishlist-item-info">
                                <a href="{{ $productUrl }}" class="hb-mini-wishlist-item-title">{{ $product->name }}</a>
                                <span class="hb-mini-wishlist-item-meta">{{ number_format($product->effective_price, 2) }} {{ __('ecommerce.currency') }}</span>
                            </div>
                            <button type="button"
                                    class="hb-mini-wishlist-remove"
                                    data-wishlist-remove
                                    data-remove-url="{{ route('shop.wishlist.remove', $product) }}"
                                    title="{{ __('ecommerce.remove_from_favorites') }}"
                                    aria-label="{{ __('ecommerce.remove_from_favorites') }}">✕</button>
                        </li>
                    @endforeach
                </ul>
                @if($wishlistHasMore)
                    <p class="hb-mini-wishlist-more text-xs text-gray-500 px-4 py-2 border-t border-gray-100" data-mini-wishlist-more>
                        {{ __('ecommerce.favorites_more_items', ['count' => $wishlistCount - $wishlistPreviewItems->count()]) }}
                    </p>
                @endif
                <div class="hb-mini-wishlist-footer">
                    <a href="{{ $favoritesPageUrl }}" class="hb-mini-wishlist-btn">{{ __('ecommerce.view_favorites') }}</a>
                </div>
            @endif
        </div>
    </div>
</div>
