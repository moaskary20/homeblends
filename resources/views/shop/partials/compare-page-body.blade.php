@php
    use App\Support\ProductMedia;
@endphp
<div class="hb-compare-page {{ $wrapperClass ?? '' }}">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    <header class="hb-compare-header">
        <div>
            <h1 class="hb-compare-title">{{ __('ecommerce.my_compare') }}</h1>
            <p class="hb-compare-subtitle">{{ __('ecommerce.compare_page_hint', ['max' => $maxItems]) }}</p>
        </div>
        @if($products->isNotEmpty())
            <form method="post" action="{{ $clearRoute }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="hb-compare-clear-btn">{{ __('ecommerce.compare_clear') }}</button>
            </form>
        @endif
    </header>

    @if($products->isEmpty())
        <div class="hb-compare-empty">
            <span class="hb-compare-empty-icon" aria-hidden="true">⚖️</span>
            <p>{{ __('ecommerce.compare_empty') }}</p>
            <p class="hb-compare-empty-hint">{{ __('ecommerce.compare_empty_hint') }}</p>
            <a href="{{ route('shop.products.index') }}" class="hb-account-btn-primary">{{ __('ecommerce.explore_now') }}</a>
        </div>
    @else
        <div class="hb-compare-matrix-wrap">
            <table class="hb-compare-matrix">
                <thead>
                    <tr>
                        <th class="hb-compare-feature-col">{{ __('ecommerce.compare_feature') }}</th>
                        @foreach($products as $product)
                            @php $thumb = ProductMedia::productThumbnail($product); @endphp
                            <th class="hb-compare-product-col">
                                <article class="hb-compare-product-card">
                                    <form method="post" action="{{ route($toggleRouteName, $product) }}" class="hb-compare-remove-form">
                                        @csrf
                                        <button type="submit" class="hb-compare-remove" title="{{ __('ecommerce.remove_from_compare') }}" aria-label="{{ __('ecommerce.remove_from_compare') }}">✕</button>
                                    </form>
                                    <a href="{{ route('shop.products.show', $product->slug) }}" class="hb-compare-product-link">
                                        <div class="hb-compare-product-thumb">
                                            @if($thumb)
                                                <img src="{{ $thumb }}" alt="{{ $product->name }}" loading="lazy" width="120" height="120">
                                            @else
                                                <span>📦</span>
                                            @endif
                                        </div>
                                        <h3 class="hb-compare-product-name">{{ $product->name }}</h3>
                                    </a>
                                    <button type="button"
                                            class="hb-compare-add-cart"
                                            data-product-add-cart
                                            data-api="{{ url('/api/v1') }}"
                                            data-session-id="{{ session()->getId() }}"
                                            data-product-id="{{ $product->id }}"
                                            data-added-label="{{ __('ecommerce.added_to_cart') }}">
                                        {{ __('ecommerce.add_to_cart') }}
                                    </button>
                                </article>
                            </th>
                        @endforeach
                        @for($i = $products->count(); $i < $maxItems; $i++)
                            <th class="hb-compare-product-col is-slot-empty">
                                <div class="hb-compare-slot-empty">
                                    <span>+</span>
                                    <p>{{ __('ecommerce.compare_add_slot') }}</p>
                                    <a href="{{ route('shop.products.index') }}">{{ __('ecommerce.explore_now') }}</a>
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <th class="hb-compare-feature-col">{{ $row['label'] }}</th>
                            @foreach($row['cells'] as $cell)
                                <td class="hb-compare-value-col {{ $cell['highlight'] ? 'is-diff' : '' }}">
                                    {!! $cell['html'] !!}
                                </td>
                            @endforeach
                            @for($i = count($row['cells']); $i < $maxItems; $i++)
                                <td class="hb-compare-value-col is-empty">—</td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
