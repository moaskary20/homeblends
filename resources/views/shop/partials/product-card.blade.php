@php
    use App\Support\ProductMedia;
    $entry = $entry ?? null;
    $product = $entry?->product ?? $product;
    $wishlistProductIds = $wishlistProductIds ?? [];
    $compareProductIds = $compareProductIds ?? [];
    $inWishlist = in_array($product->id, $wishlistProductIds, true);
    $inCompare = in_array($product->id, $compareProductIds, true);
    $activeEntry = $entry ?? (
        ($product->relationLoaded('activeFlashSaleEntry') && $product->activeFlashSaleEntry?->hasStock())
            ? $product->activeFlashSaleEntry
            : null
    );
    $flashPrice = $activeEntry?->sale_price;
    $endsAt = $activeEntry?->flashSale?->ends_at;
    $comparePrice = $activeEntry
        ? ($activeEntry->variant?->price ?? $product->regular_price)
        : $product->regular_price;
    $salePrice = $flashPrice ?? $product->effective_price;
    $isFlash = $flashPrice !== null;
    $hasTimedDiscount = ! $isFlash && $product->hasActiveTimedDiscount();
    if ($hasTimedDiscount) {
        $comparePrice = (float) $product->regular_price;
    }
    $discountEndsAt = $hasTimedDiscount ? $product->discount_ends_at : null;
    $thumb = ProductMedia::productThumbnail($product);
@endphp
<article class="hb-product-card bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition relative group"
         data-product-card
         data-product-id="{{ $product->id }}">
    @if($isFlash)
        <span class="absolute top-2 inset-inline-start-2 z-10 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">
            {{ __('ecommerce.flash_sale_badge') }}
        </span>
    @endif

    <button type="button"
            class="hb-product-card-compare {{ $inCompare ? 'is-active' : '' }}"
            data-product-compare
            data-product-id="{{ $product->id }}"
            data-compare-url="{{ auth()->check() ? route('shop.account.compare.toggle', $product) : '' }}"
            data-login-url="{{ route('login') }}"
            aria-label="{{ $inCompare ? __('ecommerce.remove_from_compare') : __('ecommerce.add_to_compare') }}"
            title="{{ $inCompare ? __('ecommerce.remove_from_compare') : __('ecommerce.add_to_compare') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
    </button>

    <button type="button"
            class="hb-product-card-wishlist {{ $inWishlist ? 'is-active' : '' }}"
            data-product-wishlist
            data-product-id="{{ $product->id }}"
            data-favorite-url="{{ auth()->check() ? route('shop.account.favorites.toggle', $product) : '' }}"
            data-login-url="{{ route('login') }}"
            aria-label="{{ $inWishlist ? __('ecommerce.remove_from_favorites') : __('ecommerce.add_to_favorites') }}"
            title="{{ $inWishlist ? __('ecommerce.remove_from_favorites') : __('ecommerce.add_to_favorites') }}">
        <svg class="w-5 h-5" fill="{{ $inWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>

    <a href="{{ route('shop.products.show', $product->slug) }}" class="hb-product-card-link block">
        <div class="aspect-square bg-gray-100 flex items-center justify-center text-gray-400 overflow-hidden">
            @if($thumb)
                <img src="{{ $thumb }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
            @else
                {{ __('No image') }}
            @endif
        </div>
        <div class="p-4 pb-2">
            <h3 class="font-semibold line-clamp-2 text-[#3d3830]">{{ $product->name }}</h3>
            <div class="mt-2 flex items-baseline gap-2 flex-wrap">
                <p class="text-amber-600 font-bold">{{ number_format($salePrice, 2) }} {{ __('EGP') }}</p>
                @if(($isFlash || $hasTimedDiscount) && $comparePrice > $salePrice)
                    <p class="text-sm text-gray-400 line-through">{{ number_format($comparePrice, 2) }}</p>
                @endif
            </div>
            @if($discountEndsAt)
                <p class="flash-countdown text-xs text-amber-700 mt-2 font-medium"
                   data-ends="{{ $discountEndsAt->toIso8601String() }}"
                   data-label="{{ __('ecommerce.discount_ends_in') }} "></p>
            @elseif($endsAt)
                <p class="flash-countdown text-xs text-red-600 mt-2 font-medium"
                   data-ends="{{ $endsAt->toIso8601String() }}"></p>
            @endif
        </div>
    </a>

    <div class="px-4 pb-4">
        <button type="button"
                class="hb-product-card-cart w-full"
                data-product-add-cart
                data-api="{{ url('/api/v1') }}"
                data-session-id="{{ session()->getId() }}"
                data-product-id="{{ $product->id }}"
                data-added-label="{{ __('ecommerce.added_to_cart') }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            {{ __('ecommerce.add_to_cart') }}
        </button>
    </div>
</article>
