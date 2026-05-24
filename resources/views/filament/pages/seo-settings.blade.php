<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-3">
            <x-filament::button type="submit">
                {{ __('ecommerce.save_settings') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
