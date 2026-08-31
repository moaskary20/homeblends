@php
    use App\Support\ProductMedia;
    $banner = ProductMedia::url($offer->banner_image);
    $offerTotal = $offer->offerTotal();
    $plans = $offer->planMonths();
    $monthly = count($plans) > 1
        ? $offer->lowestMonthlyAmount($offerTotal)
        : $offer->monthlyAmountFor($offerTotal);
@endphp
<a href="{{ route('shop.offers.show', $offer->slug) }}"
   class="block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
    <div class="aspect-[16/10] bg-amber-50 flex items-center justify-center overflow-hidden">
        @if($banner)
            <img src="{{ $banner }}" alt="{{ $offer->name }}" class="w-full h-full object-cover">
        @else
            <span class="text-amber-800 font-bold">{{ __('ecommerce.installment_badge') }}</span>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-semibold text-[#3d3830] line-clamp-2">{{ $offer->name }}</h3>
        <p class="text-sm text-amber-800 mt-2">
            {{ $offer->plansLabel() }}
            · {{ trans_choice('ecommerce.offer_products_count', $offer->products->count(), ['count' => $offer->products->count()]) }}
        </p>
        <p class="text-lg font-bold text-amber-700 mt-2">
            {{ number_format($offerTotal, 2) }} {{ __('EGP') }}
        </p>
        <p class="text-sm font-semibold text-amber-900 mt-1">
            @if(count($plans) > 1)
                {{ __('ecommerce.installment_from_monthly', ['amount' => number_format($monthly, 2)]) }}
            @else
                {{ __('ecommerce.installment_monthly_each', ['amount' => number_format($monthly, 2)]) }}
            @endif
        </p>
    </div>
</a>
