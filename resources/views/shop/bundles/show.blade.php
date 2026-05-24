@extends('layouts.shop')

@section('content')
    <article class="grid md:grid-cols-2 gap-8">
        <div class="bg-white rounded-xl p-6 aspect-square flex items-center justify-center overflow-hidden">
            @if($bundleImage = \App\Support\ProductMedia::url($bundle->main_image))
                <img src="{{ $bundleImage }}" alt="{{ $bundle->name }}" class="max-h-full object-contain">
            @else
                <span class="text-6xl">📦</span>
            @endif
        </div>
        <div>
            <span class="inline-block bg-amber-600 text-white text-sm font-bold px-3 py-1 rounded mb-3">
                {{ __('ecommerce.bundle_badge') }}
            </span>
            <h1 class="text-3xl font-bold mb-4">{{ $bundle->name }}</h1>
            <div class="mb-4">
                <p class="text-3xl text-amber-600 font-bold">{{ number_format($bundle->bundle_price, 2) }} {{ __('EGP') }}</p>
                @if($savings > 0)
                    <p class="text-lg text-gray-400 line-through">{{ number_format($regularTotal, 2) }} {{ __('EGP') }}</p>
                    <p class="text-green-600 font-medium mt-1">
                        {{ __('ecommerce.you_save') }} {{ number_format($savings, 2) }} {{ __('EGP') }} ({{ $savingsPercent }}%)
                    </p>
                @endif
                @if($bundle->ends_at)
                    <p class="flash-countdown text-amber-700 font-medium mt-2"
                       data-ends="{{ $bundle->ends_at->toIso8601String() }}"
                       data-label="{{ __('ecommerce.discount_ends_in') }} "></p>
                @endif
            </div>
            @if($bundle->short_description)
                <p class="text-gray-600 mb-6">{{ $bundle->short_description }}</p>
            @endif
            @if($bundle->description)
                <div class="prose max-w-none mb-8">{!! $bundle->description !!}</div>
            @endif

            <button type="button" id="add-bundle-to-cart"
                    data-bundle-id="{{ $bundle->id }}"
                    data-api="{{ url('/api/v1') }}"
                    data-session-id="{{ session()->getId() }}"
                    class="bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold mb-6">
                {{ __('ecommerce.bundle_add_to_cart') }}
            </button>
        </div>
    </article>

    <section class="mt-12">
        <h2 class="text-2xl font-bold mb-6">{{ __('ecommerce.bundle_includes') }}</h2>
        <ul class="bg-white rounded-xl divide-y">
            @foreach($bundle->items as $item)
                <li class="p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        @if($item->product?->main_image)
                            <img src="{{ asset('storage/'.$item->product->main_image) }}" alt="" class="w-16 h-16 object-cover rounded-lg">
                        @endif
                        <div>
                            <a href="{{ route('shop.products.show', $item->product->slug) }}" class="font-semibold hover:text-amber-600">
                                {{ $item->product->name }}
                            </a>
                            @if($item->variant)
                                <p class="text-sm text-gray-500">{{ $item->variant->sku }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="text-left">
                        <span class="text-gray-500">× {{ $item->quantity }}</span>
                        <p class="font-medium">{{ number_format($item->lineRegularTotal(), 2) }} {{ __('EGP') }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endsection

@push('scripts')
<script>
document.getElementById('add-bundle-to-cart')?.addEventListener('click', async () => {
    const btn = document.getElementById('add-bundle-to-cart');
    const token = typeof window.shopAuthToken === 'function' ? window.shopAuthToken() : null;
    const res = await fetch(`${btn.dataset.api}/cart/bundles`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({ product_bundle_id: parseInt(btn.dataset.bundleId, 10), quantity: 1 }),
    });
    const data = await res.json();
    if (res.ok) {
        alert(@json(__('ecommerce.bundle_added')));
        window.location.href = @json(route('shop.cart'));
    } else {
        alert(data.message || data.errors?.bundle?.[0] || 'تعذّر الإضافة');
    }
});
</script>
@endpush
