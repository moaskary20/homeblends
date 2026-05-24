<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'points', 'type', 'description', 'expires_at', 'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'earn' => __('ecommerce.loyalty_type_earn'),
            'redeem' => __('ecommerce.loyalty_type_redeem'),
            'expire' => __('ecommerce.loyalty_type_expire'),
            'adjust' => __('ecommerce.loyalty_type_adjust'),
            'wallet' => __('ecommerce.loyalty_type_wallet'),
            default => $this->type,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
