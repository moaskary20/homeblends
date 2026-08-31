<?php

namespace App\Notifications\Installments;

use App\Models\InstallmentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstallmentDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public InstallmentPayment $installment,
        public string $kind = 'due',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $installment = $this->installment->loadMissing(['contract.order', 'contract.offer']);
        $contract = $installment->contract;
        $subjectKey = match ($this->kind) {
            'upcoming' => 'ecommerce.mail_installment_upcoming_subject',
            'overdue' => 'ecommerce.mail_installment_overdue_subject',
            default => 'ecommerce.mail_installment_due_subject',
        };

        return (new MailMessage)
            ->subject(__($subjectKey, [
                'number' => $contract?->order?->order_number ?? '',
                'sequence' => $installment->sequence,
            ]))
            ->markdown('mail.installments.due', [
                'installment' => $installment,
                'contract' => $contract,
                'user' => $notifiable,
                'kind' => $this->kind,
            ]);
    }
}
