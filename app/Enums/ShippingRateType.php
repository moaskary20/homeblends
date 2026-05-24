<?php

namespace App\Enums;

enum ShippingRateType: string
{
    case Flat = 'flat';
    case Weight = 'weight';
    case Price = 'price';

    public function label(): string
    {
        return match ($this) {
            self::Flat => __('ecommerce.shipping_type_flat'),
            self::Weight => __('ecommerce.shipping_type_weight'),
            self::Price => __('ecommerce.shipping_type_price'),
        };
    }
}
