<?php

namespace App\Enums;

enum AffiliateCommissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.commission_pending'),
            self::Approved => __('ecommerce.commission_approved'),
            self::Paid => __('ecommerce.commission_paid'),
            self::Cancelled => __('ecommerce.commission_cancelled'),
        };
    }
}
