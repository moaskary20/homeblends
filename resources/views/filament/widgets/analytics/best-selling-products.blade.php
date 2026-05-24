<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('ecommerce.best_selling_products') }}</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500">
                        <th class="py-2 font-medium">{{ __('ecommerce.product') }}</th>
                        <th class="py-2 font-medium">{{ __('ecommerce.units_sold') }}</th>
                        <th class="py-2 font-medium">{{ __('ecommerce.revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getProducts() as $row)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2">{{ $row->product_name }}</td>
                            <td class="py-2">{{ number_format($row->units_sold) }}</td>
                            <td class="py-2 font-semibold">{{ number_format($row->revenue, 2) }} ج.م</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-gray-500">{{ __('ecommerce.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
