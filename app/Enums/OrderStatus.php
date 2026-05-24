<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.order_status_pending'),
            self::Confirmed => __('ecommerce.order_status_confirmed'),
            self::Processing => __('ecommerce.order_status_processing'),
            self::Shipped => __('ecommerce.order_status_shipped'),
            self::Delivered => __('ecommerce.order_status_delivered'),
            self::Cancelled => __('ecommerce.order_status_cancelled'),
            self::Refunded => __('ecommerce.order_status_refunded'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Confirmed => 'info',
            self::Processing => 'warning',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'danger',
        };
    }
}
