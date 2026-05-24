<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\RefundRequestResource;
use App\Models\RefundRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRefundAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RefundRequest $refund) {}

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
        return (new MailMessage)
            ->subject(__('ecommerce.mail_admin_refund_subject'))
            ->markdown('mail.admin.new-refund', ['refund' => $this->refund]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('ecommerce.admin_notif_refund'))
            ->body($this->refund->order?->order_number ?? '')
            ->icon('heroicon-o-arrow-uturn-left')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->url(RefundRequestResource::getUrl('edit', ['record' => $this->refund])),
            ])
            ->getDatabaseMessage();
    }
}
