<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    public const MAIL_KEYS = [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp-relay.brevo.com',
        'mail_port' => '587',
        'mail_encryption' => 'tls',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from_address' => '',
        'mail_from_name' => '',
    ];

    public const SEO_KEYS = [
        'seo_site_name' => null,
        'seo_title_suffix' => null,
        'seo_default_description' => null,
        'seo_default_og_image' => null,
        'seo_organization_logo' => null,
        'seo_organization_name' => null,
        'seo_twitter_site' => null,
        'seo_google_verification' => null,
        'seo_robots' => 'index, follow',
        'seo_home_title' => null,
        'seo_home_description' => null,
        'seo_products_description' => null,
        'seo_bundles_description' => null,
        'seo_robots_txt' => null,
    ];

    public const NOTIFICATION_KEYS = [
        'notifications_enabled' => true,
        'notify_order_placed_customer' => true,
        'notify_order_placed_admin' => true,
        'notify_order_status_customer' => true,
        'notify_refund_admin' => true,
        'notify_return_admin' => true,
        'admin_notification_email' => '',
        'new_order_notification_emails' => [],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === 'mail_password') {
            $encrypted = Setting::getValue('mail_password');

            return $encrypted ? Crypt::decryptString($encrypted) : $default;
        }

        return Setting::getValue($key, $default ?? $this->defaultFor($key));
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        if ($key === 'mail_password') {
            if (blank($value)) {
                return;
            }
            Setting::setValue('mail_password', Crypt::encryptString((string) $value), 'mail');

            return;
        }

        Setting::setValue($key, $value, $group);
    }

    public function getMailSettings(): array
    {
        $settings = [];
        foreach (self::MAIL_KEYS as $key => $default) {
            $settings[$key] = $this->get($key, $default);
        }

        return $settings;
    }

    public function getNotificationSettings(): array
    {
        $settings = [];
        foreach (self::NOTIFICATION_KEYS as $key => $default) {
            $settings[$key] = $this->get($key, $default);
        }

        $emails = $settings['new_order_notification_emails'] ?? [];
        if (! is_array($emails)) {
            $emails = filled($emails) ? [(string) $emails] : [];
        }

        if ($emails === [] && filled($settings['admin_notification_email'] ?? null)) {
            $emails = [(string) $settings['admin_notification_email']];
        }

        $settings['new_order_notification_emails'] = collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        return $settings;
    }

    public function getSeoSettings(): array
    {
        $settings = [];
        foreach (self::SEO_KEYS as $key => $default) {
            $settings[$key] = $this->get($key, $default);
        }

        if (blank($settings['seo_site_name'])) {
            $settings['seo_site_name'] = config('app.name');
        }

        if (blank($settings['seo_title_suffix'])) {
            $settings['seo_title_suffix'] = ' | '.config('app.name');
        }

        if (blank($settings['seo_robots'])) {
            $settings['seo_robots'] = 'index, follow';
        }

        return $settings;
    }

    public function applyMailConfig(): void
    {
        if (! $this->mailIsConfigured()) {
            return;
        }

        $port = (int) $this->get('mail_port', 587);
        $encryption = strtolower((string) $this->get('mail_encryption', 'tls'));
        $scheme = match (true) {
            $encryption === 'ssl', $port === 465 => 'smtps',
            default => null,
        };

        Config::set('mail.default', $this->get('mail_mailer', 'smtp') ?: 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.host', $this->get('mail_host'));
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $this->get('mail_username'));
        Config::set('mail.mailers.smtp.password', $this->get('mail_password'));
        Config::set('mail.mailers.smtp.encryption', in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null);
        Config::set('mail.from.address', $this->get('mail_from_address'));
        Config::set('mail.from.name', $this->get('mail_from_name', config('app.name')));
    }

    public function mailIsConfigured(): bool
    {
        return filled($this->get('mail_host'))
            && filled($this->get('mail_username'))
            && filled($this->get('mail_password'))
            && filled($this->get('mail_from_address'));
    }

    public function mailPasswordIsSet(): bool
    {
        return filled(Setting::getValue('mail_password'));
    }

    public function notificationsEnabled(): bool
    {
        return (bool) $this->get('notifications_enabled', true);
    }

    public function isEnabled(string $eventKey): bool
    {
        if (! $this->notificationsEnabled() || ! $this->mailIsConfigured()) {
            return false;
        }

        return (bool) $this->get($eventKey, true);
    }

    public function adminRecipients(): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->where('is_admin', true)
                    ->orWhereHas('roles', fn ($q) => $q->where('name', 'admin'));
            })
            ->get()
            ->unique('email')
            ->values();
    }

    /**
     * @return list<string>
     */
    public function newOrderNotificationEmails(): array
    {
        $emails = collect($this->get('new_order_notification_emails', []))
            ->whenEmpty(function () {
                $legacy = $this->get('admin_notification_email');

                return filled($legacy) ? collect([$legacy]) : collect();
            })
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    /**
     * @return list<string>
     */
    public function adminAlertEmails(): array
    {
        $emails = collect($this->get('admin_notification_email'))
            ->merge($this->get('new_order_notification_emails', []))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        return $emails;
    }

    protected function defaultFor(string $key): mixed
    {
        return self::MAIL_KEYS[$key]
            ?? self::NOTIFICATION_KEYS[$key]
            ?? self::SEO_KEYS[$key]
            ?? null;
    }
}
