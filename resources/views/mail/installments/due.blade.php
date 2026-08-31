<x-mail::message>
# {{ __('ecommerce.mail_installment_title') }}

{{ __('ecommerce.mail_hello', ['name' => $user->name ?? '']) }}

@if($kind === 'upcoming')
{{ __('ecommerce.mail_installment_upcoming_line', [
    'sequence' => $installment->sequence,
    'date' => $installment->due_date?->format('d/m/Y'),
    'amount' => number_format((float) $installment->amount, 2),
    'currency' => $contract->currency ?? 'EGP',
]) }}
@elseif($kind === 'overdue')
{{ __('ecommerce.mail_installment_overdue_line', [
    'sequence' => $installment->sequence,
    'date' => $installment->due_date?->format('d/m/Y'),
    'amount' => number_format((float) $installment->amount, 2),
    'currency' => $contract->currency ?? 'EGP',
]) }}
@else
{{ __('ecommerce.mail_installment_due_line', [
    'sequence' => $installment->sequence,
    'date' => $installment->due_date?->format('d/m/Y'),
    'amount' => number_format((float) $installment->amount, 2),
    'currency' => $contract->currency ?? 'EGP',
]) }}
@endif

@if($contract?->order?->order_number)
**{{ __('ecommerce.order_number') }}:** {{ $contract->order->order_number }}
@endif

<x-mail::button :url="url('/ar/account/installments')">
{{ __('ecommerce.pay_installment') }}
</x-mail::button>

{{ __('ecommerce.mail_thanks') }}
</x-mail::message>
