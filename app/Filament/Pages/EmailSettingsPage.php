<?php

namespace App\Filament\Pages;

use App\Notifications\TestEmailNotification;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Notification as Notify;

class EmailSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static string $view = 'filament.pages.email-settings';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.email_settings');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill(array_merge(
            $settings->getMailSettings(),
            $settings->getNotificationSettings(),
            ['mail_password' => '']
        ));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ecommerce.brevo_settings'))
                    ->description(__('ecommerce.brevo_settings_help'))
                    ->schema([
                        Forms\Components\Toggle::make('notifications_enabled')
                            ->label(__('ecommerce.notifications_enabled')),
                        Forms\Components\TextInput::make('mail_host')
                            ->label(__('ecommerce.mail_host'))
                            ->default('smtp-relay.brevo.com')
                            ->required(),
                        Forms\Components\TextInput::make('mail_port')
                            ->label(__('ecommerce.mail_port'))
                            ->numeric()
                            ->default(587)
                            ->required(),
                        Forms\Components\Select::make('mail_encryption')
                            ->label(__('ecommerce.mail_encryption'))
                            ->options(['tls' => 'TLS', 'ssl' => 'SSL'])
                            ->default('tls'),
                        Forms\Components\TextInput::make('mail_username')
                            ->label(__('ecommerce.mail_username'))
                            ->helperText(__('ecommerce.brevo_username_help'))
                            ->required(),
                        Forms\Components\TextInput::make('mail_password')
                            ->label(__('ecommerce.mail_password'))
                            ->password()
                            ->revealable()
                            ->helperText(__('ecommerce.brevo_password_help')),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label(__('ecommerce.mail_from_address'))
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label(__('ecommerce.mail_from_name'))
                            ->required(),
                        Forms\Components\TextInput::make('admin_notification_email')
                            ->label(__('ecommerce.admin_notification_email'))
                            ->email()
                            ->helperText(__('ecommerce.admin_notification_email_help')),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('ecommerce.notification_events'))
                    ->schema([
                        Forms\Components\Toggle::make('notify_order_placed_customer')
                            ->label(__('ecommerce.notify_order_placed_customer')),
                        Forms\Components\Toggle::make('notify_order_placed_admin')
                            ->label(__('ecommerce.notify_order_placed_admin')),
                        Forms\Components\Toggle::make('notify_order_status_customer')
                            ->label(__('ecommerce.notify_order_status_customer')),
                        Forms\Components\Toggle::make('notify_refund_admin')
                            ->label(__('ecommerce.notify_refund_admin')),
                        Forms\Components\Toggle::make('notify_return_admin')
                            ->label(__('ecommerce.notify_return_admin')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test')
                ->label(__('ecommerce.send_test_email'))
                ->icon('heroicon-o-paper-airplane')
                ->action(function (SettingsService $settings): void {
                    $settings->applyMailConfig();

                    if (! $settings->mailIsConfigured()) {
                        Notification::make()
                            ->title(__('ecommerce.mail_not_configured'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $email = auth()->user()->email;
                    Notify::route('mail', $email)->notify(new TestEmailNotification);

                    Notification::make()
                        ->title(__('ecommerce.test_email_sent'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($key === 'mail_password' && blank($value)) {
                continue;
            }
            $group = str_starts_with($key, 'notify_') || str_starts_with($key, 'notifications_') || $key === 'admin_notification_email'
                ? 'notifications'
                : 'mail';
            $settings->set($key, $value, $group);
        }

        $settings->applyMailConfig();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->success()
            ->send();
    }
}
