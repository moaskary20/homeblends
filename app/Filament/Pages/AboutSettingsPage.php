<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Shop\AboutPageService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AboutSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static string $view = 'filament.pages.about-settings';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.about_company');
    }

    public function getTitle(): string
    {
        return __('ecommerce.about_page_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.about_page_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(AboutPageService $about): void
    {
        $content = $about->getContent();
        $defaults = config('about');

        $intro = array_merge($defaults['intro'] ?? [], $content['intro'] ?? []);
        $ceo = array_merge($defaults['ceo'] ?? [], $content['ceo'] ?? []);
        $services = array_merge($defaults['services'] ?? [], $content['services'] ?? []);

        $this->form->fill([
            'is_active' => (bool) ($content['is_active'] ?? $defaults['is_active'] ?? true),
            'page_title' => $content['page_title'] ?? $defaults['page_title'] ?? '',
            'seo_title' => $content['seo_title'] ?? $defaults['seo_title'] ?? '',
            'seo_description' => $content['seo_description'] ?? $defaults['seo_description'] ?? '',
            'intro' => [
                'is_active' => (bool) ($intro['is_active'] ?? true),
                'eyebrow' => $intro['eyebrow'] ?? '',
                'title' => $intro['title'] ?? '',
                'paragraphs' => collect($intro['paragraphs'] ?? [])
                    ->map(fn (string $text) => ['text' => $text])
                    ->values()
                    ->all(),
                'image' => $intro['image'] ?? null,
            ],
            'ceo' => [
                'is_active' => (bool) ($ceo['is_active'] ?? true),
                'section_title' => $ceo['section_title'] ?? '',
                'quote' => $ceo['quote'] ?? '',
                'name' => $ceo['name'] ?? '',
                'title' => $ceo['title'] ?? '',
                'image' => $ceo['image'] ?? null,
            ],
            'services' => [
                'is_active' => (bool) ($services['is_active'] ?? true),
                'section_title' => $services['section_title'] ?? '',
                'section_subtitle' => $services['section_subtitle'] ?? '',
                'items' => collect($services['items'] ?? [])
                    ->map(fn (array $item): array => [
                        'title' => $item['title'] ?? '',
                        'title_en' => $item['title_en'] ?? '',
                        'image' => $item['image'] ?? null,
                        'bullets' => collect($item['bullets'] ?? [])
                            ->map(fn (string $bullet) => ['text' => $bullet])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('about_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('ecommerce.about_page_general'))
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('ecommerce.about_page_active'))
                                    ->default(true),
                                Forms\Components\TextInput::make('page_title')
                                    ->label(__('ecommerce.about_page_title'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('seo_title')
                                    ->label(__('ecommerce.seo_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('seo_description')
                                    ->label(__('ecommerce.seo_description'))
                                    ->rows(3)
                                    ->maxLength(500),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.about_page_intro'))
                            ->schema([
                                Forms\Components\Toggle::make('intro.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('intro.eyebrow')
                                    ->label(__('ecommerce.about_intro_eyebrow'))
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('intro.title')
                                    ->label(__('ecommerce.about_intro_title'))
                                    ->maxLength(255),
                                Forms\Components\Repeater::make('intro.paragraphs')
                                    ->label(__('ecommerce.about_intro_paragraphs'))
                                    ->schema([
                                        Forms\Components\Textarea::make('text')
                                            ->label(__('ecommerce.paragraph'))
                                            ->rows(3)
                                            ->required(),
                                    ])
                                    ->addActionLabel(__('ecommerce.add_paragraph'))
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('intro.image')
                                    ->label(__('ecommerce.about_intro_image'))
                                    ->image()
                                    ->directory('about/intro')
                                    ->imagePreviewHeight('180')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.about_page_ceo'))
                            ->schema([
                                Forms\Components\Toggle::make('ceo.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('ceo.section_title')
                                    ->label(__('ecommerce.about_ceo_section_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('ceo.quote')
                                    ->label(__('ecommerce.about_ceo_quote'))
                                    ->rows(8)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('ceo.name')
                                    ->label(__('ecommerce.about_ceo_name'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('ceo.title')
                                    ->label(__('ecommerce.about_ceo_role'))
                                    ->maxLength(255),
                                Forms\Components\FileUpload::make('ceo.image')
                                    ->label(__('ecommerce.about_ceo_image'))
                                    ->image()
                                    ->directory('about/ceo')
                                    ->imagePreviewHeight('180')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.about_page_services'))
                            ->schema([
                                Forms\Components\Toggle::make('services.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('services.section_title')
                                    ->label(__('ecommerce.about_services_title'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('services.section_subtitle')
                                    ->label(__('ecommerce.about_services_subtitle'))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('services.items')
                                    ->label(__('ecommerce.about_services_items'))
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label(__('ecommerce.service_title'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('title_en')
                                            ->label(__('ecommerce.service_title_en'))
                                            ->maxLength(255),
                                        Forms\Components\FileUpload::make('image')
                                            ->label(__('ecommerce.service_image'))
                                            ->image()
                                            ->directory('about/services')
                                            ->imagePreviewHeight('120'),
                                        Forms\Components\Repeater::make('bullets')
                                            ->label(__('ecommerce.service_bullets'))
                                            ->schema([
                                                Forms\Components\TextInput::make('text')
                                                    ->label(__('ecommerce.bullet_point'))
                                                    ->required(),
                                            ])
                                            ->addActionLabel(__('ecommerce.add_bullet'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel(__('ecommerce.add_service'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('ecommerce.preview_about_page'))
                ->icon('heroicon-o-eye')
                ->url(route('shop.about'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(AboutPageService $about): void
    {
        $data = $this->form->getState();

        $introParagraphs = collect($data['intro']['paragraphs'] ?? [])
            ->pluck('text')
            ->filter(fn (?string $text) => filled($text))
            ->values()
            ->all();

        $serviceItems = collect($data['services']['items'] ?? [])
            ->map(fn (array $item): array => [
                'title' => $item['title'] ?? '',
                'title_en' => $item['title_en'] ?? '',
                'image' => $this->normalizeUploadedPath($item['image'] ?? null),
                'bullets' => collect($item['bullets'] ?? [])
                    ->pluck('text')
                    ->filter(fn (?string $text) => filled($text))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $item): bool => filled($item['title']) && $item['bullets'] !== [])
            ->values()
            ->all();

        Setting::setValue('about_page_content', [
            'is_active' => (bool) ($data['is_active'] ?? true),
            'page_title' => $data['page_title'] ?? '',
            'seo_title' => $data['seo_title'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'intro' => [
                'is_active' => (bool) ($data['intro']['is_active'] ?? true),
                'eyebrow' => $data['intro']['eyebrow'] ?? '',
                'title' => $data['intro']['title'] ?? '',
                'paragraphs' => $introParagraphs,
                'image' => $this->normalizeUploadedPath($data['intro']['image'] ?? null) ?: 'images/banner01.png',
            ],
            'ceo' => [
                'is_active' => (bool) ($data['ceo']['is_active'] ?? true),
                'section_title' => $data['ceo']['section_title'] ?? '',
                'quote' => $data['ceo']['quote'] ?? '',
                'name' => $data['ceo']['name'] ?? '',
                'title' => $data['ceo']['title'] ?? '',
                'image' => $this->normalizeUploadedPath($data['ceo']['image'] ?? null) ?: 'images/ceo.png',
            ],
            'services' => [
                'is_active' => (bool) ($data['services']['is_active'] ?? true),
                'section_title' => $data['services']['section_title'] ?? '',
                'section_subtitle' => $data['services']['section_subtitle'] ?? '',
                'items' => $serviceItems,
            ],
        ], 'pages');

        $about->clearCache();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.about_page_settings_saved_body'))
            ->success()
            ->send();
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
