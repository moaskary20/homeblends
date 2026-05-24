@php
    use App\Services\Shop\HomepageService;
    $cards = $popularCollectionCards ?? collect();
    $sectionTitle = HomepageService::popularCollections()['section_title'] ?? __('ecommerce.popular_collections');
    $placeholder = asset('images/collections/placeholder-thumb.svg');
@endphp

@if($cards->isNotEmpty())
    <section class="hb-popular-collections is-visible is-revealed pb-12" id="popular-collections" aria-labelledby="popular-collections-title">
        <div class="hb-pc-inner">
            <div class="hb-pc-header">
                <h2 id="popular-collections-title" class="hb-pc-title">{{ $sectionTitle }}</h2>
                <div class="hb-pc-nav" aria-hidden="false">
                    <button type="button" class="hb-pc-nav-btn hb-pc-prev" aria-label="{{ __('ecommerce.carousel_prev') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <button type="button" class="hb-pc-nav-btn hb-pc-next" aria-label="{{ __('ecommerce.carousel_next') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                </div>
            </div>

            <div class="hb-pc-viewport">
                <div class="hb-pc-track">
                    @foreach($cards as $card)
                        <article class="hb-pc-card">
                            <a href="{{ $card['hero']['url'] }}" class="hb-pc-hero">
                                <img
                                    src="{{ $card['hero']['image'] ?? $placeholder }}"
                                    alt="{{ $card['hero']['name'] }}"
                                    loading="lazy"
                                    decoding="async"
                                    data-fallback="{{ $placeholder }}"
                                    onerror="if (this.dataset.fallback && this.src !== this.dataset.fallback) { this.src = this.dataset.fallback; }"
                                >
                            </a>
                            <div class="hb-pc-thumbs">
                                @foreach($card['thumbs'] as $thumb)
                                    <a href="{{ $thumb['url'] }}" class="hb-pc-thumb">
                                        <img
                                            src="{{ $thumb['image'] ?? $placeholder }}"
                                            alt="{{ $thumb['name'] }}"
                                            loading="lazy"
                                            decoding="async"
                                            data-fallback="{{ $placeholder }}"
                                            onerror="if (this.dataset.fallback && this.src !== this.dataset.fallback) { this.src = this.dataset.fallback; }"
                                        >
                                    </a>
                                @endforeach
                            </div>
                            <div class="hb-pc-footer">
                                <div class="hb-pc-meta">
                                    <h3 class="hb-pc-card-title">{{ $card['title'] }}</h3>
                                    <p class="hb-pc-count">{{ __('ecommerce.collection_items_count', ['count' => $card['items_count']]) }}</p>
                                </div>
                                <a href="{{ $card['shop_url'] }}" class="hb-pc-shop-btn">{{ __('ecommerce.shop_collection') }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
