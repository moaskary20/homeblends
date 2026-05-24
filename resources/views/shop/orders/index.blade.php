@extends('layouts.shop')

@section('content')
    <h1 class="text-3xl font-bold mb-8">{{ __('ecommerce.my_orders') }}</h1>

    @guest
        <p class="text-gray-600 mb-4">{{ __('ecommerce.login_to_view_orders') }}</p>
        <a href="/admin/login" class="text-amber-600 font-semibold">{{ __('ecommerce.login') }}</a>
    @else
        @if($orders->isEmpty())
            <p class="text-gray-500">{{ __('ecommerce.no_orders') }}</p>
        @else
            <div class="bg-white rounded-xl shadow-sm divide-y">
                @foreach($orders as $order)
                    <a href="{{ route('shop.orders.show', $order->order_number) }}"
                       class="block p-4 hover:bg-gray-50 flex flex-wrap justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-left">
                            <span class="inline-block px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">
                                {{ $order->status->label() }}
                            </span>
                            <p class="font-bold mt-1">{{ number_format($order->total, 2) }} ج.م</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    @endguest
@endsection
