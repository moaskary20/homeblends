<?php

namespace App\Console\Commands;

use App\Enums\InstallmentPaymentStatus;
use App\Models\InstallmentPayment;
use App\Services\Installment\InstallmentScheduler;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

class SendInstallmentRemindersCommand extends Command
{
    protected $signature = 'installments:remind';

    protected $description = 'Mark overdue installments and email customers about upcoming and due payments';

    public function handle(NotificationDispatcher $dispatcher, InstallmentScheduler $scheduler): int
    {
        $today = now()->startOfDay();

        $rows = InstallmentPayment::query()
            ->with(['contract.user', 'contract.order', 'contract.offer'])
            ->whereIn('status', [
                InstallmentPaymentStatus::Pending->value,
                InstallmentPaymentStatus::Overdue->value,
            ])
            ->get();

        $sent = 0;

        foreach ($rows as $row) {
            if ($row->due_date->lt($today) && $row->status === InstallmentPaymentStatus::Pending) {
                $row->update(['status' => InstallmentPaymentStatus::Overdue]);
                $scheduler->refreshContractStatus($row->contract);
            }

            $kind = null;
            $flag = null;

            if ($row->due_date->lt($today) && ! $row->overdue_reminded_at) {
                $kind = 'overdue';
                $flag = 'overdue_reminded_at';
            } elseif ($row->due_date->isSameDay($today) && ! $row->due_reminded_at) {
                $kind = 'due';
                $flag = 'due_reminded_at';
            } elseif ($row->due_date->betweenIncluded($today->copy()->addDay(), $today->copy()->addDays(3)) && ! $row->pre_due_reminded_at) {
                $kind = 'upcoming';
                $flag = 'pre_due_reminded_at';
            }

            if (! $kind || ! $row->contract?->user) {
                continue;
            }

            if ($dispatcher->installmentDue($row, $kind)) {
                $row->update([$flag => now()]);
                $sent++;
            }
        }

        $this->info("Sent {$sent} installment reminders.");

        return self::SUCCESS;
    }
}
