@php
    use App\Support\ProductMedia;
    $gallery = ProductMedia::productGallery($product);
    $galleryTotal = ProductMedia::productGalleryTotal($product);
    $galleryHidden = max(0, $galleryTotal - $gallery->count());
@endphp

@if($gallery->isNotEmpty())
    <div class="hb-product-gallery" data-gallery>
        <div class="hb-gallery-main">
            <img src="{{ $gallery->first()['url'] }}" alt="{{ $gallery->first()['alt'] }}"
                 data-gallery-main loading="eager">
        </div>
        @if($gallery->count() > 1)
            <div class="hb-gallery-thumbs-wrap">
                <div class="hb-gallery-thumbs" role="list">
                    @foreach($gallery as $index => $item)
                        <button type="button"
                                class="hb-gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                data-gallery-thumb
                                data-url="{{ $item['url'] }}"
                                role="listitem"
                                aria-label="{{ $item['alt'] }}">
                            <img src="{{ $item['url'] }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
                @if($galleryHidden > 0)
                    <p class="hb-gallery-more-hint">
                        {{ __('ecommerce.gallery_more_images', ['count' => $galleryHidden]) }}
                    </p>
                @endif
            </div>
        @endif
    </div>
@else
    <div class="hb-gallery-empty">
        {{ __('ecommerce.no_image') }}
    </div>
@endif
