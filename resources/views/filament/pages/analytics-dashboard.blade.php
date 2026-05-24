<x-filament-panels::page>
    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div class="w-full max-w-xs">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
                {{ __('ecommerce.report_period') }}
            </label>
            <select
                wire:model.live="period"
                class="fi-select-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="7">{{ __('ecommerce.period_7_days') }}</option>
                <option value="30">{{ __('ecommerce.period_30_days') }}</option>
                <option value="month">{{ __('ecommerce.period_month') }}</option>
                <option value="90">{{ __('ecommerce.period_90_days') }}</option>
                <option value="year">{{ __('ecommerce.period_year') }}</option>
            </select>
        </div>
    </div>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="
            [
                ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
                ...$this->getWidgetData(),
            ]
        "
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
