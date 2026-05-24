@php
    $logoUrl = $logoUrl ?? null;
    $name = $name ?? '';
@endphp

<div class="hb-partner-preview">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $name }}" loading="lazy">
    @else
        <span class="hb-partner-preview-placeholder">
            <x-heroicon-o-building-storefront class="w-8 h-8" />
        </span>
    @endif
    @if ($name)
        <p class="hb-partner-preview-name">{{ $name }}</p>
    @endif
</div>
