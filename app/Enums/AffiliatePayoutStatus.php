<?php

namespace App\Enums;

enum AffiliatePayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.payout_pending'),
            self::Processing => __('ecommerce.payout_processing'),
            self::Paid => __('ecommerce.payout_paid'),
            self::Rejected => __('ecommerce.payout_rejected'),
        };
    }
}
