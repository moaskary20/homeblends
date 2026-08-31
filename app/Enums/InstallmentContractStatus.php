<?php

namespace App\Enums;

enum InstallmentContractStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('ecommerce.installment_contract_active'),
            self::Completed => __('ecommerce.installment_contract_completed'),
            self::Overdue => __('ecommerce.installment_contract_overdue'),
            self::Cancelled => __('ecommerce.installment_contract_cancelled'),
        };
    }
}
