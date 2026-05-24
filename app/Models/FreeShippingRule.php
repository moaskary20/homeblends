<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeShippingRule extends Model
{
    protected $fillable = ['shipping_zone_id', 'min_order_amount', 'is_active'];

    protected function casts(): array
    {
        return [
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
