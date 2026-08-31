<?php

namespace App\Enums;

enum OrderItemFulfillmentStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('ecommerce.item_status_pending'),
            self::Preparing => __('ecommerce.item_status_preparing'),
            self::Shipped => __('ecommerce.item_status_shipped'),
            self::Delivered => __('ecommerce.item_status_delivered'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Preparing => 'warning',
            self::Shipped => 'info',
            self::Delivered => 'success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->label()]
        )->all();
    }
}
