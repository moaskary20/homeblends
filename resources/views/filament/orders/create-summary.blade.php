@php
    $preview = $preview ?? [];
    $data = $data ?? [];
    $customerLabel = ($data['customer_type'] ?? '') === 'guest'
        ? ($data['guest_name'] ?? '—').' ('.($data['guest_phone'] ?? '').')'
        : (\App\Models\User::find($data['user_id'] ?? null)?->name ?? '—');
@endphp

<div class="space-y-3 text-sm">
    <div>
        <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('ecommerce.customer') }}:</span>
        {{ $customerLabel }}
    </div>
    <div>
        <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('ecommerce.order_items') }}:</span>
        {{ count($preview['lines'] ?? []) }}
    </div>
    <ul class="divide-y divide-gray-100 dark:divide-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        @foreach ($preview['lines'] ?? [] as $line)
            <li class="flex justify-between gap-4 px-3 py-2">
                <span>{{ $line['product_name'] }} × {{ $line['quantity'] }}</span>
                <span class="font-medium">{{ number_format($line['total'], 2) }} ج.م</span>
            </li>
        @endforeach
    </ul>
    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
        <span>{{ __('ecommerce.subtotal') }}</span>
        <span class="text-left font-medium">{{ number_format($preview['subtotal'] ?? 0, 2) }} ج.م</span>
        @if (($preview['discount_total'] ?? 0) > 0)
            <span>{{ __('ecommerce.discount') }}</span>
            <span class="text-left text-danger-600">−{{ number_format($preview['discount_total'], 2) }} ج.م</span>
        @endif
        <span>{{ __('ecommerce.shipping') }}</span>
        <span class="text-left">{{ number_format($preview['shipping_amount'] ?? 0, 2) }} ج.م</span>
        <span>{{ __('ecommerce.tax') }}</span>
        <span class="text-left">{{ number_format($preview['tax_amount'] ?? 0, 2) }} ج.م</span>
        <span class="font-semibold">{{ __('ecommerce.total') }}</span>
        <span class="text-left font-bold text-primary-600">{{ number_format($preview['total'] ?? 0, 2) }} ج.م</span>
    </div>
</div>
