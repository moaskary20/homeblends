<x-mail::message>
# {{ __('ecommerce.admin_notif_refund') }}

- **{{ __('ecommerce.order_number') }}:** {{ $refund->order?->order_number }}
- **{{ __('ecommerce.amount') }}:** {{ number_format($refund->amount, 2) }} ج.م

{{ $refund->reason }}
</x-mail::message>
