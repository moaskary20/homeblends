@extends('shop.account._layout', ['current' => 'installments'])

@section('account_content')
    <h1 class="text-2xl font-bold text-[#3d3830] mb-6">{{ __('ecommerce.my_installments') }}</h1>

    @if(session('success'))
        <p class="mb-4 text-green-700 bg-green-50 rounded-lg px-4 py-2">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="mb-4 text-red-700 bg-red-50 rounded-lg px-4 py-2">{{ session('error') }}</p>
    @endif
    @if($errors->any())
        <p class="mb-4 text-red-700 bg-red-50 rounded-lg px-4 py-2">{{ $errors->first() }}</p>
    @endif

    @forelse($contracts as $contract)
        @php $next = $contract->nextUnpaid(); @endphp
        <article class="bg-white rounded-xl shadow-sm p-5 mb-6">
            <div class="flex flex-wrap justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-bold text-lg">{{ $contract->offer?->name ?? ($contract->offer_snapshot['name'] ?? __('ecommerce.installment_offer')) }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ __('ecommerce.order_number') }}:
                        <a class="text-amber-800 hover:underline" href="{{ route('shop.orders.show', $contract->order->order_number) }}">
                            {{ $contract->order->order_number }}
                        </a>
                    </p>
                </div>
                <div class="text-end">
                    <span class="inline-block px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">
                        {{ $contract->status->label() }}
                    </span>
                    <p class="text-sm mt-1">{{ __('ecommerce.remaining') }}: {{ number_format($contract->remainingTotal(), 2) }} {{ $contract->currency }}</p>
                </div>
            </div>

            @if($contract->orderItems->isNotEmpty())
                <ul class="hb-purchases-items hb-purchases-items--compact mb-4">
                    @foreach($contract->orderItems as $item)
                        <li class="hb-purchases-item">
                            <div class="hb-purchases-item-meta">
                                <p class="hb-purchases-item-name">{{ $item->product_name }}</p>
                            </div>
                            @include('shop.partials.item-fulfillment-badge', ['item' => $item])
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b">
                            <th class="py-2 text-start">#</th>
                            <th class="py-2 text-start">{{ __('ecommerce.installment_due_date') }}</th>
                            <th class="py-2 text-start">{{ __('ecommerce.amount') }}</th>
                            <th class="py-2 text-start">{{ __('ecommerce.status') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contract->installments as $row)
                            <tr class="border-b border-gray-100">
                                <td class="py-3">{{ $row->sequence }}</td>
                                <td class="py-3">{{ $row->due_date->format('d/m/Y') }}</td>
                                <td class="py-3 font-medium">{{ number_format((float) $row->amount, 2) }} {{ $contract->currency }}</td>
                                <td class="py-3">{{ $row->status->label() }}</td>
                                <td class="py-3 text-end">
                                    @if($next && $next->id === $row->id && $contract->isOpen())
                                        @if($paymentGateways->isEmpty())
                                            <span class="text-xs text-gray-500">{{ __('ecommerce.installment_gateway_required') }}</span>
                                        @else
                                            <form method="post" action="{{ route('shop.account.installments.pay', $row) }}" class="flex flex-wrap gap-2 justify-end">
                                                @csrf
                                                <select name="payment_gateway" required class="border rounded-lg px-2 py-1 text-sm">
                                                    @foreach($paymentGateways as $gateway)
                                                        <option value="{{ $gateway->code }}">{{ $gateway->displayName() }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="bg-amber-700 text-white text-sm px-3 py-1 rounded-lg">
                                                    {{ __('ecommerce.pay_installment') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @empty
        <p class="text-gray-500 bg-white rounded-xl p-8 text-center">{{ __('ecommerce.no_installments') }}</p>
    @endforelse
@endsection
