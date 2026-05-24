<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Paypal = 'paypal';
    case CashOnDelivery = 'cash_on_delivery';
    case LocalProvider = 'local_provider';

    public function label(): string
    {
        return match ($this) {
            self::Paypal => __('ecommerce.payment_gateway_paypal'),
            self::CashOnDelivery => __('ecommerce.payment_cod'),
            self::LocalProvider => __('ecommerce.payment_gateway_local'),
        };
    }

    public static function codes(): array
    {
        return array_column(self::cases(), 'value');
    }
}
