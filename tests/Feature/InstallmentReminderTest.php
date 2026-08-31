<?php

namespace Tests\Feature;

use App\Enums\InstallmentContractStatus;
use App\Enums\InstallmentPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Installments\InstallmentDueNotification;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstallmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_overdue_and_notifies_when_mail_configured(): void
    {
        Notification::fake();

        $settings = app(SettingsService::class);
        $settings->set('notifications_enabled', true, 'notifications');
        $settings->set('notify_installment_due_customer', true, 'notifications');
        $settings->set('mail_host', 'smtp.example.com', 'mail');
        $settings->set('mail_username', 'shop@example.com', 'mail');
        $settings->set('mail_password', 'secret', 'mail');
        $settings->set('mail_from_address', 'shop@example.com', 'mail');
        $settings->applyMailConfig();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'HB-TESTINST',
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 600,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 600,
            'currency' => 'EGP',
            'payment_method' => 'local_provider',
            'payment_status' => 'partial',
        ]);

        $contract = InstallmentContract::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'months' => 3,
            'total_amount' => 600,
            'monthly_amount' => 200,
            'status' => InstallmentContractStatus::Active,
        ]);

        InstallmentPayment::create([
            'installment_contract_id' => $contract->id,
            'sequence' => 1,
            'due_date' => now()->subDay()->toDateString(),
            'amount' => 200,
            'status' => InstallmentPaymentStatus::Pending,
        ]);

        $this->artisan('installments:remind')->assertSuccessful();

        $this->assertSame('overdue', InstallmentPayment::first()->status->value);
        Notification::assertSentTo($user, InstallmentDueNotification::class);
    }
}
