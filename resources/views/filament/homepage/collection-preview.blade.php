@php
    $title = $title ?? '';
    $itemsCount = $itemsCount ?? 0;
    $heroUrl = $heroUrl ?? null;
    $thumbUrls = $thumbUrls ?? [];
@endphp

<div class="hb-collection-preview">
    <p class="hb-collection-preview-label">{{ __('ecommerce.preview') }}</p>
    @if ($heroUrl)
        <div class="hb-collection-preview-hero">
            <img src="{{ $heroUrl }}" alt="{{ $title }}" loading="lazy">
        </div>
    @else
        <div class="hb-collection-preview-hero hb-collection-preview-hero--empty">
            <x-heroicon-o-photo class="w-8 h-8 opacity-40" />
        </div>
    @endif
    @if (count($thumbUrls) > 0)
        <div class="hb-collection-preview-thumbs">
            @foreach ($thumbUrls as $url)
                <img src="{{ $url }}" alt="" loading="lazy">
            @endforeach
        </div>
    @endif
    <div class="hb-collection-preview-footer">
        <div>
            <strong>{{ $title ?: '—' }}</strong>
            @if ($itemsCount)
                <span>{{ __('ecommerce.collection_items_count', ['count' => $itemsCount]) }}</span>
            @endif
        </div>
        <span class="hb-collection-preview-btn">{{ __('ecommerce.shop_collection') }}</span>
    </div>
</div>
