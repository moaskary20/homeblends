@php
    $showcase = $catalogShowcase ?? null;
    $sectionId = $sectionId ?? 'catalog-showcase';
@endphp

@if(!empty($showcase) && count($showcase['tabs'] ?? []) > 0)
    <section class="hb-catalog-showcase" id="{{ $sectionId }}" aria-labelledby="{{ $sectionId }}-title">
        <div class="hb-catalog-inner">
            <div class="hb-catalog-header">
                <h2 id="{{ $sectionId }}-title" class="hb-catalog-title">{{ $showcase['title'] }}</h2>
                <div class="hb-catalog-tabs" role="tablist" aria-label="{{ $showcase['title'] }}">
                    @foreach($showcase['tabs'] as $index => $tab)
                        <button
                            type="button"
                            role="tab"
                            class="hb-catalog-tab {{ $index === 0 ? 'is-active' : '' }}"
                            id="{{ $sectionId }}-tab-{{ $tab['id'] }}"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="{{ $sectionId }}-panel-{{ $tab['id'] }}"
                            data-catalog-tab="{{ $tab['id'] }}"
                        >
                            {{ $tab['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach($showcase['tabs'] as $index => $tab)
                <div
                    id="{{ $sectionId }}-panel-{{ $tab['id'] }}"
                    role="tabpanel"
                    class="hb-catalog-panel {{ $index === 0 ? 'is-active' : '' }}"
                    aria-labelledby="{{ $sectionId }}-tab-{{ $tab['id'] }}"
                    data-catalog-panel="{{ $tab['id'] }}"
                    @if($index !== 0) hidden @endif
                >
                    <div class="hb-catalog-track-wrap">
                        <div class="hb-catalog-track">
                            @foreach($tab['products'] as $item)
                                @include('shop.partials.catalog-showcase-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                    <div class="hb-catalog-panel-footer">
                        <a href="{{ $tab['url'] }}" class="hb-catalog-view-all">{{ __('ecommerce.view_all_in_category') }} ←</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
