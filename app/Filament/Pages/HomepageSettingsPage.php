<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Shop\HomepageService;
use Illuminate\Support\Facades\Cache;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class HomepageSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static string $view = 'filament.pages.homepage-settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.homepage');
    }

    public function getTitle(): string
    {
        return __('ecommerce.homepage_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.homepage_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(HomepageService $homepage): void
    {
        $content = $homepage->getContent();

        $popular = $content['popular_collections'] ?? config('homepage.popular_collections', []);
        $designBanner = $content['design_banner'] ?? config('homepage.design_banner', []);
        $catalogShowcase = $content['catalog_showcase'] ?? config('homepage.catalog_showcase', []);
        $catalogShowcaseFurniture = $content['catalog_showcase_furniture'] ?? config('homepage.catalog_showcase_furniture', []);
        $promoBanner = $content['promo_banner'] ?? config('homepage.promo_banner', []);
        $customerReviews = $content['customer_reviews'] ?? config('homepage.customer_reviews', []);
        $contactStrip = $content['contact_strip'] ?? config('homepage.contact_strip', []);
        $comfortSpotlight = $content['comfort_spotlight'] ?? config('homepage.comfort_spotlight', []);

        $this->form->fill([
            'news_ticker' => collect($content['news_ticker'] ?? [])
                ->map(fn (string $text) => ['text' => $text])
                ->values()
                ->all(),
            'hero_slides' => $content['hero_slides'] ?? [],
            'partners' => $content['partners'] ?? [],
            'popular_collections_section_title' => $popular['section_title'] ?? __('ecommerce.popular_collections'),
            'popular_collections' => $this->expandCollectionsForForm($popular['items'] ?? []),
            'design_banner' => [
                'is_active' => (bool) ($designBanner['is_active'] ?? true),
                'image' => $designBanner['image'] ?? 'images/banner01.png',
                'eyebrow' => $designBanner['eyebrow'] ?? '',
                'title' => $designBanner['title'] ?? '',
                'subtitle' => $designBanner['subtitle'] ?? '',
                'cta' => $designBanner['cta'] ?? '',
                'url' => $designBanner['url'] ?? '#contact',
            ],
            'catalog_showcase' => $this->catalogShowcaseFormState($catalogShowcase),
            'catalog_showcase_furniture' => $this->catalogShowcaseFormState($catalogShowcaseFurniture),
            'promo_banner' => [
                'is_active' => (bool) ($promoBanner['is_active'] ?? true),
                'image' => $promoBanner['image'] ?? 'images/s1.png',
                'cta' => $promoBanner['cta'] ?? __('Shop Now'),
                'url' => $promoBanner['url'] ?? '/products',
            ],
            'customer_reviews' => [
                'is_active' => (bool) ($customerReviews['is_active'] ?? true),
                'section_title' => $customerReviews['section_title'] ?? __('ecommerce.customer_reviews'),
                'auto_limit' => (int) ($customerReviews['auto_limit'] ?? 10),
                'items' => collect($customerReviews['items'] ?? [])->map(fn (array $item): array => [
                    'image' => $item['image'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'customer_name' => $item['customer_name'] ?? '',
                    'rating' => (int) ($item['rating'] ?? 5),
                    'comment' => $item['comment'] ?? '',
                    'is_verified' => (bool) ($item['is_verified'] ?? false),
                ])->all(),
            ],
            'contact_strip' => [
                'is_active' => (bool) ($contactStrip['is_active'] ?? true),
                'items' => collect($contactStrip['items'] ?? [])->map(fn (array $item): array => [
                    'icon' => $item['icon'] ?? 'chat',
                    'title' => $item['title'] ?? '',
                    'text' => $item['text'] ?? '',
                    'url' => $item['url'] ?? '',
                ])->all(),
            ],
            'comfort_spotlight' => [
                'is_active' => (bool) ($comfortSpotlight['is_active'] ?? true),
                'eyebrow' => $comfortSpotlight['eyebrow'] ?? '',
                'title' => $comfortSpotlight['title'] ?? '',
                'description' => $comfortSpotlight['description'] ?? '',
                'cta' => $comfortSpotlight['cta'] ?? __('ecommerce.shop_all'),
                'url' => $comfortSpotlight['url'] ?? '/products',
                'image' => $comfortSpotlight['image'] ?? null,
                'hero_product_id' => $comfortSpotlight['hero_product_id'] ?? null,
                'product_ids' => $comfortSpotlight['product_ids'] ?? [],
                'links' => collect($comfortSpotlight['links'] ?? [])->map(fn (array $link): array => [
                    'name' => $link['name'] ?? '',
                    'url' => $link['url'] ?? '',
                    'category_id' => $link['category_id'] ?? null,
                ])->all(),
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('homepage_tabs')
                    ->tabs([
                        $this->newsTickerTab(),
                        $this->heroSliderTab(),
                        $this->popularCollectionsTab(),
                        $this->designBannerTab(),
                        $this->catalogShowcaseTab(),
                        $this->promoBannerTab(),
                        $this->catalogShowcaseFurnitureTab(),
                        $this->comfortSpotlightTab(),
                        $this->customerReviewsTab(),
                        $this->contactStripTab(),
                        $this->partnersTab(),
                    ])
                    ->activeTab(1)
                    ->persistTabInQueryString('homepage-tab')
                    ->contained(false),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('ecommerce.preview_homepage'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => route('shop.home'))
                ->openUrlInNewTab(),
        ];
    }

    protected function newsTickerTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.homepage_news_ticker'))
            ->id('news')
            ->icon('heroicon-o-megaphone')
            ->badge(fn (Get $get): string => (string) count($get('news_ticker') ?? []))
            ->schema([
                Forms\Components\Placeholder::make('news_hint')
                    ->label('')
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">'
                        .e(__('ecommerce.homepage_news_ticker_help'))
                        .'</p>'
                    )),
                Forms\Components\Repeater::make('news_ticker')
                    ->label(__('ecommerce.news_ticker_items'))
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label(__('ecommerce.news_ticker_text'))
                            ->required()
                            ->maxLength(500)
                            ->placeholder(__('ecommerce.news_ticker_placeholder'))
                            ->prefixIcon('heroicon-m-bars-3-bottom-left')
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => filled($state['text'] ?? null)
                        ? \Illuminate\Support\Str::limit($state['text'], 60)
                        : __('ecommerce.new_news_item'))
                    ->addActionLabel(__('ecommerce.add_news_item'))
                    ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation()),
            ]);
    }

    protected function heroSliderTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.homepage_hero_slider'))
            ->id('hero')
            ->icon('heroicon-o-photo')
            ->badge(fn (Get $get): string => (string) count($get('hero_slides') ?? []))
            ->schema([
                Forms\Components\Placeholder::make('hero_hint')
                    ->label('')
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">'
                        .e(__('ecommerce.homepage_hero_slider_help'))
                        .'</p>'
                    )),
                Forms\Components\Repeater::make('hero_slides')
                    ->label(__('ecommerce.hero_slides'))
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label(__('ecommerce.hero_slide_title'))
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-m-sparkles'),
                                        Forms\Components\TextInput::make('subtitle')
                                            ->label(__('ecommerce.hero_slide_subtitle'))
                                            ->maxLength(500)
                                            ->prefixIcon('heroicon-m-chat-bubble-bottom-center-text'),
                                        Forms\Components\TextInput::make('cta')
                                            ->label(__('ecommerce.hero_slide_cta'))
                                            ->maxLength(100)
                                            ->prefixIcon('heroicon-m-cursor-arrow-rays'),
                                        Forms\Components\TextInput::make('url')
                                            ->label(__('ecommerce.hero_slide_url'))
                                            ->maxLength(500)
                                            ->prefixIcon('heroicon-m-link')
                                            ->helperText(__('ecommerce.hero_slide_url_help')),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\FileUpload::make('image')
                                            ->label(__('ecommerce.hero_slide_image'))
                                            ->image()
                                            ->directory('homepage/hero')
                                            ->imagePreviewHeight('200')
                                            ->panelAspectRatio('16:9')
                                            ->panelLayout('integrated')
                                            ->required()
                                            ->live(),
                                        Forms\Components\ViewField::make('slide_preview')
                                            ->view('filament.homepage.slide-preview')
                                            ->dehydrated(false)
                                            ->viewData(fn (Get $get): array => [
                                                'imageUrl' => HomepageService::slideImageUrl(
                                                    $this->normalizeUploadedPath($get('image'))
                                                ),
                                            ]),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(2),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => filled($state['title'] ?? null)
                        ? $state['title']
                        : __('ecommerce.new_hero_slide'))
                    ->addActionLabel(__('ecommerce.add_hero_slide'))
                    ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation()),
            ]);
    }

    protected function popularCollectionsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.popular_collections'))
            ->id('collections')
            ->icon('heroicon-o-shopping-bag')
            ->badge(fn (Get $get): string => (string) count($get('popular_collections') ?? []))
            ->schema([
                Forms\Components\TextInput::make('popular_collections_section_title')
                    ->label(__('ecommerce.popular_collections_title'))
                    ->required()
                    ->maxLength(120)
                    ->prefixIcon('heroicon-m-tag'),
                Forms\Components\Placeholder::make('collections_hint')
                    ->label('')
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">'
                        .e(__('ecommerce.popular_collections_products_help'))
                        .'</p>'
                    )),
                Forms\Components\Repeater::make('popular_collections')
                    ->label(__('ecommerce.popular_collections_items'))
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('ecommerce.collection_main_product'))
                            ->options(fn (): array => $this->productSelectOptions())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Forms\Set $set, Get $get): void {
                                if (! $state || filled($get('title'))) {
                                    return;
                                }
                                $product = Product::find($state);
                                if ($product) {
                                    $set('title', $product->name);
                                }
                            }),
                        Forms\Components\TextInput::make('title')
                            ->label(__('ecommerce.collection_title'))
                            ->maxLength(255)
                            ->helperText(__('ecommerce.collection_title_optional')),
                        Forms\Components\Select::make('product_ids')
                            ->label(__('ecommerce.collection_thumb_products'))
                            ->options(fn (): array => $this->productSelectOptions())
                            ->searchable()
                            ->multiple()
                            ->maxItems(3)
                            ->helperText(__('ecommerce.collection_thumb_products_help')),
                        Forms\Components\TextInput::make('items_count')
                            ->label(__('ecommerce.collection_items_count_label'))
                            ->numeric()
                            ->minValue(0)
                            ->helperText(__('ecommerce.collection_items_count_optional')),
                        Forms\Components\TextInput::make('url')
                            ->label(__('ecommerce.collection_shop_url'))
                            ->maxLength(500)
                            ->helperText(__('ecommerce.collection_shop_url_optional')),
                    ])
                    ->defaultItems(0)
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $this->collectionRepeaterLabel($state))
                    ->addActionLabel(__('ecommerce.add_collection'))
                    ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation()),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected function productSelectOptions(): array
    {
        return Product::query()
            ->published()
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => $product->name.' (#'.$product->id.')',
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function collectionRepeaterLabel(array $state): ?string
    {
        if (filled($state['title'] ?? null)) {
            return $state['title'];
        }

        if (filled($state['product_id'] ?? null)) {
            return Product::query()->whereKey($state['product_id'])->value('name');
        }

        return __('ecommerce.new_collection');
    }

    protected function designBannerTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.design_banner'))
            ->id('design-banner')
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Forms\Components\Section::make(__('ecommerce.design_banner'))
                    ->description(__('ecommerce.design_banner_help'))
                    ->schema([
                        Forms\Components\Toggle::make('design_banner.is_active')
                            ->label(__('ecommerce.is_active'))
                            ->default(true),
                        Forms\Components\FileUpload::make('design_banner.image')
                            ->label(__('ecommerce.design_banner_image'))
                            ->image()
                            ->directory('homepage/banners')
                            ->helperText(__('ecommerce.design_banner_image_help')),
                        Forms\Components\TextInput::make('design_banner.eyebrow')
                            ->label(__('ecommerce.design_banner_eyebrow'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('design_banner.title')
                            ->label(__('ecommerce.design_banner_title'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('design_banner.subtitle')
                            ->label(__('ecommerce.design_banner_subtitle'))
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\TextInput::make('design_banner.cta')
                            ->label(__('ecommerce.design_banner_cta'))
                            ->maxLength(120),
                        Forms\Components\TextInput::make('design_banner.url')
                            ->label(__('ecommerce.design_banner_url'))
                            ->maxLength(500)
                            ->helperText(__('ecommerce.hero_slide_url_help')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function catalogShowcaseTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.catalog_showcase'))
            ->id('catalog-showcase')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Forms\Components\Section::make(__('ecommerce.catalog_showcase'))
                    ->description(__('ecommerce.catalog_showcase_help'))
                    ->schema($this->catalogShowcaseFormSchema('catalog_showcase'))
                    ->columns(2),
            ]);
    }

    protected function catalogShowcaseFurnitureTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.catalog_showcase_furniture'))
            ->id('catalog-showcase-furniture')
            ->icon('heroicon-o-home-modern')
            ->schema([
                Forms\Components\Section::make(__('ecommerce.catalog_showcase_furniture'))
                    ->description(__('ecommerce.catalog_showcase_furniture_help'))
                    ->schema($this->catalogShowcaseFormSchema('catalog_showcase_furniture'))
                    ->columns(2),
            ]);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function catalogShowcaseFormSchema(string $prefix): array
    {
        return [
            Forms\Components\Toggle::make("{$prefix}.is_active")
                ->label(__('ecommerce.is_active'))
                ->default(true),
            Forms\Components\TextInput::make("{$prefix}.title")
                ->label(__('ecommerce.catalog_showcase_title'))
                ->helperText(__('ecommerce.catalog_showcase_title_help'))
                ->maxLength(255),
            Forms\Components\Select::make("{$prefix}.category_id")
                ->label(__('ecommerce.catalog_showcase_parent_category'))
                ->options(fn (): array => Category::query()
                    ->active()
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set("{$prefix}.subcategory_ids", [])),
            Forms\Components\Select::make("{$prefix}.subcategory_ids")
                ->label(__('ecommerce.catalog_showcase_subcategories'))
                ->helperText(__('ecommerce.catalog_showcase_subcategories_help'))
                ->multiple()
                ->options(function (Get $get) use ($prefix): array {
                    $parentId = $get("{$prefix}.category_id");
                    if (blank($parentId)) {
                        return [];
                    }

                    return Category::query()
                        ->active()
                        ->where('parent_id', (int) $parentId)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->visible(fn (Get $get) => filled($get("{$prefix}.category_id"))),
            Forms\Components\TextInput::make("{$prefix}.products_limit")
                ->label(__('ecommerce.catalog_showcase_products_limit'))
                ->numeric()
                ->minValue(4)
                ->maxValue(16)
                ->default(8)
                ->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function catalogShowcaseFormState(array $data): array
    {
        return [
            'is_active' => (bool) ($data['is_active'] ?? true),
            'title' => $data['title'] ?? '',
            'category_id' => $data['category_id'] ?? null,
            'subcategory_ids' => $data['subcategory_ids'] ?? [],
            'products_limit' => (int) ($data['products_limit'] ?? 8),
        ];
    }

    /**
     * @param  array<string, mixed>  $showcase
     * @return array<string, mixed>
     */
    protected function normalizeCatalogShowcaseForSave(array $showcase): array
    {
        $subcategoryIds = collect($showcase['subcategory_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        return [
            'is_active' => (bool) ($showcase['is_active'] ?? true),
            'title' => $showcase['title'] ?? '',
            'category_id' => filled($showcase['category_id'] ?? null) ? (int) $showcase['category_id'] : null,
            'subcategory_ids' => $subcategoryIds,
            'products_limit' => max(4, min(16, (int) ($showcase['products_limit'] ?? 8))),
        ];
    }

    protected function promoBannerTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.promo_banner'))
            ->id('promo-banner')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\Section::make(__('ecommerce.promo_banner'))
                    ->description(__('ecommerce.promo_banner_help'))
                    ->schema([
                        Forms\Components\Toggle::make('promo_banner.is_active')
                            ->label(__('ecommerce.is_active'))
                            ->default(true),
                        Forms\Components\FileUpload::make('promo_banner.image')
                            ->label(__('ecommerce.promo_banner_image'))
                            ->image()
                            ->directory('homepage/promo'),
                        Forms\Components\TextInput::make('promo_banner.cta')
                            ->label(__('ecommerce.promo_banner_cta'))
                            ->default(__('Shop Now'))
                            ->maxLength(120),
                        Forms\Components\TextInput::make('promo_banner.url')
                            ->label(__('ecommerce.promo_banner_url'))
                            ->maxLength(500)
                            ->helperText(__('ecommerce.hero_slide_url_help')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function comfortSpotlightTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.comfort_spotlight'))
            ->id('comfort-spotlight')
            ->icon('heroicon-o-sparkles')
            ->schema([
                Forms\Components\Section::make(__('ecommerce.comfort_spotlight'))
                    ->description(__('ecommerce.comfort_spotlight_help'))
                    ->schema([
                        Forms\Components\Toggle::make('comfort_spotlight.is_active')
                            ->label(__('ecommerce.is_active'))
                            ->default(true),
                        Forms\Components\TextInput::make('comfort_spotlight.eyebrow')
                            ->label(__('ecommerce.comfort_spotlight_eyebrow'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('comfort_spotlight.title')
                            ->label(__('ecommerce.comfort_spotlight_title'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('comfort_spotlight.description')
                            ->label(__('ecommerce.comfort_spotlight_description'))
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\TextInput::make('comfort_spotlight.cta')
                            ->label(__('ecommerce.comfort_spotlight_cta'))
                            ->maxLength(120),
                        Forms\Components\TextInput::make('comfort_spotlight.url')
                            ->label(__('ecommerce.comfort_spotlight_url'))
                            ->maxLength(500),
                        Forms\Components\FileUpload::make('comfort_spotlight.image')
                            ->label(__('ecommerce.comfort_spotlight_image'))
                            ->image()
                            ->directory('homepage/comfort'),
                        Forms\Components\Select::make('comfort_spotlight.hero_product_id')
                            ->label(__('ecommerce.comfort_spotlight_hero_product'))
                            ->options(fn (): array => $this->productSelectOptions())
                            ->searchable(),
                        Forms\Components\Select::make('comfort_spotlight.product_ids')
                            ->label(__('ecommerce.comfort_spotlight_thumb_products'))
                            ->options(fn (): array => $this->productSelectOptions())
                            ->multiple()
                            ->maxItems(4)
                            ->searchable(),
                        Forms\Components\Repeater::make('comfort_spotlight.links')
                            ->label(__('ecommerce.comfort_spotlight_links'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('ecommerce.comfort_spotlight_link_name'))
                                    ->required()
                                    ->maxLength(120),
                                Forms\Components\Select::make('category_id')
                                    ->label(__('ecommerce.comfort_spotlight_link_category'))
                                    ->options(fn (): array => Category::query()
                                        ->active()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable(),
                                Forms\Components\TextInput::make('url')
                                    ->label(__('ecommerce.comfort_spotlight_link_url'))
                                    ->maxLength(500),
                            ])
                            ->columns(3)
                            ->defaultItems(4)
                            ->maxItems(8)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel(__('ecommerce.add_comfort_spotlight_link')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function customerReviewsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.customer_reviews'))
            ->id('customer-reviews')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->badge(fn (Get $get): string => (string) count($get('customer_reviews.items') ?? []))
            ->schema([
                Forms\Components\Section::make(__('ecommerce.customer_reviews'))
                    ->description(__('ecommerce.customer_reviews_help'))
                    ->schema([
                        Forms\Components\Toggle::make('customer_reviews.is_active')
                            ->label(__('ecommerce.is_active'))
                            ->default(true),
                        Forms\Components\TextInput::make('customer_reviews.section_title')
                            ->label(__('ecommerce.customer_reviews_title'))
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('customer_reviews.auto_limit')
                            ->label(__('ecommerce.customer_reviews_auto_limit'))
                            ->numeric()
                            ->minValue(4)
                            ->maxValue(16)
                            ->default(10),
                        Forms\Components\Repeater::make('customer_reviews.items')
                            ->label(__('ecommerce.customer_reviews_items'))
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label(__('ecommerce.customer_review_image'))
                                    ->image()
                                    ->directory('homepage/reviews')
                                    ->helperText(__('ecommerce.customer_review_image_help')),
                                Forms\Components\Select::make('product_id')
                                    ->label(__('ecommerce.customer_review_product'))
                                    ->options(fn (): array => $this->productSelectOptions())
                                    ->searchable(),
                                Forms\Components\TextInput::make('customer_name')
                                    ->label(__('ecommerce.customer_review_name'))
                                    ->required()
                                    ->maxLength(120),
                                Forms\Components\Select::make('rating')
                                    ->label(__('ecommerce.customer_review_rating'))
                                    ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1'])
                                    ->default(5)
                                    ->required(),
                                Forms\Components\Textarea::make('comment')
                                    ->label(__('ecommerce.customer_review_comment'))
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(500),
                                Forms\Components\Toggle::make('is_verified')
                                    ->label(__('ecommerce.customer_review_verified'))
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => filled($state['customer_name'] ?? null)
                                ? $state['customer_name']
                                : __('ecommerce.add_customer_review'))
                            ->addActionLabel(__('ecommerce.add_customer_review')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function contactStripTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.contact_strip'))
            ->id('contact-strip')
            ->icon('heroicon-o-phone')
            ->badge(fn (Get $get): string => (string) count($get('contact_strip.items') ?? []))
            ->schema([
                Forms\Components\Section::make(__('ecommerce.contact_strip'))
                    ->description(__('ecommerce.contact_strip_help'))
                    ->schema([
                        Forms\Components\Toggle::make('contact_strip.is_active')
                            ->label(__('ecommerce.is_active'))
                            ->default(true),
                        Forms\Components\Repeater::make('contact_strip.items')
                            ->label(__('ecommerce.contact_strip_item'))
                            ->schema([
                                Forms\Components\Select::make('icon')
                                    ->label(__('ecommerce.contact_strip_icon'))
                                    ->options([
                                        'location' => __('ecommerce.icon_location'),
                                        'email' => __('ecommerce.icon_email'),
                                        'phone' => __('ecommerce.icon_phone'),
                                        'chat' => __('ecommerce.icon_chat'),
                                    ])
                                    ->default('chat')
                                    ->required(),
                                Forms\Components\TextInput::make('title')
                                    ->label(__('ecommerce.contact_strip_title'))
                                    ->required()
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('text')
                                    ->label(__('ecommerce.contact_strip_text'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('url')
                                    ->label(__('ecommerce.contact_strip_url'))
                                    ->maxLength(500),
                            ])
                            ->columns(2)
                            ->defaultItems(4)
                            ->minItems(1)
                            ->maxItems(4)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel(__('ecommerce.add_contact_strip_item')),
                    ]),
            ]);
    }

    protected function partnersTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('ecommerce.homepage_partners'))
            ->id('partners')
            ->icon('heroicon-o-building-storefront')
            ->badge(fn (Get $get): string => (string) count($get('partners') ?? []))
            ->schema([
                Forms\Components\Placeholder::make('partners_hint')
                    ->label('')
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">'
                        .e(__('ecommerce.homepage_partners_help'))
                        .'</p>'
                    )),
                Forms\Components\Repeater::make('partners')
                    ->label(__('ecommerce.partners'))
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('ecommerce.partner_name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->prefixIcon('heroicon-m-building-storefront'),
                                        Forms\Components\FileUpload::make('logo')
                                            ->label(__('ecommerce.partner_logo'))
                                            ->image()
                                            ->directory('homepage/partners')
                                            ->imagePreviewHeight('120')
                                            ->panelLayout('compact')
                                            ->live(),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                                Forms\Components\ViewField::make('partner_preview')
                                    ->view('filament.homepage.partner-preview')
                                    ->dehydrated(false)
                                    ->viewData(fn (Get $get): array => [
                                        'name' => $get('name'),
                                        'logoUrl' => HomepageService::partnerLogoUrl(
                                            $this->normalizeUploadedPath($get('logo'))
                                        ),
                                    ])
                                    ->columnSpan(['lg' => 1]),
                            ])
                            ->columns(2),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null)
                        ? $state['name']
                        : __('ecommerce.new_partner'))
                    ->addActionLabel(__('ecommerce.add_partner'))
                    ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation()),
            ]);
    }

    public function getNewsCount(): int
    {
        return count($this->data['news_ticker'] ?? []);
    }

    public function getHeroCount(): int
    {
        return count($this->data['hero_slides'] ?? []);
    }

    public function getPartnersCount(): int
    {
        return count($this->data['partners'] ?? []);
    }

    public function getCollectionsCount(): int
    {
        return count($this->data['popular_collections'] ?? []);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $newsTicker = collect($data['news_ticker'] ?? [])
            ->pluck('text')
            ->filter(fn (?string $text) => filled($text))
            ->values()
            ->all();

        $heroSlides = collect($data['hero_slides'] ?? [])
            ->map(fn (array $slide) => [
                'title' => $slide['title'] ?? '',
                'subtitle' => $slide['subtitle'] ?? '',
                'cta' => $slide['cta'] ?? '',
                'url' => $slide['url'] ?? '',
                'image' => $this->normalizeUploadedPath($slide['image'] ?? null),
            ])
            ->filter(fn (array $slide) => filled($slide['title']) && filled($slide['image']))
            ->values()
            ->all();

        $partners = collect($data['partners'] ?? [])
            ->map(fn (array $partner) => [
                'name' => $partner['name'] ?? '',
                'logo' => $this->normalizeUploadedPath($partner['logo'] ?? null),
            ])
            ->filter(fn (array $partner) => filled($partner['name']))
            ->values()
            ->all();

        $collections = collect($data['popular_collections'] ?? [])
            ->map(fn (array $item) => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_ids' => collect($item['product_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->take(3)
                    ->values()
                    ->all(),
                'title' => $item['title'] ?? '',
                'items_count' => filled($item['items_count'] ?? null)
                    ? (int) $item['items_count']
                    : null,
                'url' => $item['url'] ?? '',
            ])
            ->filter(fn (array $item) => $item['product_id'] > 0)
            ->values()
            ->all();

        Setting::setValue('homepage_news_ticker', $newsTicker, 'homepage');
        Setting::setValue('homepage_hero_slides', $heroSlides, 'homepage');
        Setting::setValue('homepage_partners', $partners, 'homepage');
        Setting::setValue('homepage_popular_collections', [
            'section_title' => $data['popular_collections_section_title'] ?? __('ecommerce.popular_collections'),
            'items' => $collections,
        ], 'homepage');

        $banner = $data['design_banner'] ?? [];
        Setting::setValue('homepage_design_banner', [
            'is_active' => (bool) ($banner['is_active'] ?? true),
            'image' => $this->normalizeUploadedPath($banner['image'] ?? null) ?: 'images/banner01.png',
            'eyebrow' => $banner['eyebrow'] ?? '',
            'title' => $banner['title'] ?? '',
            'subtitle' => $banner['subtitle'] ?? '',
            'cta' => $banner['cta'] ?? '',
            'url' => $banner['url'] ?? '#contact',
        ], 'homepage');

        Setting::setValue(
            'homepage_catalog_showcase',
            $this->normalizeCatalogShowcaseForSave($data['catalog_showcase'] ?? []),
            'homepage'
        );
        Setting::setValue(
            'homepage_catalog_showcase_furniture',
            $this->normalizeCatalogShowcaseForSave($data['catalog_showcase_furniture'] ?? []),
            'homepage'
        );

        $promo = $data['promo_banner'] ?? [];
        Setting::setValue('homepage_promo_banner', [
            'is_active' => (bool) ($promo['is_active'] ?? true),
            'image' => $this->normalizeUploadedPath($promo['image'] ?? null) ?: 'images/s1.png',
            'cta' => $promo['cta'] ?? __('Shop Now'),
            'url' => $promo['url'] ?? '/products',
        ], 'homepage');

        $reviews = $data['customer_reviews'] ?? [];
        $reviewItems = collect($reviews['items'] ?? [])
            ->map(fn (array $item) => [
                'image' => $this->normalizeUploadedPath($item['image'] ?? null),
                'product_id' => filled($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
                'customer_name' => $item['customer_name'] ?? '',
                'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
                'comment' => $item['comment'] ?? '',
                'is_verified' => (bool) ($item['is_verified'] ?? false),
            ])
            ->filter(fn (array $item) => filled($item['customer_name']) && filled($item['comment']))
            ->values()
            ->all();
        Setting::setValue('homepage_customer_reviews', [
            'is_active' => (bool) ($reviews['is_active'] ?? true),
            'section_title' => $reviews['section_title'] ?? __('ecommerce.customer_reviews'),
            'auto_limit' => max(4, min(16, (int) ($reviews['auto_limit'] ?? 10))),
            'items' => $reviewItems,
        ], 'homepage');

        $comfort = $data['comfort_spotlight'] ?? [];
        Setting::setValue('homepage_comfort_spotlight', [
            'is_active' => (bool) ($comfort['is_active'] ?? true),
            'eyebrow' => $comfort['eyebrow'] ?? '',
            'title' => $comfort['title'] ?? '',
            'description' => $comfort['description'] ?? '',
            'cta' => $comfort['cta'] ?? __('ecommerce.shop_all'),
            'url' => $comfort['url'] ?? '/products',
            'image' => $this->normalizeUploadedPath($comfort['image'] ?? null),
            'hero_product_id' => filled($comfort['hero_product_id'] ?? null) ? (int) $comfort['hero_product_id'] : null,
            'product_ids' => collect($comfort['product_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->take(4)
                ->values()
                ->all(),
            'links' => collect($comfort['links'] ?? [])
                ->map(fn (array $link) => [
                    'name' => $link['name'] ?? '',
                    'url' => $link['url'] ?? '',
                    'category_id' => filled($link['category_id'] ?? null) ? (int) $link['category_id'] : null,
                ])
                ->filter(fn (array $link) => filled($link['name']))
                ->values()
                ->all(),
        ], 'homepage');

        $strip = $data['contact_strip'] ?? [];
        Setting::setValue('homepage_contact_strip', [
            'is_active' => (bool) ($strip['is_active'] ?? true),
            'items' => collect($strip['items'] ?? [])
                ->map(fn (array $item) => [
                    'icon' => $item['icon'] ?? 'chat',
                    'title' => $item['title'] ?? '',
                    'text' => $item['text'] ?? '',
                    'url' => $item['url'] ?? '',
                ])
                ->filter(fn (array $item) => filled($item['title']) && filled($item['text']))
                ->values()
                ->all(),
        ], 'homepage');

        Cache::forget('shop.popular_collections');
        Cache::forget('shop.customer_reviews');
        Cache::forget('shop.comfort_spotlight');
        Cache::forget('shop.catalog_showcase');
        Cache::forget('shop.catalog_showcase_furniture');
        Cache::forget('shop.featured');
        Cache::forget('api.v1.home');

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.homepage_settings_saved_body'))
            ->success()
            ->send();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function expandCollectionsForForm(array $items): array
    {
        return collect($items)->map(function (array $item): array {
            $productIds = collect($item['product_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            return [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_ids' => $productIds,
                'title' => $item['title'] ?? '',
                'items_count' => $item['items_count'] ?? null,
                'url' => $item['url'] ?? '',
            ];
        })->all();
    }

    protected function normalizeUploadedPath(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }
}
