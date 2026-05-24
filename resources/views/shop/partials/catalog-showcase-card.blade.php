@php
    $item = $item ?? [];
    $thumb = $item['thumb'] ?? null;
@endphp

<article class="hb-catalog-card">
    <a href="{{ $item['url'] }}" class="hb-catalog-card__media">
        @if(!empty($item['is_out_of_stock']))
            <span class="hb-catalog-badge hb-catalog-badge--stock">{{ __('ecommerce.sold_out') }}</span>
        @endif
        @if(!empty($item['discount_percent']))
            <span class="hb-catalog-badge hb-catalog-badge--sale">-{{ $item['discount_percent'] }}%</span>
        @endif
        @if($thumb)
            <img src="{{ $thumb }}" alt="{{ $item['name'] ?? '' }}" loading="lazy" decoding="async">
        @endif
    </a>
    <div class="hb-catalog-card__body">
        <a href="{{ $item['url'] }}" class="hb-catalog-card__name">{{ $item['name'] ?? '' }}</a>
        <div class="hb-catalog-card__prices">
            <span class="hb-catalog-card__price">{{ number_format($item['sale_price'] ?? 0, 0) }} {{ __('EGP') }}</span>
            @if(!empty($item['compare_price']))
                <span class="hb-catalog-card__compare">{{ number_format($item['compare_price'], 0) }} {{ __('EGP') }}</span>
            @endif
        </div>
        @if(!empty($item['swatches']) && count($item['swatches']) > 0)
            <div class="hb-catalog-card__swatches" role="list" aria-label="{{ __('ecommerce.product_variants') }}">
                @foreach($item['swatches'] as $index => $swatch)
                    <a
                        href="{{ $item['url'] }}"
                        class="hb-catalog-swatch {{ $index === 0 ? 'is-active' : '' }}"
                        role="listitem"
                    >
                        <img src="{{ $swatch['url'] }}" alt="" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</article>
