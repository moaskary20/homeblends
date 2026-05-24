@php
    $bundleService = app(\App\Services\Bundle\BundleService::class);
    $regular = $bundleService->calculateRegularTotal($bundle);
    $savings = $bundleService->calculateSavings($bundle);
    $percent = $bundleService->savingsPercent($bundle);
@endphp
<a href="{{ route('shop.bundles.show', $bundle->slug) }}"
   class="block bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden border border-amber-100">
    <div class="aspect-[4/3] bg-amber-50 flex items-center justify-center overflow-hidden">
        @if($bundleImage = \App\Support\ProductMedia::url($bundle->main_image))
            <img src="{{ $bundleImage }}" alt="{{ $bundle->name }}" class="w-full h-full object-cover">
        @else
            <span class="text-4xl">📦</span>
        @endif
    </div>
    <div class="p-4">
        <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded">{{ __('ecommerce.bundle_badge') }}</span>
        <h3 class="font-bold mt-2 line-clamp-2">{{ $bundle->name }}</h3>
        @if($bundle->short_description)
            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $bundle->short_description }}</p>
        @endif
        <p class="text-xl font-bold text-amber-600 mt-3">{{ number_format($bundle->bundle_price, 2) }} {{ __('EGP') }}</p>
        @if($savings > 0)
            <p class="text-sm text-gray-400 line-through">{{ number_format($regular, 2) }} {{ __('EGP') }}</p>
            <p class="text-sm text-green-600 font-medium">
                {{ __('ecommerce.you_save') }} {{ number_format($savings, 2) }} {{ __('EGP') }} ({{ $percent }}%)
            </p>
        @endif
        <p class="text-xs text-gray-500 mt-2">{{ $bundle->items->count() }} {{ __('ecommerce.products') }}</p>
    </div>
</a>
