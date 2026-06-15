@extends('layouts.shop')

@section('content')
    <div class="hb-departments">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#3d3830]">{{ __('ecommerce.departments') }}</h1>
            <p class="text-gray-600 mt-2">{{ __('ecommerce.departments_intro') }}</p>
        </header>

        @if($categories->isEmpty())
            <p class="text-gray-600">{{ __('ecommerce.no_categories') }}</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($categories as $category)
                    <a href="{{ route('shop.categories.show', $category->slug) }}" class="hb-dept-card group">
                        <div class="hb-dept-circle group-hover:scale-105 transition-transform">
                            @include('shop.partials.category-circle-image', ['category' => $category, 'width' => 400])
                        </div>
                        <h2 class="font-semibold text-center mt-3">{{ $category->name }}</h2>
                        @if($category->parent)
                            <p class="text-xs text-gray-500 text-center">{{ $category->parent->name }}</p>
                        @endif
                        <p class="text-xs text-amber-700 text-center mt-1">
                            {{ __('ecommerce.products_count', ['count' => $category->products_count]) }}
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-categories.css') }}">
@endpush
