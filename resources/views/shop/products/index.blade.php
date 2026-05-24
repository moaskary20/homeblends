@extends('layouts.shop')

@section('content')
    <h1 class="text-3xl font-bold mb-8">{{ __('All Products') }}</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($items as $product)
            <article class="bg-white rounded-xl shadow-sm overflow-hidden">
                <a href="{{ route('shop.products.show', $product->slug) }}" class="block p-4">
                    <h2 class="font-semibold">{{ $product->name }}</h2>
                    <p class="text-amber-600 font-bold mt-2">{{ number_format($product->effective_price, 2) }} {{ __('EGP') }}</p>
                </a>
            </article>
        @endforeach
    </div>
    <div class="mt-8">{{ $items->links() }}</div>
@endsection
