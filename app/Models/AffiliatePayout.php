<?php

namespace App\Models;

use App\Enums\AffiliatePayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayout extends Model
{
    protected $fillable = [
        'affiliate_id', 'amount', 'currency', 'status', 'payment_method',
        'payment_reference', 'notes', 'admin_notes', 'processed_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliatePayoutStatus::class,
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
