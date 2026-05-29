<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Shop\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WhatsAppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static string $view = 'filament.pages.whatsapp-settings';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.site_pages_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.whatsapp_management');
    }

    public function getTitle(): string
    {
        return __('ecommerce.whatsapp_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.whatsapp_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(WhatsAppService $whatsapp): void
    {
        $settings = $whatsapp->getSettings();
        $defaults = config('whatsapp', []);

        $this->form->fill([
            'is_active' => (bool) ($settings['is_active'] ?? $defaults['is_active'] ?? true),
            'phone' => (string) ($settings['phone'] ?? $defaults['phone'] ?? ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ecommerce.whatsapp_float_button'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('ecommerce.whatsapp_active'))
                            ->helperText(__('ecommerce.whatsapp_active_help')),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('ecommerce.whatsapp_phone'))
                            ->helperText(__('ecommerce.whatsapp_phone_help'))
                            ->placeholder('+20 12 22878031')
                            ->required()
                            ->maxLength(30),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(WhatsAppService $whatsapp): void
    {
        $data = $this->form->getState();
        $phone = trim((string) ($data['phone'] ?? ''));

        if (WhatsAppService::normalizePhone($phone) === '') {
            Notification::make()
                ->title(__('ecommerce.whatsapp_phone_invalid'))
                ->danger()
                ->send();

            return;
        }

        Setting::setValue('whatsapp_settings', [
            'is_active' => (bool) ($data['is_active'] ?? true),
            'phone' => $phone,
        ], 'pages');

        $whatsapp->clearCache();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->body(__('ecommerce.whatsapp_settings_saved_body'))
            ->success()
            ->send();
    }
}
