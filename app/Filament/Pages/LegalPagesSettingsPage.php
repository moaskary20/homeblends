<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Shop\LegalPageService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LegalPagesSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static string $view = 'filament.pages.legal-pages-settings';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.legal_pages_management');
    }

    public function getTitle(): string
    {
        return __('ecommerce.legal_pages_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.legal_pages_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(LegalPageService $legal): void
    {
        $pages = $legal->allPagesForAdmin();
        $strip = $legal->homepageStrip();

        $this->form->fill([
            'homepage_strip' => [
                'is_active' => (bool) ($strip['is_active'] ?? true),
                'eyebrow' => $strip['eyebrow'] ?? '',
                'title' => $strip['title'] ?? '',
                'subtitle' => $strip['subtitle'] ?? '',
            ],
            'privacy' => $this->pageForForm($pages['privacy']),
            'terms' => $this->pageForForm($pages['terms']),
            'returns' => $this->pageForForm($pages['returns']),
            'shipping' => $this->pageForForm($pages['shipping']),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('legal_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('ecommerce.legal_homepage_strip'))
                            ->schema($this->homepageStripSchema()),
                        ...collect(LegalPageService::PAGE_KEYS)->map(
                            fn (string $key): Forms\Components\Tabs\Tab => Forms\Components\Tabs\Tab::make($this->pageTabLabel($key))
                                ->schema($this->pageSchema($key))
                        )->all(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    protected function homepageStripSchema(): array
    {
        return [
            Forms\Components\Toggle::make('homepage_strip.is_active')
                ->label(__('ecommerce.section_active')),
            Forms\Components\TextInput::make('homepage_strip.eyebrow')
                ->label(__('ecommerce.about_intro_eyebrow'))
                ->maxLength(100),
            Forms\Components\TextInput::make('homepage_strip.title')
                ->label(__('ecommerce.legal_strip_title'))
                ->maxLength(255),
            Forms\Components\Textarea::make('homepage_strip.subtitle')
                ->label(__('ecommerce.legal_strip_subtitle'))
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    protected function pageSchema(string $key): array
    {
        return [
            Forms\Components\Toggle::make("{$key}.is_active")
                ->label(__('ecommerce.legal_page_active')),
            Forms\Components\TextInput::make("{$key}.page_title")
                ->label(__('ecommerce.legal_page_title'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make("{$key}.seo_title")
                ->label(__('ecommerce.seo_title'))
                ->maxLength(255),
            Forms\Components\Textarea::make("{$key}.seo_description")
                ->label(__('ecommerce.seo_description'))
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),
            Forms\Components\Repeater::make("{$key}.sections")
                ->label(__('ecommerce.legal_page_sections'))
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label(__('ecommerce.legal_section_title'))
                        ->maxLength(255),
                    Forms\Components\Repeater::make('paragraphs')
                        ->label(__('ecommerce.legal_section_paragraphs'))
                        ->schema([
                            Forms\Components\Textarea::make('text')
                                ->label(__('ecommerce.paragraph'))
                                ->rows(3)
                                ->required(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel(__('ecommerce.add_paragraph'))
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('bullets')
                        ->label(__('ecommerce.legal_section_bullets'))
                        ->schema([
                            Forms\Components\TextInput::make('text')
                                ->label(__('ecommerce.bullet_point'))
                                ->required()
                                ->maxLength(500),
                        ])
                        ->addActionLabel(__('ecommerce.add_bullet'))
                        ->columnSpanFull(),
                ])
                ->addActionLabel(__('ecommerce.add_legal_section'))
                ->columnSpanFull()
                ->collapsible(),
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    protected function pageForForm(array $page): array
    {
        $page['sections'] = collect($page['sections'] ?? [])
            ->map(fn (array $section): array => [
                'title' => $section['title'] ?? '',
                'paragraphs' => collect($section['paragraphs'] ?? [])
                    ->map(fn ($text): array => ['text' => is_array($text) ? ($text['text'] ?? '') : $text])
                    ->values()
                    ->all(),
                'bullets' => collect($section['bullets'] ?? [])
                    ->map(fn ($text): array => ['text' => is_array($text) ? ($text['text'] ?? '') : $text])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return $page;
    }

    protected function pageTabLabel(string $key): string
    {
        return match ($key) {
            'privacy' => __('ecommerce.legal_privacy'),
            'terms' => __('ecommerce.legal_terms'),
            'returns' => __('ecommerce.legal_returns'),
            'shipping' => __('ecommerce.legal_shipping'),
            default => $key,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_privacy')
                ->label(__('ecommerce.preview_legal_privacy'))
                ->icon('heroicon-o-eye')
                ->url(route('shop.legal.privacy'))
                ->openUrlInNewTab(),
            Action::make('preview_terms')
                ->label(__('ecommerce.preview_legal_terms'))
                ->icon('heroicon-o-eye')
                ->url(route('shop.legal.terms'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(LegalPageService $legal): void
    {
        $data = $this->form->getState();
        $strip = $data['homepage_strip'] ?? [];

        Setting::setValue('legal_pages_homepage_strip', [
            'is_active' => (bool) ($strip['is_active'] ?? true),
            'eyebrow' => $strip['eyebrow'] ?? '',
            'title' => $strip['title'] ?? '',
            'subtitle' => $strip['subtitle'] ?? '',
        ], 'pages');

        $pages = [];
        foreach (LegalPageService::PAGE_KEYS as $key) {
            $page = $data[$key] ?? [];
            $sections = collect($page['sections'] ?? [])
                ->map(function (array $section): array {
                    $paragraphs = collect($section['paragraphs'] ?? [])
                        ->map(fn (array $row): string => trim((string) ($row['text'] ?? '')))
                        ->filter(fn (string $text): bool => filled($text))
                        ->values()
                        ->all();

                    $bullets = collect($section['bullets'] ?? [])
                        ->map(fn (array $row): string => trim((string) ($row['text'] ?? '')))
                        ->filter(fn (string $text): bool => filled($text))
                        ->values()
                        ->all();

                    return [
                        'title' => trim((string) ($section['title'] ?? '')),
                        'paragraphs' => $paragraphs,
                        'bullets' => $bullets,
                    ];
                })
                ->filter(fn (array $section): bool => filled($section['title']) || $section['paragraphs'] !== [] || $section['bullets'] !== [])
                ->values()
                ->all();

            $pages[$key] = [
                'is_active' => (bool) ($page['is_active'] ?? true),
                'page_title' => $page['page_title'] ?? '',
                'seo_title' => $page['seo_title'] ?? '',
                'seo_description' => $page['seo_description'] ?? '',
                'sections' => $sections,
            ];
        }

        Setting::setValue('legal_pages_content', $pages, 'pages');

        $legal->clearCache();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.legal_pages_settings_saved_body'))
            ->success()
            ->send();
    }
}
