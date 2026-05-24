<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\ReturnRequestResource;
use App\Models\ReturnRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReturnAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReturnRequest $return) {}

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
            ->subject(__('ecommerce.mail_admin_return_subject'))
            ->markdown('mail.admin.new-return', ['return' => $this->return]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('ecommerce.admin_notif_return'))
            ->body($this->return->order?->order_number ?? '')
            ->icon('heroicon-o-arrow-path')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->url(ReturnRequestResource::getUrl('edit', ['record' => $this->return])),
            ])
            ->getDatabaseMessage();
    }
}
