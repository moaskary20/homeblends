<x-mail::message>
# {{ __('ecommerce.admin_notif_return') }}

- **{{ __('ecommerce.order_number') }}:** {{ $return->order?->order_number }}

{{ $return->reason }}
</x-mail::message>
