<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Excel import --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('ecommerce.import_instructions_title') }}</x-slot>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li>{{ __('ecommerce.import_step_download') }}</li>
                <li>{{ __('ecommerce.import_step_fill') }}</li>
                <li>{{ __('ecommerce.import_step_upload') }}</li>
            </ol>
            <p class="mt-4 text-sm text-amber-700 dark:text-amber-400">
                {{ __('ecommerce.import_sku_note') }}
            </p>
        </x-filament::section>

        <form wire:submit="import">
            {{ $this->form }}

            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">
                    {{ __('ecommerce.start_import') }}
                </x-filament::button>
            </div>
        </form>

        @if($importCreated !== null)
            <x-filament::section>
                <x-slot name="heading">{{ __('ecommerce.import_results') }}</x-slot>
                <ul class="text-sm space-y-1">
                    <li>{{ __('ecommerce.import_created', ['count' => $importCreated]) }}</li>
                    <li>{{ __('ecommerce.import_updated', ['count' => $importUpdated]) }}</li>
                </ul>
                @if(count($importErrors))
                    <div class="mt-4 text-sm text-red-600">
                        <p class="font-semibold">{{ __('ecommerce.import_errors') }}</p>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-filament::section>
        @endif

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_ariika_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_ariika_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_panel_hint') }}
            </p>

            <form wire:submit="runScrape">
                {{ $this->scrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($scrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($scrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($scrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $scrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $scrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($scrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($scrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_sedar_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_sedar_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_sedar_category_hint') }}
            </p>

            <form wire:submit="runSedarScrape">
                {{ $this->sedarScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewSedarScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($sedarScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($sedarScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sedarScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($sedarScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $sedarScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $sedarScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($sedarScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($sedarScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_gemma_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_gemma_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_gemma_category_hint') }}
            </p>

            <form wire:submit="runGemmaScrape">
                {{ $this->gemmaScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewGemmaScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($gemmaScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($gemmaScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gemmaScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($gemmaScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $gemmaScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $gemmaScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($gemmaScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($gemmaScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_hans_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_hans_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_hans_category_hint') }}
            </p>

            <form wire:submit="runHansScrape">
                {{ $this->hansScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewHansScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($hansScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($hansScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hansScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($hansScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $hansScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $hansScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($hansScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($hansScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_cleopatra_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_cleopatra_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_cleopatra_category_hint') }}
            </p>

            <form wire:submit="runCleopatraScrape">
                {{ $this->cleopatraScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewCleopatraScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($cleopatraScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($cleopatraScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cleopatraScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($cleopatraScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $cleopatraScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $cleopatraScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($cleopatraScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($cleopatraScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_mahgoub_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_mahgoub_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_mahgoub_category_hint') }}
            </p>

            <form wire:submit="runMahgoubScrape">
                {{ $this->mahgoubScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewMahgoubScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($mahgoubScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($mahgoubScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mahgoubScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($mahgoubScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $mahgoubScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $mahgoubScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($mahgoubScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($mahgoubScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_sallab_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_sallab_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_sallab_category_hint') }}
            </p>

            <form wire:submit="runSallabScrape">
                {{ $this->sallabScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewSallabScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($sallabScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($sallabScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sallabScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($sallabScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $sallabScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $sallabScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($sallabScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($sallabScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-globe-alt">
            <x-slot name="heading">{{ __('ecommerce.scrape_raya_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                {{ __('ecommerce.scrape_raya_section_description') }}
            </p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mb-4 font-medium">
                {{ __('ecommerce.scrape_raya_category_hint') }}
            </p>

            <form wire:submit="runRayaScrape">
                {{ $this->rayaScrapeForm }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button type="button" wire:click="previewRayaScrape" color="gray" icon="heroicon-o-eye">
                        {{ __('ecommerce.scrape_preview') }}
                    </x-filament::button>
                    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-down" wire:confirm="{{ __('ecommerce.scrape_confirm') }}">
                        {{ __('ecommerce.scrape_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($rayaScrapePreview)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm font-medium mb-2">{{ __('ecommerce.scrape_preview_table', ['count' => count($rayaScrapePreview)]) }}</p>
                    <table class="w-full text-sm text-start border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2">{{ __('ecommerce.sku') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.name') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.category') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.regular_price') }}</th>
                                <th class="px-3 py-2">{{ __('ecommerce.stock_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rayaScrapePreview as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['name'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2">{{ $row['price'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stock'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($rayaScrapeCreated !== null)
                <div class="mt-6 text-sm space-y-1">
                    <p class="font-semibold">{{ __('ecommerce.scrape_results') }}</p>
                    <p>{{ __('ecommerce.import_created', ['count' => $rayaScrapeCreated]) }}</p>
                    <p>{{ __('ecommerce.import_updated', ['count' => $rayaScrapeUpdated]) }}</p>
                </div>
            @endif

            @if(count($rayaScrapeErrors))
                <div class="mt-4 text-sm text-red-600">
                    <ul class="list-disc list-inside">
                        @foreach($rayaScrapeErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-photo">
            <x-slot name="heading">{{ __('ecommerce.sync_images_section_title') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                {{ __('ecommerce.sync_images_section_description') }}
            </p>

            <form wire:submit="syncScrapedImages">
                {{ $this->syncImagesForm }}

                <div class="mt-6">
                    <x-filament::button type="submit" icon="heroicon-o-arrow-path" wire:confirm="{{ __('ecommerce.sync_images_confirm') }}">
                        {{ __('ecommerce.sync_images_start') }}
                    </x-filament::button>
                </div>
            </form>

            @if($imagesSynced !== null)
                <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">
                    {{ __('ecommerce.sync_images_success', ['count' => $imagesSynced]) }}
                </p>
            @endif

            @if(count($syncImagesErrors))
                <ul class="mt-4 text-sm text-red-600 list-disc list-inside">
                    @foreach($syncImagesErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
