<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\Admin\NewOrderAdminNotification;
use App\Notifications\Admin\NewRefundAdminNotification;
use App\Notifications\Admin\NewReturnAdminNotification;
use App\Notifications\Orders\OrderPlacedNotification;
use App\Notifications\Orders\OrderStatusUpdatedNotification;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Notification;

class NotificationDispatcher
{
    public function __construct(protected SettingsService $settings) {}

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing(['user', 'items']);

        if ($this->settings->isEnabled('notify_order_placed_customer') && $order->user) {
            $order->user->notify(new OrderPlacedNotification($order));
        }

        if ($this->settings->isEnabled('notify_order_placed_admin')) {
            $this->notifyAdmins(
                new NewOrderAdminNotification($order),
                $this->settings->newOrderNotificationEmails()
            );
        }
    }

    public function orderStatusUpdated(Order $order, ?string $comment = null): void
    {
        $order->loadMissing('user');

        if ($this->settings->isEnabled('notify_order_status_customer') && $order->user) {
            $order->user->notify(new OrderStatusUpdatedNotification($order, $comment));
        }
    }

    public function refundRequested(RefundRequest $refund): void
    {
        $refund->loadMissing(['order', 'user']);

        if ($this->settings->isEnabled('notify_refund_admin')) {
            $this->notifyAdmins(
                new NewRefundAdminNotification($refund),
                $this->settings->adminAlertEmails()
            );
        }
    }

    public function returnRequested(ReturnRequest $return): void
    {
        $return->loadMissing(['order', 'user']);

        if ($this->settings->isEnabled('notify_return_admin')) {
            $this->notifyAdmins(
                new NewReturnAdminNotification($return),
                $this->settings->adminAlertEmails()
            );
        }
    }

    /**
     * @param  list<string>  $extraEmails
     */
    protected function notifyAdmins(object $notification, array $extraEmails = []): void
    {
        $admins = $this->settings->adminRecipients()->filter(fn (User $u) => $u->exists);

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }

        $alreadyNotified = $admins
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->all();

        foreach ($extraEmails as $email) {
            $normalized = strtolower(trim($email));
            if ($normalized === '' || in_array($normalized, $alreadyNotified, true)) {
                continue;
            }

            Notification::route('mail', $normalized)->notify($notification);
            $alreadyNotified[] = $normalized;
        }
    }
}
