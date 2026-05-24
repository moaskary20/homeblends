@extends('layouts.shop')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}">
@endpush

@section('content')
    <div class="hb-account-page max-w-[1200px] mx-auto">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="hb-account-layout">
            @include('shop.account._nav', ['current' => $current ?? ''])
            <div class="hb-account-content">
                @yield('account_content')
            </div>
        </div>
    </div>
@endsection
