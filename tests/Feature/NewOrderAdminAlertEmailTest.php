<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewOrderAdminNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewOrderAdminAlertEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_alert_emails_receive_mail_notification(): void
    {
        Notification::fake();

        $settings = app(SettingsService::class);
        $settings->set('notifications_enabled', true, 'notifications');
        $settings->set('notify_order_placed_admin', true, 'notifications');
        $settings->set('notify_order_placed_customer', false, 'notifications');
        $settings->set('mail_host', 'smtp-relay.brevo.com', 'mail');
        $settings->set('mail_username', 'shop@example.com', 'mail');
        $settings->set('mail_password', 'smtp-key', 'mail');
        $settings->set('mail_from_address', 'shop@example.com', 'mail');
        $settings->set('new_order_notification_emails', [
            'orders@homeblendstore.com',
            'manager@homeblendstore.com',
        ], 'notifications');

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Item',
            'slug' => 'item',
            'sku' => 'SKU-1',
            'regular_price' => 100,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::create([
            'order_number' => 'HB-TEST-ORDER',
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'billing_address' => ['email' => $user->email],
            'shipping_address' => ['email' => $user->email],
            'subtotal' => 100,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 100,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);

        app(NotificationDispatcher::class)->orderPlaced($order);

        Notification::assertSentOnDemand(
            NewOrderAdminNotification::class,
            function (NewOrderAdminNotification $notification, array $channels, object $notifiable): bool {
                return ($notifiable->routes['mail'] ?? null) === 'orders@homeblendstore.com';
            }
        );

        Notification::assertSentOnDemand(
            NewOrderAdminNotification::class,
            function (NewOrderAdminNotification $notification, array $channels, object $notifiable): bool {
                return ($notifiable->routes['mail'] ?? null) === 'manager@homeblendstore.com';
            }
        );
    }

    public function test_settings_service_resolves_legacy_admin_email_as_order_alert(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('admin_notification_email', 'legacy@homeblendstore.com', 'notifications');

        $this->assertSame(
            ['legacy@homeblendstore.com'],
            $settings->newOrderNotificationEmails()
        );
    }
}
