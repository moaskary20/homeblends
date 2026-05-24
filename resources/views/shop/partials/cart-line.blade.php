@php
    use App\Support\ProductMedia;
    $isBundle = $item->isBundleLine();
    $product = $item->product;
    $thumb = $product ? ProductMedia::productThumbnail($product) : null;
    $title = $isBundle
        ? ($item->bundle_snapshot['name'] ?? $item->bundle?->name ?? __('ecommerce.product_bundles'))
        : ($product?->name ?? '');
    $productUrl = $product && ! $isBundle ? route('shop.products.show', $product->slug) : null;
@endphp
<div class="hb-cart-line p-4 flex flex-wrap gap-4 border-b border-gray-100 last:border-0" data-cart-line data-id="{{ $item->id }}">
    <a href="{{ $productUrl ?? route('shop.bundles.index') }}" class="hb-cart-line-image shrink-0">
        @if($thumb)
            <img src="{{ $thumb }}" alt="{{ $title }}" loading="lazy">
        @else
            <span class="hb-cart-line-placeholder">🛒</span>
        @endif
    </a>
    <div class="flex-1 min-w-[200px]">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                @if($isBundle)
                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">{{ __('ecommerce.bundle_badge') }}</span>
                @endif
                <a href="{{ $productUrl ?? route('shop.bundles.index') }}" class="font-semibold text-[#3d3830] hover:text-amber-700 block mt-1">
                    {{ $title }}
                </a>
                @if($isBundle && ! empty($item->bundle_snapshot['items']))
                    <ul class="text-sm text-gray-500 mt-2 list-disc list-inside">
                        @foreach($item->bundle_snapshot['items'] as $row)
                            <li>{{ $row['product_name'] ?? '' }} × {{ $row['quantity'] ?? 1 }}</li>
                        @endforeach
                    </ul>
                @elseif($item->variant)
                    <p class="text-sm text-gray-500 mt-1">{{ $item->variant->sku }}</p>
                @endif
            </div>
            <p class="font-bold text-amber-800 whitespace-nowrap" data-line-subtotal>
                {{ number_format($item->subtotal, 2) }} {{ __('ecommerce.currency') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 mt-3">
            <label class="text-sm text-gray-500">{{ __('ecommerce.quantity') }}</label>
            <input type="number" min="0" max="99" value="{{ $item->quantity }}"
                   class="qty-input border border-gray-200 rounded-lg w-20 px-2 py-1 text-center"
                   data-id="{{ $item->id }}" aria-label="{{ __('ecommerce.quantity') }}">
            <button type="button" class="remove-btn text-red-600 text-sm hover:underline" data-id="{{ $item->id }}">
                {{ __('ecommerce.remove_from_cart') }}
            </button>
        </div>
    </div>
</div>
