@php
    use App\Support\ProductMedia;
    $cartPreviewItems = $cartPreviewItems ?? collect();
    $cartItemsCount = (int) ($cartItemsCount ?? 0);
    $cartSubtotal = (float) ($cartSubtotal ?? 0);
    $cartHasMore = (bool) ($cartHasMore ?? false);
@endphp
<div class="hb-mini-cart"
     data-mini-cart
     data-api="{{ url('/api/v1') }}"
     data-session-id="{{ session()->getId() }}"
     data-currency="{{ __('ecommerce.currency') }}"
     data-empty-text="{{ __('ecommerce.cart_empty') }}"
     data-more-template="{{ __('ecommerce.cart_more_items', ['count' => ':count']) }}"
     data-continue-url="{{ route('shop.products.index') }}"
     data-cart-url="{{ route('shop.cart') }}"
     data-checkout-url="{{ route('shop.checkout') }}"
     data-continue-label="{{ __('ecommerce.continue_shopping') }}"
     data-cart-label="{{ __('ecommerce.view_cart') }}"
     data-checkout-label="{{ __('ecommerce.proceed_checkout') }}"
     data-subtotal-label="{{ __('ecommerce.subtotal') }}"
     data-items-label="{{ __('ecommerce.items_count_label') }}"
     data-product-url-template="{{ route('shop.products.show', ['slug' => '__SLUG__']) }}"
     data-bundles-url="{{ route('shop.bundles.index') }}">
    <a href="{{ route('shop.cart') }}" class="hb-icon-btn hb-cart-icon-wrap" title="{{ __('ecommerce.cart') }}" aria-label="{{ __('ecommerce.cart') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        <span data-cart-count class="hb-cart-badge {{ $cartItemsCount < 1 ? 'hb-cart-hidden' : '' }}">{{ $cartItemsCount > 99 ? '99+' : $cartItemsCount }}</span>
    </a>

    <div class="hb-mini-cart-dropdown" data-mini-cart-panel role="region" aria-label="{{ __('ecommerce.cart') }}">
        <div class="hb-mini-cart-dropdown-header">
            <strong>{{ __('ecommerce.cart') }}</strong>
            <span data-mini-cart-count class="text-gray-500 text-xs {{ $cartItemsCount < 1 ? 'hb-cart-hidden' : '' }}">
                @if($cartItemsCount > 0)
                    {{ $cartItemsCount }} {{ __('ecommerce.items_count_label') }}
                @endif
            </span>
        </div>

        <div data-mini-cart-body>
            @if($cartPreviewItems->isEmpty())
                <div class="hb-mini-cart-empty">
                    <p>{{ __('ecommerce.cart_empty') }}</p>
                    <a href="{{ route('shop.products.index') }}" class="hb-mini-cart-link">{{ __('ecommerce.continue_shopping') }}</a>
                </div>
            @else
                <ul class="hb-mini-cart-items">
                    @foreach($cartPreviewItems as $item)
                        @php
                            $isBundle = $item->isBundleLine();
                            $product = $item->product;
                            $thumb = $product ? ProductMedia::productThumbnail($product) : null;
                            $title = $isBundle
                                ? ($item->bundle_snapshot['name'] ?? $item->bundle?->name ?? __('ecommerce.product_bundles'))
                                : ($product?->name ?? '');
                            $productUrl = $product && ! $isBundle ? route('shop.products.show', $product->slug) : route('shop.bundles.index');
                        @endphp
                        <li class="hb-mini-cart-item">
                            <a href="{{ $productUrl }}" class="hb-mini-cart-item-thumb">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="{{ $title }}" loading="lazy" width="48" height="48">
                                @else
                                    <span class="hb-mini-cart-item-placeholder">🛒</span>
                                @endif
                            </a>
                            <div class="hb-mini-cart-item-info">
                                <a href="{{ $productUrl }}" class="hb-mini-cart-item-title">{{ $title }}</a>
                                <span class="hb-mini-cart-item-meta">{{ $item->quantity }} × {{ number_format($item->unit_price, 2) }} {{ __('ecommerce.currency') }}</span>
                            </div>
                            <span class="hb-mini-cart-item-price">{{ number_format($item->subtotal, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if($cartHasMore)
                    <p class="hb-mini-cart-more text-xs text-gray-500 px-4 py-2 border-t border-gray-100">
                        {{ __('ecommerce.cart_more_items', ['count' => $cartItemsCount - $cartPreviewItems->count()]) }}
                    </p>
                @endif
                <div class="hb-mini-cart-footer">
                    <div class="hb-mini-cart-subtotal">
                        <span>{{ __('ecommerce.subtotal') }}</span>
                        <strong data-mini-cart-subtotal>{{ number_format($cartSubtotal, 2) }} {{ __('ecommerce.currency') }}</strong>
                    </div>
                    <a href="{{ route('shop.cart') }}" class="hb-mini-cart-btn hb-mini-cart-btn-secondary">{{ __('ecommerce.view_cart') }}</a>
                    <a href="{{ route('shop.checkout') }}" class="hb-mini-cart-btn hb-mini-cart-btn-primary">{{ __('ecommerce.proceed_checkout') }}</a>
                </div>
            @endif
        </div>
    </div>
</div>
