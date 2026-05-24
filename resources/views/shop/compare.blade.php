@extends('layouts.shop')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}">
@endpush

@section('content')
    <div class="max-w-[1200px] mx-auto px-4 py-8">
        @include('shop.partials.compare-page-body', [
            'clearRoute' => route('shop.compare.clear'),
            'toggleRouteName' => 'shop.compare.toggle',
        ])
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/shop-product-card.js') }}" defer></script>
@endpush
