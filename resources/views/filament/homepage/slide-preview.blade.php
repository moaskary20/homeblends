@php
    $imageUrl = $imageUrl ?? null;
@endphp

@if ($imageUrl)
    <div class="hb-slide-preview">
        <img src="{{ $imageUrl }}" alt="" loading="lazy">
        <span class="hb-slide-preview-badge">{{ __('ecommerce.preview') }}</span>
    </div>
@else
    <div class="hb-slide-preview hb-slide-preview--empty">
        <x-heroicon-o-photo class="w-10 h-10 opacity-40" />
        <span>{{ __('ecommerce.hero_slide_image_hint') }}</span>
    </div>
@endif
