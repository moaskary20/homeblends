@php
    $shipping = $order->shipping_address ?? [];
    $customerName = $order->user?->name ?? ($shipping['name'] ?? __('ecommerce.guest_customer'));
@endphp

<div class="invoice-header">
    <h1>{{ __('ecommerce.tax_invoice') }}</h1>
    <p class="brand">{{ config('app.name', 'هوم بلند') }}</p>
</div>

<div class="invoice-meta">
    <p><strong>{{ __('ecommerce.order_number') }}:</strong> {{ $order->order_number }}</p>
    <p><strong>{{ __('ecommerce.created_at') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>{{ __('ecommerce.customer') }}:</strong> {{ $customerName }}</p>
    @if (! empty($shipping['phone']))
        <p><strong>{{ __('ecommerce.phone') }}:</strong> {{ $shipping['phone'] }}</p>
    @endif
    @if (! empty($shipping['address']))
        <p><strong>{{ __('ecommerce.shipping_address') }}:</strong>
            {{ $shipping['city'] ?? '' }} — {{ $shipping['address'] }}
        </p>
    @endif
    <p><strong>{{ __('ecommerce.status') }}:</strong> {{ $order->status->label() }}</p>
    <p><strong>{{ __('ecommerce.payment_status') }}:</strong>
        @switch($order->payment_status)
            @case('paid') {{ __('ecommerce.payment_paid') }} @break
            @case('failed') {{ __('ecommerce.payment_failed') }} @break
            @case('refunded') {{ __('ecommerce.payment_refunded') }} @break
            @default {{ __('ecommerce.payment_pending') }}
        @endswitch
    </p>
</div>

<table class="invoice-table">
    <thead>
        <tr>
            <th>{{ __('ecommerce.product') }}</th>
            <th>{{ __('ecommerce.sku') }}</th>
            <th>{{ __('ecommerce.quantity') }}</th>
            <th>{{ __('ecommerce.unit_price') }}</th>
            <th>{{ __('ecommerce.total') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                <td>{{ number_format($item->total, 2) }} ج.م</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="invoice-totals">
    <p><span>{{ __('ecommerce.subtotal') }}</span><span>{{ number_format($order->subtotal, 2) }} ج.م</span></p>
    <p><span>{{ __('ecommerce.discount') }}</span><span>{{ number_format($order->discount_amount, 2) }} ج.م</span></p>
    <p><span>{{ __('ecommerce.shipping') }}</span><span>{{ number_format($order->shipping_amount, 2) }} ج.م</span></p>
    <p><span>{{ __('ecommerce.tax') }}</span><span>{{ number_format($order->tax_amount, 2) }} ج.م</span></p>
    <p class="grand-total"><span>{{ __('ecommerce.total') }}</span><span>{{ number_format($order->total, 2) }} ج.م</span></p>
</div>
