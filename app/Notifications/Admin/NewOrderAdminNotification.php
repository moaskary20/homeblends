<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->email ?? false) {
            $channels[] = 'mail';
        }

        if ($notifiable->exists ?? false) {
            $channels[] = 'database';
        }

        return $channels ?: ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject(__('ecommerce.mail_admin_new_order_subject', ['number' => $order->order_number]))
            ->markdown('mail.admin.new-order', ['order' => $order]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('ecommerce.admin_notif_new_order'))
            ->body(__('ecommerce.admin_notif_new_order_body', [
                'number' => $this->order->order_number,
                'total' => number_format((float) $this->order->total, 2),
            ]))
            ->icon('heroicon-o-shopping-bag')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label(__('ecommerce.view_order'))
                    ->url(OrderResource::getUrl('edit', ['record' => $this->order])),
            ])
            ->getDatabaseMessage();
    }
}
