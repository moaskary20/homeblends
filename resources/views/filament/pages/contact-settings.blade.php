<x-filament-panels::page>
    <div class="mb-6 rounded-2xl border border-amber-200/80 bg-gradient-to-l from-amber-50 via-white to-white p-5 shadow-sm dark:border-amber-900/40 dark:from-amber-950/30 dark:via-gray-900 dark:to-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                {{ __('ecommerce.contact_page_editor_tip') }}
            </p>
            <a
                href="{{ route('shop.contact') }}"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-amber-700"
            >
                <x-heroicon-o-eye class="w-5 h-5" />
                {{ __('ecommerce.preview_contact_page') }}
            </a>
        </div>
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
