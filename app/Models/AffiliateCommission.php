<?php

namespace App\Models;

use App\Enums\AffiliateCommissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id', 'order_id', 'affiliate_click_id', 'order_amount',
        'commission_rate', 'commission_amount', 'currency', 'status',
        'approved_at', 'cancelled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliateCommissionStatus::class,
            'order_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(AffiliateClick::class, 'affiliate_click_id');
    }
}
