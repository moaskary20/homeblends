<?php

namespace Tests\Feature;

use App\Filament\Pages\EmailSettingsPage;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class EmailSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_brevo_smtp_settings_and_apply_mail_config(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin);

        Livewire::test(EmailSettingsPage::class)
            ->assertFormSet([
                'mail_host' => 'smtp-relay.brevo.com',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
            ])
            ->fillForm([
                'notifications_enabled' => true,
                'mail_host' => 'smtp-relay.brevo.com',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
                'mail_username' => 'shop@homeblendstore.com',
                'mail_password' => 'xsmtpsib-test-key',
                'mail_from_address' => 'noreply@homeblendstore.com',
                'mail_from_name' => 'هوم بلند',
                'admin_notification_email' => 'admin@homeblendstore.com',
                'new_order_notification_emails' => ['orders@homeblendstore.com'],
                'notify_order_placed_customer' => true,
                'notify_order_placed_admin' => true,
                'notify_order_status_customer' => true,
                'notify_refund_admin' => true,
                'notify_return_admin' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(SettingsService::class);

        $this->assertTrue($settings->mailIsConfigured());
        $this->assertSame('smtp-relay.brevo.com', $settings->get('mail_host'));
        $this->assertSame('shop@homeblendstore.com', $settings->get('mail_username'));
        $this->assertSame('xsmtpsib-test-key', $settings->get('mail_password'));
        $this->assertSame('noreply@homeblendstore.com', $settings->get('mail_from_address'));
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp-relay.brevo.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, (int) config('mail.mailers.smtp.port'));
        $this->assertNull(config('mail.mailers.smtp.scheme'));
    }

    public function test_apply_mail_config_uses_smtps_scheme_for_ssl_port(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('mail_host', 'smtp-relay.brevo.com', 'mail');
        $settings->set('mail_port', '465', 'mail');
        $settings->set('mail_encryption', 'ssl', 'mail');
        $settings->set('mail_username', 'shop@example.com', 'mail');
        $settings->set('mail_password', 'secret-key', 'mail');
        $settings->set('mail_from_address', 'shop@example.com', 'mail');
        $settings->set('mail_from_name', 'HomeBlend', 'mail');

        $settings->applyMailConfig();

        $this->assertSame('smtps', Config::get('mail.mailers.smtp.scheme'));
        $this->assertSame(465, (int) Config::get('mail.mailers.smtp.port'));
    }

    public function test_admin_can_send_test_email_action(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin-test@homeblendstore.com',
        ]);

        $settings = app(SettingsService::class);
        $settings->set('mail_host', 'smtp-relay.brevo.com', 'mail');
        $settings->set('mail_port', '587', 'mail');
        $settings->set('mail_encryption', 'tls', 'mail');
        $settings->set('mail_username', 'shop@homeblendstore.com', 'mail');
        $settings->set('mail_password', 'xsmtpsib-test-key', 'mail');
        $settings->set('mail_from_address', 'noreply@homeblendstore.com', 'mail');
        $settings->set('mail_from_name', 'هوم بلند', 'mail');
        $settings->set('new_order_notification_emails', ['orders@homeblendstore.com'], 'notifications');

        $this->actingAs($admin);

        Livewire::test(EmailSettingsPage::class)
            ->assertFormSet([
                'test_email_to' => 'orders@homeblendstore.com',
            ])
            ->fillForm([
                'test_email_to' => 'probe@homeblendstore.com',
                'mail_host' => 'smtp-relay.brevo.com',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
                'mail_username' => 'shop@homeblendstore.com',
                'mail_from_address' => 'noreply@homeblendstore.com',
                'mail_from_name' => 'هوم بلند',
            ])
            ->call('sendTestEmail')
            ->assertNotified();

        \Illuminate\Support\Facades\Notification::assertSentOnDemand(
            \App\Notifications\TestEmailNotification::class,
            function ($notification, $channels, $notifiable): bool {
                return ($notifiable->routes['mail'] ?? null) === 'probe@homeblendstore.com';
            }
        );
    }
}
