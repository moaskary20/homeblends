@extends('layouts.shop')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}">
@endpush

@section('content')
    <a href="{{ route('shop.orders.index') }}" class="text-amber-600 text-sm mb-4 inline-block">← {{ __('ecommerce.my_orders') }}</a>

    <h1 class="text-2xl font-bold mb-2">{{ $order->order_number }}</h1>
    <p class="text-gray-500 mb-6">{{ $order->created_at->format('d/m/Y H:i') }}</p>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-3">
            <h2 class="font-bold text-lg">{{ __('ecommerce.order_details') }}</h2>
            <p><span class="text-gray-500">{{ __('ecommerce.status') }}:</span>
                <span class="font-semibold">{{ $order->status->label() }}</span></p>
            @if($order->tracking_number)
                <p><span class="text-gray-500">{{ __('ecommerce.tracking_number') }}:</span>
                    <span class="font-mono font-semibold">{{ $order->tracking_number }}</span></p>
            @endif
            <p><span class="text-gray-500">{{ __('ecommerce.total') }}:</span>
                <span class="font-bold">{{ number_format($order->total, 2) }} ج.م</span></p>
            <p><span class="text-gray-500">{{ __('ecommerce.payment_status') }}:</span> {{ $order->payment_status }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 hb-tracking-order-show">
            <h2 class="font-bold text-lg mb-4">{{ __('ecommerce.order_tracking') }}</h2>
            @include('shop.partials.order-tracking-route', ['order' => $order])
            <div class="mt-4">
                @include('shop.partials.order-tracking-log', ['order' => $order, 'open' => true])
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
        <h2 class="font-bold text-lg mb-4">{{ __('ecommerce.order_items') }}</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-gray-500">
                    <th class="text-right py-2">{{ __('ecommerce.product') }}</th>
                    <th class="text-right py-2">{{ __('ecommerce.quantity') }}</th>
                    <th class="text-right py-2">{{ __('ecommerce.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-b">
                        <td class="py-2">{{ $item->product_name }}</td>
                        <td class="py-2">{{ $item->quantity }}</td>
                        <td class="py-2">{{ number_format($item->total, 2) }} ج.م</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
