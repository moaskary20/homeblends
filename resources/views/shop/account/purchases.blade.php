@extends('shop.account._layout', ['current' => 'purchases'])

@section('account_content')
    <h1 class="text-2xl font-bold text-[#3d3830] mb-6">{{ __('ecommerce.my_purchases') }}</h1>

    @if($orders->isEmpty())
        <p class="text-gray-500 bg-white rounded-xl p-8 text-center">{{ __('ecommerce.no_orders') }}</p>
    @else
        <div class="hb-purchases-list">
            @foreach($orders as $order)
                <article class="hb-purchases-order">
                    <header class="hb-purchases-order-header">
                        <div>
                            <a href="{{ route('shop.orders.show', $order->order_number) }}" class="hb-purchases-order-number">
                                {{ $order->order_number }}
                            </a>
                            <p class="hb-purchases-order-date">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="hb-purchases-order-aside">
                            <span class="hb-tracking-status-pill is-{{ $order->status->value }}">
                                {{ $order->status->label() }}
                            </span>
                            <p class="hb-purchases-order-total">
                                {{ number_format($order->total, 2) }} {{ __('ecommerce.currency') }}
                            </p>
                        </div>
                    </header>

                    <ul class="hb-purchases-items">
                        @foreach($order->items as $item)
                            <li class="hb-purchases-item">
                                <div class="hb-purchases-item-meta">
                                    <p class="hb-purchases-item-name">{{ $item->product_name }}</p>
                                    <p class="hb-purchases-item-qty">{{ __('ecommerce.quantity') }}: {{ $item->quantity }}</p>
                                </div>
                                @include('shop.partials.item-fulfillment-badge', ['item' => $item])
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection
