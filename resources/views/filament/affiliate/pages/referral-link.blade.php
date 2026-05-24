<x-filament-panels::page>
    <div class="space-y-6 max-w-2xl">
        <x-filament::section>
            <x-slot name="heading">{{ __('ecommerce.referral_link') }}</x-slot>
            <p class="text-sm text-gray-600 mb-4">{{ __('ecommerce.referral_link_help') }}</p>
            <div class="flex flex-wrap gap-2 items-center">
                <code class="flex-1 min-w-0 p-3 bg-gray-100 rounded-lg text-sm break-all" id="ref-url">{{ $this->getReferralUrl() }}</code>
                <x-filament::button type="button" onclick="navigator.clipboard.writeText(document.getElementById('ref-url').textContent); alert(@json(__('ecommerce.copied')))">
                    {{ __('ecommerce.copy_link') }}
                </x-filament::button>
            </div>
            <p class="mt-4 text-sm">{{ __('ecommerce.affiliate_code') }}: <strong>{{ $this->getReferralCode() }}</strong></p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
