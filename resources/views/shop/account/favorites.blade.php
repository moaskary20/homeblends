@extends('shop.account._layout', ['current' => 'favorites'])

@section('account_content')
    <h1 class="text-2xl font-bold text-[#3d3830] mb-6">{{ __('ecommerce.my_favorites') }}</h1>

    @if($products->isEmpty())
        <p class="text-gray-500 bg-white rounded-xl p-8 text-center">{{ __('ecommerce.favorites_empty') }}</p>
        <a href="{{ route('shop.products.index') }}" class="hb-account-btn-primary inline-block mt-4">{{ __('ecommerce.continue_shopping') }}</a>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($products as $product)
                <div class="relative">
                    @include('shop.partials.product-card', ['product' => $product])
                    <form method="post" action="{{ route('shop.account.favorites.remove', $product) }}" class="absolute top-2 left-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-white/90 text-red-600 text-xs px-2 py-1 rounded shadow hover:bg-white" title="{{ __('ecommerce.remove_from_favorites') }}">
                            ✕
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
