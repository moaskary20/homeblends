@extends('layouts.shop')

@section('content')
    <h1 class="text-3xl font-bold mb-2">{{ __('ecommerce.product_bundles') }}</h1>
    <p class="text-gray-600 mb-8">{{ __('ecommerce.bundle_savings') }} — {{ __('ecommerce.you_save') }} عند شراء مجموعة منتجات معاً</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($bundles as $bundle)
            @include('shop.partials.bundle-card', ['bundle' => $bundle])
        @empty
            <p class="col-span-full text-gray-500">{{ __('No products yet.') }}</p>
        @endforelse
    </div>
@endsection
