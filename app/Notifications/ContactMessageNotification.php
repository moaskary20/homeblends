<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{name: string, phone: string, email: string, message: string}  $payload
     */
    public function __construct(protected array $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('ecommerce.contact_form_mail_subject', ['name' => $this->payload['name']]))
            ->replyTo($this->payload['email'], $this->payload['name'])
            ->line(__('ecommerce.contact_form_mail_intro'))
            ->line(__('ecommerce.contact_form_name').': '.$this->payload['name'])
            ->line(__('ecommerce.contact_form_phone').': '.$this->payload['phone'])
            ->line(__('ecommerce.contact_form_email').': '.$this->payload['email'])
            ->line(__('ecommerce.contact_form_message').':')
            ->line($this->payload['message']);
    }
}
