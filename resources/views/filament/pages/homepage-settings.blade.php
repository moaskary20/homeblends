@php
    $newsCount = $this->getNewsCount();
    $heroCount = $this->getHeroCount();
    $collectionsCount = $this->getCollectionsCount();
    $partnersCount = $this->getPartnersCount();
@endphp

<x-filament-panels::page class="hb-homepage-settings">
    <link rel="stylesheet" href="{{ asset('css/filament-homepage-settings.css') }}?v={{ filemtime(public_path('css/filament-homepage-settings.css')) }}">

    <div class="hb-settings-hero mb-6 rounded-2xl border border-amber-200/80 bg-gradient-to-l from-amber-50 via-white to-white p-5 shadow-sm dark:border-amber-900/40 dark:from-amber-950/30 dark:via-gray-900 dark:to-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                    {{ __('ecommerce.homepage_editor_tip') }}
                </p>
            </div>
            <a
                href="{{ route('shop.home') }}"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-amber-700 hover:shadow-lg active:scale-[0.98]"
            >
                <x-heroicon-o-eye class="w-5 h-5" />
                {{ __('ecommerce.preview_homepage') }}
            </a>
        </div>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="hb-stat-card flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                <x-heroicon-o-megaphone class="w-6 h-6" />
            </span>
            <div>
                <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $newsCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ecommerce.homepage_news_ticker') }}</p>
            </div>
        </div>
        <div class="hb-stat-card flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                <x-heroicon-o-photo class="w-6 h-6" />
            </span>
            <div>
                <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $heroCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ecommerce.homepage_hero_slider') }}</p>
            </div>
        </div>
        <div class="hb-stat-card flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                <x-heroicon-o-squares-2x2 class="w-6 h-6" />
            </span>
            <div>
                <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $collectionsCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ecommerce.popular_collections') }}</p>
            </div>
        </div>
        <div class="hb-stat-card flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                <x-heroicon-o-building-storefront class="w-6 h-6" />
            </span>
            <div>
                <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $partnersCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ecommerce.homepage_partners') }}</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="hb-form-panel relative pb-24">
        {{ $this->form }}

        <div class="hb-sticky-bar fixed bottom-0 inset-x-0 z-20 border-t border-gray-200 bg-white/90 px-4 py-3 shadow-[0_-8px_30px_-12px_rgba(0,0,0,0.25)] backdrop-blur-md dark:border-gray-700 dark:bg-gray-900/90 lg:start-[var(--sidebar-width)]">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400 hidden sm:block">
                    {{ __('ecommerce.homepage_save_hint') }}
                </p>
                <div class="flex flex-wrap items-center gap-2 ms-auto">
                    <x-filament::button
                        tag="a"
                        href="{{ route('shop.home') }}"
                        target="_blank"
                        color="gray"
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        {{ __('ecommerce.preview_homepage') }}
                    </x-filament::button>
                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-check-circle"
                    >
                        {{ __('ecommerce.save_settings') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </form>
</x-filament-panels::page>
