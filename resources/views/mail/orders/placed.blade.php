<x-mail::message>
# {{ __('ecommerce.mail_order_placed_title') }}

{{ __('ecommerce.mail_hello', ['name' => $user->name ?? '']) }}

{{ __('ecommerce.mail_order_placed_line', ['number' => $order->order_number]) }}

**{{ __('ecommerce.total') }}:** {{ number_format($order->total, 2) }} {{ $order->currency }}

<x-mail::button :url="url('/ar/orders/'.$order->order_number)">
{{ __('ecommerce.view_order') }}
</x-mail::button>

{{ __('ecommerce.mail_thanks') }}
</x-mail::message>
