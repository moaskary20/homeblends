<?php

namespace App\Filament\Pages;

use App\Exports\ProductsImportTemplateExport;
use App\Imports\ProductsImport;
use App\Services\ProductScraper\AriikaScraperService;
use App\Services\ProductScraper\CleopatraScraperService;
use App\Services\ProductScraper\GemmaScraperService;
use App\Services\ProductScraper\HansScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use App\Services\ProductScraper\SedarScraperService;
use App\Services\ProductScraper\SyncScrapedProductImagesService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportProductsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.pages.import-products';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public ?array $scrapeData = [];

    public ?array $sedarScrapeData = [];

    public ?array $gemmaScrapeData = [];

    public ?array $hansScrapeData = [];

    public ?int $importCreated = null;

    public ?int $importUpdated = null;

    public array $importErrors = [];

    /** @var array<int, array<string, mixed>>|null */
    public ?array $scrapePreview = null;

    public ?int $scrapeCreated = null;

    public ?int $scrapeUpdated = null;

    public array $scrapeErrors = [];

    /** @var array<int, array<string, mixed>>|null */
    public ?array $sedarScrapePreview = null;

    public ?int $sedarScrapeCreated = null;

    public ?int $sedarScrapeUpdated = null;

    public array $sedarScrapeErrors = [];

    /** @var array<int, array<string, mixed>>|null */
    public ?array $gemmaScrapePreview = null;

    public ?int $gemmaScrapeCreated = null;

    public ?int $gemmaScrapeUpdated = null;

    public array $gemmaScrapeErrors = [];

    /** @var array<int, array<string, mixed>>|null */
    public ?array $hansScrapePreview = null;

    public ?int $hansScrapeCreated = null;

    public ?int $hansScrapeUpdated = null;

    public array $hansScrapeErrors = [];

    public ?array $cleopatraScrapeData = [];

    /** @var array<int, array<string, mixed>>|null */
    public ?array $cleopatraScrapePreview = null;

    public ?int $cleopatraScrapeCreated = null;

    public ?int $cleopatraScrapeUpdated = null;

    public array $cleopatraScrapeErrors = [];

    public ?array $syncImagesData = [];

    public ?int $imagesSynced = null;

    public array $syncImagesErrors = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.import_products');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('products.create') || $user->can('products.import'));
    }

    public function mount(): void
    {
        $defaultCollections = array_keys(app(AriikaScraperService::class)->getFurnitureCollectionOptions());

        $defaultSedarCollections = array_keys(app(SedarScraperService::class)->getCollectionOptions());

        $defaultGemmaCollections = array_keys(app(GemmaScraperService::class)->getCollectionOptions());

        $defaultHansCollections = array_keys(app(HansScraperService::class)->getCollectionOptions());

        $defaultCleopatraCollections = array_keys(app(CleopatraScraperService::class)->getCollectionOptions());

        $this->form->fill();
        $this->scrapeForm->fill([
            'source' => 'ariika',
            'collections' => array_slice($defaultCollections, 0, 3),
            'max_per_collection' => 5,
            'download_images' => true,
        ]);
        $this->sedarScrapeForm->fill([
            'collections' => array_slice($defaultSedarCollections, 0, 2),
            'max_per_collection' => 5,
            'download_images' => true,
        ]);
        $this->gemmaScrapeForm->fill([
            'collections' => array_slice($defaultGemmaCollections, 0, 3),
            'max_per_collection' => 5,
            'download_images' => true,
        ]);
        $this->hansScrapeForm->fill([
            'collections' => array_slice($defaultHansCollections, 0, 1),
            'max_per_collection' => 5,
            'download_images' => true,
        ]);
        $this->cleopatraScrapeForm->fill([
            'collections' => array_slice($defaultCleopatraCollections, 0, 3),
            'max_per_collection' => 5,
            'download_images' => true,
        ]);
        $this->syncImagesForm->fill([
            'limit' => 20,
            'sku' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file')
                    ->label(__('ecommerce.import_file'))
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->required()
                    ->maxSize(10240)
                    ->directory('imports/products')
                    ->visibility('private'),
            ])
            ->statePath('data');
    }

    public function scrapeForm(Form $form): Form
    {
        $collectionOptions = app(AriikaScraperService::class)->getFurnitureCollectionOptions();

        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->label(__('ecommerce.scrape_source'))
                    ->options(['ariika' => 'Ariika — ariika.com/ar'])
                    ->default('ariika')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\CheckboxList::make('collections')
                    ->label(__('ecommerce.scrape_collections'))
                    ->options($collectionOptions)
                    ->columns(2)
                    ->required()
                    ->minItems(1),
                Forms\Components\TextInput::make('max_per_collection')
                    ->label(__('ecommerce.scrape_max_per_collection'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(5)
                    ->required(),
                Forms\Components\Toggle::make('download_images')
                    ->label(__('ecommerce.scrape_download_images'))
                    ->default(true),
            ])
            ->statePath('scrapeData');
    }

    public function sedarScrapeForm(Form $form): Form
    {
        $collectionOptions = app(SedarScraperService::class)->getCollectionOptions();

        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->label(__('ecommerce.scrape_source'))
                    ->options(['sedar' => 'Sedar Global — sedarglobal.com'])
                    ->default('sedar')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\CheckboxList::make('collections')
                    ->label(__('ecommerce.scrape_sedar_collections'))
                    ->options($collectionOptions)
                    ->columns(2)
                    ->required()
                    ->minItems(1),
                Forms\Components\TextInput::make('max_per_collection')
                    ->label(__('ecommerce.scrape_max_per_collection'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(5)
                    ->required(),
                Forms\Components\Toggle::make('download_images')
                    ->label(__('ecommerce.scrape_download_images'))
                    ->default(true),
            ])
            ->statePath('sedarScrapeData');
    }

    public function gemmaScrapeForm(Form $form): Form
    {
        $collectionOptions = app(GemmaScraperService::class)->getCollectionOptions();

        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->label(__('ecommerce.scrape_source'))
                    ->options(['gemma' => 'Gemma — gemma.com.eg/shop'])
                    ->default('gemma')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\CheckboxList::make('collections')
                    ->label(__('ecommerce.scrape_gemma_collections'))
                    ->options($collectionOptions)
                    ->columns(2)
                    ->required()
                    ->minItems(1),
                Forms\Components\TextInput::make('max_per_collection')
                    ->label(__('ecommerce.scrape_max_per_collection'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(5)
                    ->required(),
                Forms\Components\Toggle::make('download_images')
                    ->label(__('ecommerce.scrape_download_images'))
                    ->default(true),
            ])
            ->statePath('gemmaScrapeData');
    }

    public function hansScrapeForm(Form $form): Form
    {
        $collectionOptions = app(HansScraperService::class)->getCollectionOptions();

        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->label(__('ecommerce.scrape_source'))
                    ->options(['hans' => 'HANS — hansegypt.com/ar'])
                    ->default('hans')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\CheckboxList::make('collections')
                    ->label(__('ecommerce.scrape_hans_collections'))
                    ->options($collectionOptions)
                    ->columns(2)
                    ->required()
                    ->minItems(1),
                Forms\Components\TextInput::make('max_per_collection')
                    ->label(__('ecommerce.scrape_max_per_collection'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(5)
                    ->required(),
                Forms\Components\Toggle::make('download_images')
                    ->label(__('ecommerce.scrape_download_images'))
                    ->default(true),
            ])
            ->statePath('hansScrapeData');
    }

    public function cleopatraScrapeForm(Form $form): Form
    {
        $collectionOptions = app(CleopatraScraperService::class)->getCollectionOptions();

        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->label(__('ecommerce.scrape_source'))
                    ->options(['cleopatra' => 'Cleopatra — cleopatraceramics.com/ar'])
                    ->default('cleopatra')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\CheckboxList::make('collections')
                    ->label(__('ecommerce.scrape_cleopatra_collections'))
                    ->options($collectionOptions)
                    ->columns(2)
                    ->required()
                    ->minItems(1),
                Forms\Components\TextInput::make('max_per_collection')
                    ->label(__('ecommerce.scrape_max_per_collection'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->default(5)
                    ->required(),
                Forms\Components\Toggle::make('download_images')
                    ->label(__('ecommerce.scrape_download_images'))
                    ->default(true),
            ])
            ->statePath('cleopatraScrapeData');
    }

    public function syncImagesForm(Form $form): Form
    {
        $skuOptions = app(SyncScrapedProductImagesService::class)
            ->scrapedProductsOptions()
            ->mapWithKeys(fn ($p) => [$p->sku => "{$p->sku} — {$p->name}"])
            ->all();

        return $form
            ->schema([
                Forms\Components\Placeholder::make('scraped_count')
                    ->label(__('ecommerce.scraped_products_count'))
                    ->content(fn () => (string) app(SyncScrapedProductImagesService::class)->scrapedProductsCount()),
                Forms\Components\TextInput::make('limit')
                    ->label(__('ecommerce.sync_images_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(20)
                    ->required(),
                Forms\Components\Select::make('sku')
                    ->label(__('ecommerce.sync_images_single_sku'))
                    ->options($skuOptions)
                    ->searchable()
                    ->placeholder(__('ecommerce.sync_images_all_hint')),
            ])
            ->statePath('syncImagesData');
    }

    protected function getForms(): array
    {
        return ['form', 'scrapeForm', 'sedarScrapeForm', 'gemmaScrapeForm', 'hansScrapeForm', 'cleopatraScrapeForm', 'syncImagesForm'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label(__('ecommerce.download_import_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ProductsImportTemplateExport,
                    'products-import-template.xlsx'
                )),
        ];
    }

    public function import(): void
    {
        $path = $this->data['file'] ?? null;
        if (is_array($path)) {
            $path = $path[array_key_first($path)] ?? null;
        }

        if (! $path) {
            Notification::make()->title(__('ecommerce.import_file_required'))->danger()->send();

            return;
        }

        $disk = config('filament.default_filesystem_disk', config('filesystems.default', 'local'));
        $fullPath = Storage::disk($disk)->path($path);

        $import = new ProductsImport;

        try {
            Excel::import($import, $fullPath);
        } catch (ValidationException $e) {
            $messages = collect($e->failures())
                ->map(fn ($failure) => __('ecommerce.import_validation_error', [
                    'row' => $failure->row(),
                    'errors' => implode(', ', $failure->errors()),
                ]))
                ->all();

            $this->importErrors = $messages;
            Notification::make()
                ->title(__('ecommerce.import_failed'))
                ->body(implode("\n", array_slice($messages, 0, 5)))
                ->danger()
                ->send();

            return;
        }

        Storage::disk($disk)->delete($path);

        $this->importCreated = $import->getCreatedCount();
        $this->importUpdated = $import->getUpdatedCount();
        $this->importErrors = $import->getErrors()->all();
        $this->data = [];

        Notification::make()
            ->title(__('ecommerce.import_success', [
                'created' => $this->importCreated,
                'updated' => $this->importUpdated,
            ]))
            ->success()
            ->send();
    }

    public function previewScrape(): void
    {
        $this->scrapePreview = null;
        $this->scrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchScrapeItems();
            $this->scrapeErrors = $this->formatScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_no_products'));
            }

            $this->scrapePreview = $items->map(fn (array $p) => [
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category_name'],
                'price' => number_format($p['regular_price'], 0).' '.__('ecommerce.currency'),
                'stock' => $p['stock_quantity'],
                'url' => $p['source_url'],
            ])->all();

            Notification::make()
                ->title(__('ecommerce.scrape_preview_ready', ['count' => count($this->scrapePreview)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->scrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runScrape(): void
    {
        $this->scrapeCreated = null;
        $this->scrapeUpdated = null;
        $this->scrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchScrapeItems();
            $collectionErrors = $this->formatScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_no_products'));
            }

            $importer = app(ScrapedProductImporter::class);
            $downloadImages = (bool) ($this->scrapeData['download_images'] ?? true);
            $importer->import($items, $downloadImages);

            $this->scrapeCreated = $importer->getCreatedCount();
            $this->scrapeUpdated = $importer->getUpdatedCount();
            $this->scrapeErrors = array_merge($collectionErrors, $importer->getErrors()->all());
            $this->scrapePreview = null;

            Notification::make()
                ->title(__('ecommerce.scrape_success', [
                    'created' => $this->scrapeCreated,
                    'updated' => $this->scrapeUpdated,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->scrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: AriikaScraperService}
     */
    protected function fetchScrapeItems(): array
    {
        $state = $this->scrapeForm->getState();
        $collections = $state['collections'] ?? [];
        $limit = max(1, min(50, (int) ($state['max_per_collection'] ?? 5)));

        $scraper = app(AriikaScraperService::class);
        $items = $scraper->scrapeFurniture($collections, $limit);

        return [$items, $scraper];
    }

    public function previewSedarScrape(): void
    {
        $this->sedarScrapePreview = null;
        $this->sedarScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchSedarScrapeItems();
            $this->sedarScrapeErrors = $this->formatSedarScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_sedar_no_products'));
            }

            $this->sedarScrapePreview = $items->map(fn (array $p) => [
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category_name'],
                'price' => number_format($p['regular_price'], 0).' '.__('ecommerce.currency'),
                'stock' => $p['stock_quantity'],
                'url' => $p['source_url'],
            ])->all();

            Notification::make()
                ->title(__('ecommerce.scrape_preview_ready', ['count' => count($this->sedarScrapePreview)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->sedarScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runSedarScrape(): void
    {
        $this->sedarScrapeCreated = null;
        $this->sedarScrapeUpdated = null;
        $this->sedarScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchSedarScrapeItems();
            $pageErrors = $this->formatSedarScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_sedar_no_products'));
            }

            $importer = app(ScrapedProductImporter::class);
            $downloadImages = (bool) ($this->sedarScrapeData['download_images'] ?? true);
            $importer->import($items, $downloadImages);

            $this->sedarScrapeCreated = $importer->getCreatedCount();
            $this->sedarScrapeUpdated = $importer->getUpdatedCount();
            $this->sedarScrapeErrors = array_merge($pageErrors, $importer->getErrors()->all());
            $this->sedarScrapePreview = null;

            Notification::make()
                ->title(__('ecommerce.scrape_success', [
                    'created' => $this->sedarScrapeCreated,
                    'updated' => $this->sedarScrapeUpdated,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->sedarScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: SedarScraperService}
     */
    protected function fetchSedarScrapeItems(): array
    {
        $state = $this->sedarScrapeForm->getState();
        $collections = $state['collections'] ?? [];
        $limit = max(1, min(50, (int) ($state['max_per_collection'] ?? 5)));

        $scraper = app(SedarScraperService::class);
        $items = $scraper->scrapeCollections($collections, $limit);

        return [$items, $scraper];
    }

    /**
     * @return array<int, string>
     */
    protected function formatSedarScrapeErrors(SedarScraperService $scraper): array
    {
        return $scraper->getScrapeErrors()
            ->map(fn (array $e) => __('ecommerce.scrape_sedar_collection_error', [
                'handle' => $e['handle'],
                'message' => $e['message'],
            ]))
            ->all();
    }

    public function previewGemmaScrape(): void
    {
        $this->gemmaScrapePreview = null;
        $this->gemmaScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchGemmaScrapeItems();
            $this->gemmaScrapeErrors = $this->formatGemmaScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_gemma_no_products'));
            }

            $this->gemmaScrapePreview = $items->map(fn (array $p) => [
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category_name'],
                'price' => number_format($p['regular_price'], 0).' '.__('ecommerce.currency'),
                'stock' => $p['stock_quantity'],
                'url' => $p['source_url'],
            ])->all();

            Notification::make()
                ->title(__('ecommerce.scrape_preview_ready', ['count' => count($this->gemmaScrapePreview)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->gemmaScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runGemmaScrape(): void
    {
        $this->gemmaScrapeCreated = null;
        $this->gemmaScrapeUpdated = null;
        $this->gemmaScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchGemmaScrapeItems();
            $collectionErrors = $this->formatGemmaScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_gemma_no_products'));
            }

            $importer = app(ScrapedProductImporter::class);
            $downloadImages = (bool) ($this->gemmaScrapeData['download_images'] ?? true);
            $importer->import($items, $downloadImages);

            $this->gemmaScrapeCreated = $importer->getCreatedCount();
            $this->gemmaScrapeUpdated = $importer->getUpdatedCount();
            $this->gemmaScrapeErrors = array_merge($collectionErrors, $importer->getErrors()->all());
            $this->gemmaScrapePreview = null;

            Notification::make()
                ->title(__('ecommerce.scrape_success', [
                    'created' => $this->gemmaScrapeCreated,
                    'updated' => $this->gemmaScrapeUpdated,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->gemmaScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: GemmaScraperService}
     */
    protected function fetchGemmaScrapeItems(): array
    {
        $state = $this->gemmaScrapeForm->getState();
        $collections = $state['collections'] ?? [];
        $limit = max(1, min(50, (int) ($state['max_per_collection'] ?? 5)));

        $scraper = app(GemmaScraperService::class);
        $items = $scraper->scrapeCollections($collections, $limit);

        return [$items, $scraper];
    }

    /**
     * @return array<int, string>
     */
    protected function formatGemmaScrapeErrors(GemmaScraperService $scraper): array
    {
        return $scraper->getScrapeErrors()
            ->map(fn (array $e) => __('ecommerce.scrape_gemma_collection_error', [
                'handle' => $e['handle'],
                'message' => $e['message'],
            ]))
            ->all();
    }

    public function previewHansScrape(): void
    {
        $this->hansScrapePreview = null;
        $this->hansScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchHansScrapeItems();
            $this->hansScrapeErrors = $this->formatHansScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_hans_no_products'));
            }

            $this->hansScrapePreview = $items->map(fn (array $p) => [
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category_name'],
                'price' => number_format($p['regular_price'], 0).' '.__('ecommerce.currency'),
                'stock' => $p['stock_quantity'],
                'url' => $p['source_url'],
            ])->all();

            Notification::make()
                ->title(__('ecommerce.scrape_preview_ready', ['count' => count($this->hansScrapePreview)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->hansScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runHansScrape(): void
    {
        $this->hansScrapeCreated = null;
        $this->hansScrapeUpdated = null;
        $this->hansScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchHansScrapeItems();
            $collectionErrors = $this->formatHansScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_hans_no_products'));
            }

            $importer = app(ScrapedProductImporter::class);
            $downloadImages = (bool) ($this->hansScrapeData['download_images'] ?? true);
            $importer->import($items, $downloadImages);

            $this->hansScrapeCreated = $importer->getCreatedCount();
            $this->hansScrapeUpdated = $importer->getUpdatedCount();
            $this->hansScrapeErrors = array_merge($collectionErrors, $importer->getErrors()->all());
            $this->hansScrapePreview = null;

            Notification::make()
                ->title(__('ecommerce.scrape_success', [
                    'created' => $this->hansScrapeCreated,
                    'updated' => $this->hansScrapeUpdated,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->hansScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: HansScraperService}
     */
    protected function fetchHansScrapeItems(): array
    {
        $state = $this->hansScrapeForm->getState();
        $collections = $state['collections'] ?? [];
        $limit = max(1, min(50, (int) ($state['max_per_collection'] ?? 5)));

        $scraper = app(HansScraperService::class);
        $items = $scraper->scrapeCollections($collections, $limit);

        return [$items, $scraper];
    }

    /**
     * @return array<int, string>
     */
    protected function formatHansScrapeErrors(HansScraperService $scraper): array
    {
        return $scraper->getScrapeErrors()
            ->map(fn (array $e) => __('ecommerce.scrape_hans_collection_error', [
                'handle' => $e['handle'],
                'message' => $e['message'],
            ]))
            ->all();
    }

    public function previewCleopatraScrape(): void
    {
        $this->cleopatraScrapePreview = null;
        $this->cleopatraScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchCleopatraScrapeItems();
            $this->cleopatraScrapeErrors = $this->formatCleopatraScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_cleopatra_no_products'));
            }

            $this->cleopatraScrapePreview = $items->map(fn (array $p) => [
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category_name'],
                'price' => number_format($p['regular_price'], 0).' '.__('ecommerce.currency'),
                'stock' => $p['stock_quantity'],
                'url' => $p['source_url'],
            ])->all();

            Notification::make()
                ->title(__('ecommerce.scrape_preview_ready', ['count' => count($this->cleopatraScrapePreview)]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->cleopatraScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runCleopatraScrape(): void
    {
        $this->cleopatraScrapeCreated = null;
        $this->cleopatraScrapeUpdated = null;
        $this->cleopatraScrapeErrors = [];

        try {
            [$items, $scraper] = $this->fetchCleopatraScrapeItems();
            $collectionErrors = $this->formatCleopatraScrapeErrors($scraper);

            if ($items->isEmpty()) {
                throw new \RuntimeException(__('ecommerce.scrape_cleopatra_no_products'));
            }

            $importer = app(ScrapedProductImporter::class);
            $downloadImages = (bool) ($this->cleopatraScrapeData['download_images'] ?? true);
            $importer->import($items, $downloadImages);

            $this->cleopatraScrapeCreated = $importer->getCreatedCount();
            $this->cleopatraScrapeUpdated = $importer->getUpdatedCount();
            $this->cleopatraScrapeErrors = array_merge($collectionErrors, $importer->getErrors()->all());
            $this->cleopatraScrapePreview = null;

            Notification::make()
                ->title(__('ecommerce.scrape_success', [
                    'created' => $this->cleopatraScrapeCreated,
                    'updated' => $this->cleopatraScrapeUpdated,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->cleopatraScrapeErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.scrape_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: CleopatraScraperService}
     */
    protected function fetchCleopatraScrapeItems(): array
    {
        $state = $this->cleopatraScrapeForm->getState();
        $collections = $state['collections'] ?? [];
        $limit = max(1, min(50, (int) ($state['max_per_collection'] ?? 5)));

        $scraper = app(CleopatraScraperService::class);
        $items = $scraper->scrapeCollections($collections, $limit);

        return [$items, $scraper];
    }

    /**
     * @return array<int, string>
     */
    protected function formatCleopatraScrapeErrors(CleopatraScraperService $scraper): array
    {
        return $scraper->getScrapeErrors()
            ->map(fn (array $e) => __('ecommerce.scrape_cleopatra_collection_error', [
                'handle' => $e['handle'],
                'message' => $e['message'],
            ]))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function syncScrapedImages(): void
    {
        $this->imagesSynced = null;
        $this->syncImagesErrors = [];

        $state = $this->syncImagesForm->getState();
        $limit = max(1, min(100, (int) ($state['limit'] ?? 20)));
        $sku = filled($state['sku'] ?? null) ? (string) $state['sku'] : null;

        try {
            $result = app(SyncScrapedProductImagesService::class)->sync($sku, $limit);
            $this->imagesSynced = $result['synced'];
            $this->syncImagesErrors = $result['errors'];

            Notification::make()
                ->title(__('ecommerce.sync_images_success', ['count' => $this->imagesSynced]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->syncImagesErrors = [$e->getMessage()];
            Notification::make()
                ->title(__('ecommerce.sync_images_failed_title'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function formatScrapeErrors(AriikaScraperService $scraper): array
    {
        return $scraper->getScrapeErrors()
            ->map(fn (array $e) => __('ecommerce.scrape_collection_error', [
                'handle' => $e['handle'],
                'message' => $e['message'],
            ]))
            ->all();
    }
}
