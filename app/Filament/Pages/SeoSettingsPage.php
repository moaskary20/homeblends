<?php

namespace App\Filament\Pages;

use App\Services\Seo\SeoService;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SeoSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string $view = 'filament.pages.seo-settings';

    protected static ?int $navigationSort = 85;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.seo_optimization');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.seo_optimization');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage') || $user->can('seo.manage'));
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->getSeoSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ecommerce.seo_global'))
                    ->schema([
                        Forms\Components\TextInput::make('seo_site_name')
                            ->label(__('ecommerce.seo_site_name'))
                            ->default(config('app.name')),
                        Forms\Components\TextInput::make('seo_title_suffix')
                            ->label(__('ecommerce.seo_title_suffix'))
                            ->helperText(__('ecommerce.seo_title_suffix_hint'))
                            ->default(' | '.config('app.name')),
                        Forms\Components\Textarea::make('seo_default_description')
                            ->label(__('ecommerce.meta_description'))
                            ->rows(3),
                        Forms\Components\FileUpload::make('seo_default_og_image')
                            ->label(__('ecommerce.seo_default_og_image'))
                            ->image()
                            ->directory('seo'),
                        Forms\Components\FileUpload::make('seo_organization_logo')
                            ->label(__('ecommerce.seo_organization_logo'))
                            ->image()
                            ->directory('seo'),
                        Forms\Components\TextInput::make('seo_organization_name')
                            ->label(__('ecommerce.seo_organization_name')),
                        Forms\Components\TextInput::make('seo_twitter_site')
                            ->label(__('ecommerce.seo_twitter_site'))
                            ->placeholder('@homeblend'),
                        Forms\Components\TextInput::make('seo_google_verification')
                            ->label(__('ecommerce.seo_google_verification')),
                        Forms\Components\TextInput::make('seo_robots')
                            ->label(__('ecommerce.seo_robots'))
                            ->default('index, follow'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('ecommerce.seo_pages'))
                    ->schema([
                        Forms\Components\TextInput::make('seo_home_title')
                            ->label(__('ecommerce.seo_home_title')),
                        Forms\Components\Textarea::make('seo_home_description')
                            ->label(__('ecommerce.seo_home_description'))
                            ->rows(2),
                        Forms\Components\Textarea::make('seo_products_description')
                            ->label(__('ecommerce.seo_products_description'))
                            ->rows(2),
                        Forms\Components\Textarea::make('seo_bundles_description')
                            ->label(__('ecommerce.seo_bundles_description'))
                            ->rows(2),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('ecommerce.seo_robots_file'))
                    ->schema([
                        Forms\Components\Textarea::make('seo_robots_txt')
                            ->label(__('ecommerce.seo_robots_txt'))
                            ->rows(8)
                            ->helperText(__('ecommerce.seo_robots_txt_hint')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_sitemap')
                ->label(__('ecommerce.seo_regenerate_sitemap'))
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    Artisan::call('sitemap:generate');

                    Notification::make()
                        ->title(__('ecommerce.seo_sitemap_regenerated'))
                        ->success()
                        ->send();
                }),
            Action::make('view_sitemap')
                ->label(__('ecommerce.seo_view_sitemap'))
                ->icon('heroicon-o-link')
                ->url(route('sitemap'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(SettingsService $settings): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $settings->set($key, $value, 'seo');
        }

        Cache::forget('sitemap.xml');

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->success()
            ->send();
    }
}
