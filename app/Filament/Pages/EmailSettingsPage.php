<?php

namespace App\Filament\Pages;

use App\Notifications\TestEmailNotification;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Notification as Notify;
use Throwable;

class EmailSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static string $view = 'filament.pages.email-settings';

    protected static ?string $slug = 'email-settings';

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

    public function getTitle(): string
    {
        return __('ecommerce.email_settings');
    }

    public function getSubheading(): ?string
    {
        return __('ecommerce.brevo_settings_subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('settings.manage'));
    }

    public function mount(SettingsService $settings): void
    {
        $mail = $settings->getMailSettings();
        $notifications = $settings->getNotificationSettings();

        $orderEmails = collect($notifications['new_order_notification_emails'] ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values();

        $this->form->fill(array_merge($mail, $notifications, [
            'mail_host' => $mail['mail_host'] ?: 'smtp-relay.brevo.com',
            'mail_port' => (string) ($mail['mail_port'] ?: '587'),
            'mail_encryption' => $mail['mail_encryption'] ?: 'tls',
            'mail_from_name' => $mail['mail_from_name'] ?: config('app.name'),
            'mail_password' => '',
            'mail_password_is_set' => $settings->mailPasswordIsSet(),
            // أولوية: إيميلات تنبيه الطلبات → بريد الإشعارات العام → بريد حساب الأدمن
            'test_email_to' => $orderEmails->first()
                ?: ($notifications['admin_notification_email'] ?? null)
                ?: (auth()->user()?->email ?? ''),
        ]));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ecommerce.brevo_settings'))
                    ->description(__('ecommerce.brevo_settings_help'))
                    ->schema([
                        Forms\Components\Placeholder::make('brevo_guide')
                            ->label(__('ecommerce.brevo_setup_steps_title'))
                            ->content(__('ecommerce.brevo_setup_steps'))
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('notifications_enabled')
                            ->label(__('ecommerce.notifications_enabled'))
                            ->helperText(__('ecommerce.notifications_enabled_help'))
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('mail_password_is_set'),
                        Forms\Components\TextInput::make('mail_host')
                            ->label(__('ecommerce.mail_host'))
                            ->placeholder('smtp-relay.brevo.com')
                            ->default('smtp-relay.brevo.com')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('mail_port')
                            ->label(__('ecommerce.mail_port'))
                            ->options([
                                '587' => '587 (TLS — موصى به)',
                                '465' => '465 (SSL)',
                                '2525' => '2525 (بديل إذا حُظر 587)',
                            ])
                            ->default('587')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('mail_encryption')
                            ->label(__('ecommerce.mail_encryption'))
                            ->options([
                                'tls' => 'TLS / STARTTLS',
                                'ssl' => 'SSL',
                            ])
                            ->default('tls')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('mail_username')
                            ->label(__('ecommerce.mail_username'))
                            ->helperText(__('ecommerce.brevo_username_help'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_password')
                            ->label(__('ecommerce.mail_password'))
                            ->password()
                            ->revealable()
                            ->helperText(fn (Forms\Get $get): string => $get('mail_password_is_set')
                                ? __('ecommerce.brevo_password_help_set')
                                : __('ecommerce.brevo_password_help'))
                            ->required(fn (Forms\Get $get): bool => ! (bool) $get('mail_password_is_set'))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label(__('ecommerce.mail_from_address'))
                            ->helperText(__('ecommerce.brevo_from_address_help'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label(__('ecommerce.mail_from_name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('ecommerce.new_order_alerts'))
                    ->description(__('ecommerce.new_order_alerts_help'))
                    ->schema([
                        Forms\Components\Toggle::make('notify_order_placed_admin')
                            ->label(__('ecommerce.notify_order_placed_admin'))
                            ->helperText(__('ecommerce.notify_order_placed_admin_help'))
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('new_order_notification_emails')
                            ->label(__('ecommerce.new_order_notification_emails'))
                            ->placeholder('orders@example.com')
                            ->helperText(__('ecommerce.new_order_notification_emails_help'))
                            ->splitKeys(['Tab', ' ', ',', 'Enter'])
                            ->nestedRecursiveRules(['email'])
                            ->live()
                            ->afterStateUpdated(function (?array $state, Forms\Set $set, Forms\Get $get): void {
                                $first = collect($state ?? [])
                                    ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
                                    ->first();

                                if (! $first) {
                                    return;
                                }

                                $currentTest = trim((string) ($get('test_email_to') ?? ''));
                                $authEmail = (string) (auth()->user()?->email ?? '');

                                // حدّث إيميل الاختبار تلقائياً إن كان فارغاً أو ما زال بريد حساب الأدمن
                                if ($currentTest === '' || $currentTest === $authEmail) {
                                    $set('test_email_to', $first);
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('notify_order_placed_admin'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Forms\Components\Section::make(__('ecommerce.notification_events'))
                    ->description(__('ecommerce.notification_events_help'))
                    ->schema([
                        Forms\Components\Toggle::make('notify_order_placed_customer')
                            ->label(__('ecommerce.notify_order_placed_customer')),
                        Forms\Components\Toggle::make('notify_order_status_customer')
                            ->label(__('ecommerce.notify_order_status_customer')),
                        Forms\Components\Toggle::make('notify_refund_admin')
                            ->label(__('ecommerce.notify_refund_admin')),
                        Forms\Components\Toggle::make('notify_return_admin')
                            ->label(__('ecommerce.notify_return_admin')),
                        Forms\Components\TextInput::make('admin_notification_email')
                            ->label(__('ecommerce.admin_notification_email'))
                            ->email()
                            ->helperText(__('ecommerce.admin_notification_email_help'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('ecommerce.mail_test_section'))
                    ->description(__('ecommerce.mail_test_section_help'))
                    ->schema([
                        Forms\Components\TextInput::make('test_email_to')
                            ->label(__('ecommerce.mail_test_recipient'))
                            ->email()
                            ->required()
                            ->helperText(__('ecommerce.mail_test_recipient_help'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('send_test_email')
                                ->label(__('ecommerce.send_test_email'))
                                ->icon('heroicon-o-paper-airplane')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading(__('ecommerce.send_test_email'))
                                ->modalDescription(fn (): string => __('ecommerce.send_test_email_confirm_to', [
                                    'email' => $this->resolveTestRecipientEmail() ?: '—',
                                ]))
                                ->action(fn () => $this->sendTestEmail()),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test')
                ->label(__('ecommerce.send_test_email'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('ecommerce.send_test_email'))
                ->modalDescription(fn (): string => __('ecommerce.send_test_email_confirm_to', [
                    'email' => $this->resolveTestRecipientEmail() ?: '—',
                ]))
                ->action(fn () => $this->sendTestEmail()),
        ];
    }

    public function sendTestEmail(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        // احفظ إعدادات SMTP الظاهرة في النموذج قبل الاختبار (ما عدا كلمة المرور الفارغة)
        $this->persistMailSettingsFromForm($settings);
        $settings->applyMailConfig();

        if (! $settings->mailIsConfigured()) {
            Notification::make()
                ->title(__('ecommerce.mail_not_configured'))
                ->danger()
                ->send();

            return;
        }

        $email = $this->resolveTestRecipientEmail();

        if ($email === '') {
            Notification::make()
                ->title(__('ecommerce.mail_test_no_admin_email'))
                ->danger()
                ->send();

            return;
        }

        try {
            // إرسال فوري بدون طابور للتحقق من SMTP مباشرة
            Notify::route('mail', $email)->notifyNow(new TestEmailNotification);

            Notification::make()
                ->title(__('ecommerce.test_email_sent'))
                ->body(__('ecommerce.test_email_sent_to', ['email' => $email]))
                ->success()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title(__('ecommerce.test_email_failed'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * بريد الاختبار فقط من حقل «إيميل الاختبار» (منفصل عن تنبيهات الطلبات).
     */
    protected function resolveTestRecipientEmail(): string
    {
        $email = trim((string) ($this->data['test_email_to'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return '';
    }

    protected function persistMailSettingsFromForm(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->set('mail_mailer', 'smtp', 'mail');

        foreach ([
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_from_address',
            'mail_from_name',
        ] as $key) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                $settings->set($key, $data[$key], 'mail');
            }
        }

        if (filled($data['mail_password'] ?? null)) {
            $settings->set('mail_password', $data['mail_password'], 'mail');
            $this->data['mail_password'] = '';
            $this->data['mail_password_is_set'] = true;
        }
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->set('mail_mailer', 'smtp', 'mail');

        foreach ($data as $key => $value) {
            if (in_array($key, ['mail_password_is_set', 'test_email_to'], true)) {
                continue;
            }

            if ($key === 'mail_password' && blank($value)) {
                continue;
            }

            $group = str_starts_with((string) $key, 'notify_')
                || str_starts_with((string) $key, 'notifications_')
                || $key === 'admin_notification_email'
                || $key === 'new_order_notification_emails'
                ? 'notifications'
                : 'mail';

            if ($key === 'new_order_notification_emails') {
                $value = collect(is_array($value) ? $value : [])
                    ->map(fn ($email) => strtolower(trim((string) $email)))
                    ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                    ->unique()
                    ->values()
                    ->all();
            }

            $settings->set((string) $key, $value, $group);
        }

        $settings->applyMailConfig();

        $this->data['mail_password'] = '';
        $this->data['mail_password_is_set'] = $settings->mailPasswordIsSet();

        Notification::make()
            ->title(__('ecommerce.settings_saved'))
            ->success()
            ->send();
    }
}
