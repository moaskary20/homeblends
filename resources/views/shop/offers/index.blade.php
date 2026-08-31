@extends('layouts.shop')

@section('content')
    <h1 class="text-3xl font-bold mb-2">{{ __('ecommerce.installment_offers') }}</h1>
    <p class="text-gray-600 mb-8">{{ __('ecommerce.offers_intro') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($offers as $offer)
            @include('shop.partials.offer-card', ['offer' => $offer])
        @empty
            <p class="col-span-full text-gray-500">{{ __('ecommerce.no_offers') }}</p>
        @endforelse
    </div>
@endsection
