<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit">
                {{ __('ecommerce.save_settings') }}
            </x-filament::button>

            <x-filament::button
                type="button"
                color="success"
                wire:click="sendTestEmail"
                wire:confirm="{{ __('ecommerce.send_test_email_confirm') }}"
            >
                {{ __('ecommerce.send_test_email') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
