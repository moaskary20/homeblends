<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Shop\ContactPageService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ContactSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static string $view = 'filament.pages.contact-settings';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.contact_us');
    }

    public function getTitle(): string
    {
        return __('ecommerce.contact_page_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.contact_page_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(ContactPageService $contact): void
    {
        $content = $contact->getContent();
        $defaults = config('contact');

        $this->form->fill([
            'is_active' => (bool) ($content['is_active'] ?? $defaults['is_active'] ?? true),
            'page_title' => $content['page_title'] ?? $defaults['page_title'] ?? '',
            'seo_title' => $content['seo_title'] ?? $defaults['seo_title'] ?? '',
            'seo_description' => $content['seo_description'] ?? $defaults['seo_description'] ?? '',
            'hero' => array_merge($defaults['hero'] ?? [], $content['hero'] ?? []),
            'info' => array_merge($defaults['info'] ?? [], $content['info'] ?? []),
            'map' => array_merge($defaults['map'] ?? [], $content['map'] ?? []),
            'form' => array_merge($defaults['form'] ?? [], $content['form'] ?? []),
            'social' => collect($content['social'] ?? $defaults['social'] ?? [])->map(fn (array $item): array => [
                'label' => $item['label'] ?? '',
                'url' => $item['url'] ?? '',
                'icon' => $item['icon'] ?? 'facebook',
            ])->values()->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('contact_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make(__('ecommerce.contact_page_general'))
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('ecommerce.contact_page_active')),
                                Forms\Components\TextInput::make('page_title')
                                    ->label(__('ecommerce.contact_page_title'))
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
                                    ->label(__('ecommerce.contact_hero_title'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('hero.subtitle')
                                    ->label(__('ecommerce.contact_hero_subtitle'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.contact_page_info'))
                            ->schema([
                                Forms\Components\TextInput::make('info.address_label')
                                    ->label(__('ecommerce.contact_address_label'))
                                    ->maxLength(100),
                                Forms\Components\Textarea::make('info.address')
                                    ->label(__('ecommerce.contact_address'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('info.phone_label')
                                    ->label(__('ecommerce.contact_phone_label'))
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('info.phone')
                                    ->label(__('ecommerce.contact_phone'))
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('info.phone_link')
                                    ->label(__('ecommerce.contact_phone_link'))
                                    ->helperText(__('ecommerce.contact_phone_link_help'))
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('info.email_label')
                                    ->label(__('ecommerce.contact_email_label'))
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('info.email')
                                    ->label(__('ecommerce.contact_email'))
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('info.social_title')
                                    ->label(__('ecommerce.contact_social_title'))
                                    ->maxLength(100)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.contact_page_map'))
                            ->schema([
                                Forms\Components\Toggle::make('map.is_active')
                                    ->label(__('ecommerce.section_active')),
                                Forms\Components\Textarea::make('map.embed_url')
                                    ->label(__('ecommerce.contact_map_embed'))
                                    ->helperText(__('ecommerce.contact_map_embed_help'))
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('map.link_url')
                                    ->label(__('ecommerce.contact_map_link'))
                                    ->url()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.contact_page_form'))
                            ->schema([
                                Forms\Components\TextInput::make('form.title')
                                    ->label(__('ecommerce.contact_form_title'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('form.subtitle')
                                    ->label(__('ecommerce.contact_form_subtitle'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('form.recipient_email')
                                    ->label(__('ecommerce.contact_form_recipient'))
                                    ->email()
                                    ->helperText(__('ecommerce.contact_form_recipient_help'))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make(__('ecommerce.contact_page_social'))
                            ->schema([
                                Forms\Components\Repeater::make('social')
                                    ->label(__('ecommerce.contact_social_links'))
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label(__('ecommerce.contact_social_label'))
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\Select::make('icon')
                                            ->label(__('ecommerce.contact_strip_icon'))
                                            ->options([
                                                'facebook' => 'Facebook',
                                                'instagram' => 'Instagram',
                                                'tiktok' => 'TikTok',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('url')
                                            ->label(__('ecommerce.contact_social_url'))
                                            ->url()
                                            ->required()
                                            ->maxLength(500),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel(__('ecommerce.add_contact_social'))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('ecommerce.preview_contact_page'))
                ->icon('heroicon-o-eye')
                ->url(route('shop.contact'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(ContactPageService $contact): void
    {
        $data = $this->form->getState();

        Setting::setValue('contact_page_content', [
            'is_active' => (bool) ($data['is_active'] ?? true),
            'page_title' => $data['page_title'] ?? '',
            'seo_title' => $data['seo_title'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'hero' => [
                'eyebrow' => $data['hero']['eyebrow'] ?? '',
                'title' => $data['hero']['title'] ?? '',
                'subtitle' => $data['hero']['subtitle'] ?? '',
            ],
            'info' => [
                'address_label' => $data['info']['address_label'] ?? '',
                'address' => $data['info']['address'] ?? '',
                'phone_label' => $data['info']['phone_label'] ?? '',
                'phone' => $data['info']['phone'] ?? '',
                'phone_link' => $data['info']['phone_link'] ?? '',
                'email_label' => $data['info']['email_label'] ?? '',
                'email' => $data['info']['email'] ?? '',
                'social_title' => $data['info']['social_title'] ?? '',
            ],
            'map' => [
                'is_active' => (bool) ($data['map']['is_active'] ?? true),
                'embed_url' => $data['map']['embed_url'] ?? '',
                'link_url' => $data['map']['link_url'] ?? '',
            ],
            'form' => [
                'title' => $data['form']['title'] ?? '',
                'subtitle' => $data['form']['subtitle'] ?? '',
                'recipient_email' => $data['form']['recipient_email'] ?? '',
            ],
            'social' => collect($data['social'] ?? [])
                ->map(fn (array $item): array => [
                    'label' => $item['label'] ?? '',
                    'url' => $item['url'] ?? '',
                    'icon' => $item['icon'] ?? 'facebook',
                ])
                ->filter(fn (array $item): bool => filled($item['url']))
                ->values()
                ->all(),
        ], 'pages');

        $contact->clearCache();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.contact_page_settings_saved_body'))
            ->success()
            ->send();
    }
}
