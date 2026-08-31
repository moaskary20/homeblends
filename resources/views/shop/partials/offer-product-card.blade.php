@php
    use App\Support\ProductMedia;
    $product = $entry->product;
    $thumb = $product ? ProductMedia::productThumbnail($product, 800) : null;
    $comparePrice = $entry->comparePrice();
    $offerPrice = (float) $entry->offer_price;
    $showCompare = $comparePrice > $offerPrice;
    $inStock = $product && $entry->hasStock();
    $productUrl = $product ? route('shop.products.show', $product->slug) : route('shop.offers.index');
@endphp
<article class="hb-offer-product{{ $inStock ? '' : ' is-sold-out' }}">
    <div class="hb-offer-product__visual">
        <a href="{{ $productUrl }}" class="hb-offer-product__media">
            @if($thumb)
                <img src="{{ $thumb }}" alt="{{ $product?->name }}" loading="lazy" decoding="async">
            @else
                <span class="hb-offer-product__placeholder">{{ __('No image') }}</span>
            @endif
        </a>
        @if(! $inStock)
            <span class="hb-offer-product__sold">{{ __('ecommerce.offer_sold_out') }}</span>
        @endif
    </div>

    <div class="hb-offer-product__body">
        @if($product?->category)
            <p class="hb-offer-product__category">{{ $product->category->name }}</p>
        @endif
        <a href="{{ $productUrl }}" class="hb-offer-product__name">{{ $product?->name }}</a>
        <div class="hb-offer-product__prices">
            <span class="hb-offer-product__price">{{ number_format($offerPrice, 2) }} {{ __('EGP') }}</span>
            @if($showCompare)
                <span class="hb-offer-product__compare">{{ number_format($comparePrice, 2) }} {{ __('EGP') }}</span>
            @endif
        </div>
        <a href="{{ $productUrl }}" class="hb-offer-product__details">{{ __('ecommerce.offer_view_product') }}</a>
    </div>
</article>
