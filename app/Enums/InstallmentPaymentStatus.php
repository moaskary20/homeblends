<?php

namespace App\Enums;

enum InstallmentPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.installment_payment_pending'),
            self::Paid => __('ecommerce.installment_payment_paid'),
            self::Overdue => __('ecommerce.installment_payment_overdue'),
            self::Failed => __('ecommerce.installment_payment_failed'),
        };
    }
}
