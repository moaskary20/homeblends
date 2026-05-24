<?php

namespace App\Enums;

enum AffiliateStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.affiliate_pending'),
            self::Active => __('ecommerce.affiliate_active'),
            self::Suspended => __('ecommerce.affiliate_suspended'),
            self::Rejected => __('ecommerce.affiliate_rejected'),
        };
    }
}
