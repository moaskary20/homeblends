<x-mail::message>
# {{ __('ecommerce.admin_notif_new_order') }}

- **{{ __('ecommerce.order_number') }}:** {{ $order->order_number }}
- **{{ __('ecommerce.customer') }}:** {{ $order->user?->name }}
- **{{ __('ecommerce.total') }}:** {{ number_format($order->total, 2) }} ج.م

<x-mail::button :url="url('/admin/orders/'.$order->id.'/edit')">
{{ __('ecommerce.view_order') }}
</x-mail::button>
</x-mail::message>
