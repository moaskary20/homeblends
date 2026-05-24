<?php

namespace App\Enums;

enum CouponType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => __('خصم ثابت'),
            self::Percentage => __('خصم نسبة مئوية'),
            self::FreeShipping => __('شحن مجاني'),
        };
    }
}
