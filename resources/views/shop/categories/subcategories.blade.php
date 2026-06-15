@extends('layouts.shop')

@section('content')
    <div class="hb-departments">
        <nav class="text-sm text-gray-600 mb-4">
            <a href="{{ route('shop.home') }}" class="hover:text-amber-700">{{ __('ecommerce.home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route('shop.categories.index') }}" class="hover:text-amber-700">{{ __('ecommerce.departments') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">{{ $category->name }}</span>
        </nav>

        <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-[#3d3830]">{{ $category->name }}</h1>
                <p class="text-gray-600 mt-2">{{ __('ecommerce.choose_subcategory') }}</p>
                @if($category->description)
                    <p class="text-gray-500 mt-1">{{ $category->description }}</p>
                @endif
            </div>
            <a href="{{ route('shop.categories.show', ['slug' => $category->slug, 'all' => 1]) }}"
               class="text-sm font-semibold text-amber-800 hover:underline">
                {{ __('ecommerce.browse_all_in_department', ['name' => $category->name]) }} ←
            </a>
        </header>

        @if($category->children->isEmpty())
            <p class="text-gray-600">{{ __('ecommerce.no_subcategories') }}</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($category->children as $child)
                    <a href="{{ route('shop.categories.show', $child->slug) }}" class="hb-dept-card group">
                        <div class="hb-dept-circle group-hover:scale-105 transition-transform">
                            @include('shop.partials.category-circle-image', [
                                'category' => $child->imageUrl() ? $child : $category,
                                'alt' => $child->name,
                                'width' => 400,
                            ])
                        </div>
                        <h2 class="font-semibold text-center mt-3">{{ $child->name }}</h2>
                        <p class="text-xs text-amber-700 text-center mt-1">
                            {{ __('ecommerce.products_count', ['count' => $child->products_count]) }}
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
