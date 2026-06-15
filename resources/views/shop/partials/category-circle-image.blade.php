@php
    /** @var \App\Models\Category $category */
    $source = $category->imageUrl($width ?? 400);
    $vectorClass = $category->usesVectorImage() ? ' hb-dept-image--vector' : '';
@endphp

@if($source)
    <img src="{{ $source }}" alt="{{ $alt ?? $category->name }}" class="{{ trim($vectorClass) }}" loading="lazy" decoding="async">
@else
    <span class="text-4xl">🛋️</span>
@endif
