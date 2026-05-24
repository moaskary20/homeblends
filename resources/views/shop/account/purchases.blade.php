@extends('shop.account._layout', ['current' => 'purchases'])

@section('account_content')
    <h1 class="text-2xl font-bold text-[#3d3830] mb-6">{{ __('ecommerce.my_purchases') }}</h1>

    @if($orders->isEmpty())
        <p class="text-gray-500 bg-white rounded-xl p-8 text-center">{{ __('ecommerce.no_orders') }}</p>
    @else
        <div class="bg-white rounded-xl shadow-sm divide-y">
            @foreach($orders as $order)
                <a href="{{ route('shop.orders.show', $order->order_number) }}"
                   class="block p-4 hover:bg-gray-50 flex flex-wrap justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                        <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">
                            {{ $order->status->label() }}
                        </span>
                        <p class="font-bold mt-1 text-end">{{ number_format($order->total, 2) }} {{ __('ecommerce.currency') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection
