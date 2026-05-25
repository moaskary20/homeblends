@extends('shop.account._layout', ['current' => 'compare'])

@section('account_content')
    @include('shop.partials.compare-page-body', [
        'clearRoute' => route('shop.account.compare.clear'),
        'toggleRouteName' => 'shop.account.compare.toggle',
    ])
@endsection

