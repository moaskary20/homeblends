<x-mail::message>
# {{ __('ecommerce.mail_order_status_title') }}

{{ __('ecommerce.mail_order_number', ['number' => $order->order_number]) }}

**{{ __('ecommerce.status') }}:** {{ $order->status->label() }}

@if($comment)
{{ $comment }}
@endif

<x-mail::button :url="url('/ar/orders/'.$order->order_number)">
{{ __('ecommerce.order_tracking') }}
</x-mail::button>
</x-mail::message>
