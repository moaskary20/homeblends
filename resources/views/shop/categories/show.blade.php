@extends('layouts.shop')

@section('content')
    <div class="hb-browse">
        <nav class="text-sm text-gray-600 mb-4">
            <a href="{{ route('shop.home') }}" class="hover:text-amber-700">{{ __('ecommerce.home') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route('shop.categories.index') }}" class="hover:text-amber-700">{{ __('ecommerce.departments') }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-medium">{{ $category->name }}</span>
        </nav>

        <div class="hb-browse-layout">
            <aside class="hb-filters">
                <form method="get" action="{{ route('shop.categories.show', $category->slug) }}" class="space-y-6">
                    <div>
                        <h2 class="hb-filter-title">{{ __('ecommerce.filter_search') }}</h2>
                        <input type="search" name="q" value="{{ $filters['q'] }}"
                               placeholder="{{ __('ecommerce.search_in_category') }}"
                               class="hb-filter-input w-full">
                    </div>

                    <div>
                        <h2 class="hb-filter-title">{{ __('ecommerce.filter_price') }}</h2>
                        <p class="text-xs text-gray-500 mb-2">
                            {{ __('ecommerce.price_range_hint', ['min' => number_format($priceRange['min']), 'max' => number_format($priceRange['max'])]) }}
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" min="0" step="1"
                                   value="{{ $filters['min_price'] }}"
                                   placeholder="{{ __('ecommerce.min_price') }}"
                                   class="hb-filter-input">
                            <input type="number" name="max_price" min="0" step="1"
                                   value="{{ $filters['max_price'] }}"
                                   placeholder="{{ __('ecommerce.max_price') }}"
                                   class="hb-filter-input">
                        </div>
                    </div>

                    @if($facets->isNotEmpty())
                        <div>
                            <h2 class="hb-filter-title">{{ __('ecommerce.filter_variants') }}</h2>
                            @foreach($facets as $attribute)
                                <div class="mb-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">{{ $attribute->name }}</p>
                                    <div class="space-y-1 max-h-40 overflow-y-auto">
                                        @foreach($attribute->values as $value)
                                            @php
                                                $selected = in_array($value->id, $filters['attributes'][$attribute->id] ?? [], true);
                                            @endphp
                                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                                <input type="checkbox"
                                                       name="attr[{{ $attribute->id }}][]"
                                                       value="{{ $value->id }}"
                                                       @checked($selected)
                                                       class="rounded border-gray-300 text-amber-600">
                                                @if($value->color_hex)
                                                    <span class="w-4 h-4 rounded-full border border-gray-300 shrink-0" style="background: {{ $value->color_hex }}"></span>
                                                @endif
                                                <span>{{ $value->value }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])
                                   class="rounded border-gray-300 text-amber-600">
                            <span>{{ __('ecommerce.in_stock_only') }}</span>
                        </label>
                    </div>

                    <div>
                        <h2 class="hb-filter-title">{{ __('ecommerce.sort_by') }}</h2>
                        <select name="sort" class="hb-filter-input w-full">
                            <option value="newest" @selected($filters['sort'] === 'newest')>{{ __('ecommerce.sort_newest') }}</option>
                            <option value="price_asc" @selected($filters['sort'] === 'price_asc')>{{ __('ecommerce.sort_price_asc') }}</option>
                            <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('ecommerce.sort_price_desc') }}</option>
                            <option value="name_asc" @selected($filters['sort'] === 'name_asc')>{{ __('ecommerce.sort_name_asc') }}</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button type="submit" class="hb-btn-primary">{{ __('ecommerce.apply_filters') }}</button>
                        <a href="{{ route('shop.categories.show', $category->slug) }}" class="hb-btn-secondary text-center">
                            {{ __('ecommerce.clear_filters') }}
                        </a>
                    </div>
                </form>

                @if($category->children->isNotEmpty())
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h2 class="hb-filter-title">{{ __('ecommerce.subcategories') }}</h2>
                        <ul class="space-y-2 text-sm">
                            @foreach($category->children as $child)
                                <li>
                                    <a href="{{ route('shop.categories.show', $child->slug) }}"
                                       class="text-amber-800 hover:underline">{{ $child->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>

            <div class="hb-browse-main">
                <header class="flex flex-wrap items-end justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#3d3830]">{{ $category->name }}</h1>
                        @if($category->description)
                            <p class="text-gray-600 mt-1">{{ $category->description }}</p>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ __('ecommerce.results_count', ['count' => $items->total()]) }}
                    </p>
                </header>

                @if($items->isEmpty())
                    <div class="bg-white rounded-xl p-12 text-center text-gray-600">
                        {{ __('ecommerce.no_products_in_category') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
                        @foreach($items as $product)
                            @include('shop.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $items->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-categories.css') }}">
@endpush
