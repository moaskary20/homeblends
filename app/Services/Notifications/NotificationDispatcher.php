<?php

namespace App\Services\Notifications;

use App\Models\InstallmentPayment;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\Admin\NewOrderAdminNotification;
use App\Notifications\Admin\NewRefundAdminNotification;
use App\Notifications\Admin\NewReturnAdminNotification;
use App\Notifications\Installments\InstallmentDueNotification;
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

    public function installmentDue(InstallmentPayment $installment, string $kind = 'due'): bool
    {
        $installment->loadMissing(['contract.user', 'contract.order']);
        $user = $installment->contract?->user;

        if (! $user || ! $this->settings->isEnabled('notify_installment_due_customer')) {
            return false;
        }

        $user->notify(new InstallmentDueNotification($installment, $kind));

        return true;
    }

    /**
     * In-app (database) alerts go to admin users.
     * Email alerts go only to the configured addresses (not every admin account email).
     *
     * @param  list<string>  $extraEmails
     */
    protected function notifyAdmins(object $notification, array $extraEmails = []): void
    {
        $admins = $this->settings->adminRecipients()->filter(fn (User $u) => $u->exists);

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        $mailEmails = collect($extraEmails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($mailEmails->isEmpty()) {
            $mailEmails = $admins
                ->pluck('email')
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values();
        }

        foreach ($mailEmails as $email) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
