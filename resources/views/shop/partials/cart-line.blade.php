@php
    /** @var \App\Services\Cart\CartDisplayLine $line */
@endphp
<div class="hb-cart-line p-4 flex flex-wrap gap-4 border-b border-gray-100 last:border-0 {{ $line->isOfferSet ? 'is-offer-set' : '' }}"
     data-cart-line
     data-id="{{ $line->id }}"
     @if($line->qtyLocked) data-qty-locked="1" @endif>
    <a href="{{ $line->url }}" class="hb-cart-line-image shrink-0 {{ $line->isOfferSet ? 'is-offer' : '' }}">
        @if($line->imageUrl)
            <img src="{{ $line->imageUrl }}" alt="{{ $line->title }}" loading="lazy">
        @else
            <span class="hb-cart-line-placeholder">🛒</span>
        @endif
    </a>
    <div class="flex-1 min-w-[200px]">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                @if($line->isBundle)
                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">{{ __('ecommerce.bundle_badge') }}</span>
                @elseif($line->isOfferSet)
                    <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">{{ __('ecommerce.installment_badge') }}</span>
                @endif
                <a href="{{ $line->url }}" class="font-semibold text-[#3d3830] hover:text-amber-700 block mt-1">
                    {{ $line->title }}
                </a>
                @if($line->isOfferSet && $line->meta)
                    <p class="text-sm text-amber-800 mt-1">{{ $line->meta }}</p>
                @elseif($line->isBundle && $line->bundleItems !== [])
                    <ul class="text-sm text-gray-500 mt-2 list-disc list-inside">
                        @foreach($line->bundleItems as $row)
                            <li>{{ $row['product_name'] ?? '' }} × {{ $row['quantity'] ?? 1 }}</li>
                        @endforeach
                    </ul>
                @elseif($line->variantSku)
                    <p class="text-sm text-gray-500 mt-1">{{ $line->variantSku }}</p>
                @endif
            </div>
            <p class="font-bold text-amber-800 whitespace-nowrap" data-line-subtotal>
                {{ number_format($line->subtotal, 2) }} {{ __('ecommerce.currency') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 mt-3">
            @if($line->qtyLocked)
                <span class="text-sm text-gray-500">{{ __('ecommerce.offer_cart_qty_fixed') }}</span>
            @else
                <label class="text-sm text-gray-500">{{ __('ecommerce.quantity') }}</label>
                <input type="number" min="0" max="99" value="{{ $line->quantity }}"
                       class="qty-input border border-gray-200 rounded-lg w-20 px-2 py-1 text-center"
                       data-id="{{ $line->id }}" aria-label="{{ __('ecommerce.quantity') }}">
            @endif
            <button type="button" class="remove-btn text-red-600 text-sm hover:underline" data-id="{{ $line->id }}">
                {{ __('ecommerce.remove_from_cart') }}
            </button>
        </div>
    </div>
</div>
