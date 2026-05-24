<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Shop\DesignTeamPageService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DesignTeamSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $view = 'filament.pages.design-team-settings';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.design_team');
    }

    public function getTitle(): string
    {
        return __('ecommerce.design_team_page_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.design_team_page_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(DesignTeamPageService $designTeam): void
    {
        $content = $designTeam->getContent();
        $defaults = config('design-team');

        $this->form->fill([
            'is_active' => (bool) ($content['is_active'] ?? $defaults['is_active'] ?? true),
            'page_title' => $content['page_title'] ?? $defaults['page_title'] ?? '',
            'seo_title' => $content['seo_title'] ?? $defaults['seo_title'] ?? '',
            'seo_description' => $content['seo_description'] ?? $defaults['seo_description'] ?? '',
            'hero' => array_merge($defaults['hero'] ?? [], $content['hero'] ?? []),
            'how_it_works' => array_merge($defaults['how_it_works'] ?? [], $content['how_it_works'] ?? []),
            'meeting_ways' => array_merge($defaults['meeting_ways'] ?? [], $content['meeting_ways'] ?? []),
            'services' => array_merge($defaults['services'] ?? [], $content['services'] ?? []),
            'faq' => array_merge($defaults['faq'] ?? [], $content['faq'] ?? []),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('design_team_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('ecommerce.design_team_page_general'))
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('ecommerce.design_team_page_active')),
                                Forms\Components\TextInput::make('page_title')
                                    ->label(__('ecommerce.design_team_page_title'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('seo_title')
                                    ->label(__('ecommerce.seo_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('seo_description')
                                    ->label(__('ecommerce.seo_description'))
                                    ->rows(3)
                                    ->maxLength(500),
                                Forms\Components\TextInput::make('hero.eyebrow')
                                    ->label(__('ecommerce.about_intro_eyebrow'))
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('hero.title')
                                    ->label(__('ecommerce.design_team_hero_title'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('hero.subtitle')
                                    ->label(__('ecommerce.design_team_hero_subtitle'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('hero.cta')
                                    ->label(__('ecommerce.design_banner_cta'))
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('hero.cta_url')
                                    ->label(__('ecommerce.design_banner_url'))
                                    ->maxLength(500),
                                Forms\Components\TextInput::make('hero.image')
                                    ->label(__('ecommerce.design_team_hero_image'))
                                    ->helperText(__('ecommerce.design_team_hero_image_help'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.design_team_how_it_works'))
                            ->schema([
                                Forms\Components\Toggle::make('how_it_works.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('how_it_works.title')
                                    ->label(__('ecommerce.section_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('how_it_works.subtitle')
                                    ->label(__('ecommerce.section_subtitle'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('how_it_works.steps')
                                    ->label(__('ecommerce.design_team_steps'))
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label(__('ecommerce.step_title'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label(__('ecommerce.step_description'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('image')
                                            ->label(__('ecommerce.step_image'))
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel(__('ecommerce.add_step'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.design_team_meeting_ways'))
                            ->schema([
                                Forms\Components\Toggle::make('meeting_ways.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('meeting_ways.title')
                                    ->label(__('ecommerce.section_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('meeting_ways.subtitle')
                                    ->label(__('ecommerce.section_subtitle'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('meeting_ways.items')
                                    ->label(__('ecommerce.design_team_meeting_options'))
                                    ->schema([
                                        Forms\Components\TextInput::make('badge')
                                            ->label(__('ecommerce.meeting_badge'))
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('type')
                                            ->label(__('ecommerce.meeting_type'))
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('description')
                                            ->label(__('ecommerce.meeting_description'))
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('cta')
                                            ->label(__('ecommerce.design_banner_cta'))
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('url')
                                            ->label(__('ecommerce.design_banner_url'))
                                            ->maxLength(500),
                                    ])
                                    ->addActionLabel(__('ecommerce.add_meeting_option'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.design_team_services'))
                            ->schema([
                                Forms\Components\Toggle::make('services.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('services.title')
                                    ->label(__('ecommerce.section_title'))
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('services.subtitle')
                                    ->label(__('ecommerce.section_subtitle'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('services.items')
                                    ->label(__('ecommerce.design_team_service_items'))
                                    ->schema([
                                        Forms\Components\Select::make('icon')
                                            ->label(__('ecommerce.service_icon'))
                                            ->options([
                                                'clock' => __('ecommerce.icon_clock'),
                                                'ruler' => __('ecommerce.icon_ruler'),
                                                'moodboard' => __('ecommerce.icon_moodboard'),
                                                'cube' => __('ecommerce.icon_cube'),
                                                'sofa' => __('ecommerce.icon_sofa'),
                                                'voucher' => __('ecommerce.icon_voucher'),
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('title')
                                            ->label(__('ecommerce.service_title'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label(__('ecommerce.service_description'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('note')
                                            ->label(__('ecommerce.service_note'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel(__('ecommerce.add_service_item'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.design_team_faq'))
                            ->schema([
                                Forms\Components\Toggle::make('faq.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\TextInput::make('faq.title')
                                    ->label(__('ecommerce.section_title'))
                                    ->maxLength(255),
                                Forms\Components\Repeater::make('faq.items')
                                    ->label(__('ecommerce.faq_items'))
                                    ->schema([
                                        Forms\Components\TextInput::make('question')
                                            ->label(__('ecommerce.faq_question'))
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\Textarea::make('answer')
                                            ->label(__('ecommerce.faq_answer'))
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->addActionLabel(__('ecommerce.add_faq_item'))
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
                ->label(__('ecommerce.preview_design_team_page'))
                ->icon('heroicon-o-eye')
                ->url(route('shop.design-team'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(DesignTeamPageService $designTeam): void
    {
        $data = $this->form->getState();

        $steps = collect($data['how_it_works']['steps'] ?? [])
            ->map(fn (array $step): array => [
                'title' => $step['title'] ?? '',
                'description' => $step['description'] ?? '',
                'image' => $step['image'] ?? '',
            ])
            ->filter(fn (array $step): bool => filled($step['title']))
            ->values()
            ->all();

        $meetingItems = collect($data['meeting_ways']['items'] ?? [])
            ->map(fn (array $item): array => [
                'badge' => $item['badge'] ?? '',
                'type' => $item['type'] ?? '',
                'description' => $item['description'] ?? '',
                'cta' => $item['cta'] ?? '',
                'url' => $item['url'] ?? '',
            ])
            ->filter(fn (array $item): bool => filled($item['badge']) || filled($item['type']))
            ->values()
            ->all();

        $serviceItems = collect($data['services']['items'] ?? [])
            ->map(fn (array $item): array => [
                'icon' => $item['icon'] ?? 'clock',
                'title' => $item['title'] ?? '',
                'description' => $item['description'] ?? '',
                'note' => $item['note'] ?? '',
            ])
            ->filter(fn (array $item): bool => filled($item['title']))
            ->values()
            ->all();

        $faqItems = collect($data['faq']['items'] ?? [])
            ->map(fn (array $item): array => [
                'question' => $item['question'] ?? '',
                'answer' => $item['answer'] ?? '',
            ])
            ->filter(fn (array $item): bool => filled($item['question']) && filled($item['answer']))
            ->values()
            ->all();

        Setting::setValue('design_team_page_content', [
            'is_active' => (bool) ($data['is_active'] ?? true),
            'page_title' => $data['page_title'] ?? '',
            'seo_title' => $data['seo_title'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'hero' => [
                'eyebrow' => $data['hero']['eyebrow'] ?? '',
                'title' => $data['hero']['title'] ?? '',
                'subtitle' => $data['hero']['subtitle'] ?? '',
                'cta' => $data['hero']['cta'] ?? '',
                'cta_url' => $data['hero']['cta_url'] ?? '/contact',
                'image' => $data['hero']['image'] ?? '',
            ],
            'how_it_works' => [
                'is_active' => (bool) ($data['how_it_works']['is_active'] ?? true),
                'title' => $data['how_it_works']['title'] ?? '',
                'subtitle' => $data['how_it_works']['subtitle'] ?? '',
                'steps' => $steps,
            ],
            'meeting_ways' => [
                'is_active' => (bool) ($data['meeting_ways']['is_active'] ?? true),
                'title' => $data['meeting_ways']['title'] ?? '',
                'subtitle' => $data['meeting_ways']['subtitle'] ?? '',
                'items' => $meetingItems,
            ],
            'services' => [
                'is_active' => (bool) ($data['services']['is_active'] ?? true),
                'title' => $data['services']['title'] ?? '',
                'subtitle' => $data['services']['subtitle'] ?? '',
                'items' => $serviceItems,
            ],
            'faq' => [
                'is_active' => (bool) ($data['faq']['is_active'] ?? true),
                'title' => $data['faq']['title'] ?? '',
                'items' => $faqItems,
            ],
        ], 'pages');

        $designTeam->clearCache();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.design_team_page_settings_saved_body'))
            ->success()
            ->send();
    }
}
