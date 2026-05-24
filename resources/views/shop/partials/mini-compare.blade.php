@php
    use App\Support\ProductMedia;
    $comparePreviewItems = $comparePreviewItems ?? collect();
    $compareCount = (int) ($compareCount ?? 0);
    $maxCompare = (int) config('ecommerce.compare.max_items', 4);
@endphp
<div class="hb-mini-compare" data-mini-compare>
    <a href="{{ auth()->check() ? route('shop.account.compare') : route('login') }}"
       class="hb-icon-btn hb-compare-icon-wrap"
       title="{{ __('ecommerce.my_compare') }}"
       aria-label="{{ __('ecommerce.my_compare') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span data-compare-count class="hb-compare-badge {{ $compareCount < 1 ? 'hb-cart-hidden' : '' }}">
            {{ $compareCount > 99 ? '99+' : $compareCount }}
        </span>
    </a>

    <div class="hb-mini-compare-dropdown" role="region" aria-label="{{ __('ecommerce.my_compare') }}">
        <div class="hb-mini-compare-dropdown-header">
            <strong>{{ __('ecommerce.my_compare') }}</strong>
            @auth
                <span class="text-gray-500 text-xs">{{ $compareCount }}/{{ $maxCompare }}</span>
            @endauth
        </div>
        <div class="hb-mini-compare-body">
            @guest
                <div class="hb-mini-compare-empty">
                    <p>{{ __('ecommerce.login_for_compare') }}</p>
                    <a href="{{ route('login') }}" class="hb-mini-compare-link">{{ __('ecommerce.login') }}</a>
                </div>
            @elseif($comparePreviewItems->isEmpty())
                <div class="hb-mini-compare-empty">
                    <p>{{ __('ecommerce.compare_empty') }}</p>
                    <a href="{{ route('shop.products.index') }}" class="hb-mini-compare-link">{{ __('ecommerce.explore_now') }}</a>
                </div>
            @else
                <ul class="hb-mini-compare-items">
                    @foreach($comparePreviewItems as $product)
                        @php $thumb = ProductMedia::productThumbnail($product); @endphp
                        <li>
                            <a href="{{ route('shop.products.show', $product->slug) }}" class="hb-mini-compare-item">
                                <span class="hb-mini-compare-thumb">
                                    @if($thumb)
                                        <img src="{{ $thumb }}" alt="" width="40" height="40" loading="lazy">
                                    @else
                                        <span>📦</span>
                                    @endif
                                </span>
                                <span class="hb-mini-compare-name">{{ $product->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('shop.account.compare') }}" class="hb-mini-compare-btn">{{ __('ecommerce.view_compare') }}</a>
            @endif
        </div>
    </div>
</div>
