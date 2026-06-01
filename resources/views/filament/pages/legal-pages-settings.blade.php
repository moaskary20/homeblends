<x-filament-panels::page>
    <div class="mb-6 rounded-2xl border border-stone-200/80 bg-gradient-to-l from-stone-50 via-white to-white p-5 shadow-sm dark:border-stone-700 dark:from-stone-900/40 dark:via-gray-900 dark:to-gray-900">
        <p class="text-sm font-medium text-stone-700 dark:text-stone-300">
            {{ __('ecommerce.legal_pages_editor_tip') }}
        </p>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-check-circle">
                {{ __('ecommerce.save_settings') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
